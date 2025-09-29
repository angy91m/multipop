<?php

defined( 'ABSPATH' ) || exit;

class MultipopEventsPlugin {
  public static function init() {

    // ADD mpop_event POST TYPE
    add_action( 'init', function () {
      $labels = array(
        'name'                  => 'Eventi',
        'singular_name'         => 'Evento',
        'menu_name'             => 'Eventi',
        'name_admin_bar'        => 'Evento',
        'add_new'               => 'Aggiungi Nuovo',
        'add_new_item'          => 'Aggiungi Nuovo Evento',
        'new_item'              => 'Nuovo Evento',
        'edit_item'             => 'Modifica Evento',
        'view_item'             => 'Vedi Evento',
        'all_items'             => 'Tutti gli Articoli Eventi',
        'search_items'          => 'Cerca Eventi',
        'not_found'             => 'Nessun evento trovato.',
        'not_found_in_trash'    => 'Nessun evento trovato nel cestino.'
      );

      $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'show_in_rest'       => true,   // abilita REST API / Gutenberg
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'eventi' ),
        'has_archive'        => 'archivio-eventi',
        'hierarchical'       => false,
        'menu_position'      => 5,
        'menu_icon'          => 'dashicons-calendar',
        'supports'           => array(
          'title',
          'editor',
          'excerpt',
          'author',
          'thumbnail',
          'comments',
          'revisions',
          'trackbacks',
          'custom-fields',
          'post-formats'
        ),
        'taxonomies'         => array( 'category', 'post_tag' ),
        'map_meta_cap'       => true
      );

      register_post_type( 'mpop_event', $args );
    } );

    // HIDING META INFO FROM EVENT RENDERING
    add_filter( 'render_block', function($block_content, $block) {
      if (
        get_post_type() == 'mpop_event'
        && isset($block['blockName'])
        && (
          (
            in_array($block['blockName'], ['core/post-author','core/post-date'])
          )
          || (
            $block['blockName'] == 'core/template-part'
            && isset($block['attrs']['slug'])
            && $block['attrs']['slug'] == 'post-meta'
          )
        )
      ) {
        return '';
      }
      return $block_content;
    }, 10, 2 );


    // META FIELDS
    add_action('add_meta_boxes', function () {
      add_meta_box(
        'mpop_event_extra_fields',
        'Dettagli evento',
        [self::class, 'extra_fields'],
        'mpop_event',
        'normal',
        'high'
      );
    });
    add_action('save_post_mpop_event', [self::class, 'extra_fields_save'], 10, 2);
  }
  public static function extra_fields($post) {
    wp_nonce_field( 'mpop_event_extra_fields_nonce_action', 'mpop_event_extra_fields_nonce' );
    $start_date = date_create('now', new DateTimeZone(current_time('e')));
    $end_date = date_create('now', new DateTimeZone(current_time('e')));
    $end_date->add(new DateInterval('PT1H'));
    $start_ts = get_post_meta( $post->ID, '_mpop_event_start', true );
    $end_ts = get_post_meta( $post->ID, '_mpop_event_end', true );
    if ($start_ts) {
      $start_date->setTimestamp(intval($start_ts));
    }
    if ($end_ts) {
      $end_date->setTimestamp(intval($end_ts));
    }
  ?>
    <p>
      <label for="">Data inizio</label>
      <input
        id="mpop_event_start_date"
        name="mpop_event_start_date"
        type="date"
        value="<?=$start_date->format('Y-m-d')?>"
      />
      &nbsp;&nbsp;&nbsp;&nbsp;
      <label for="">Ora inizio</label>
      <input
        id="mpop_event_start_time"
        name="mpop_event_start_time"
        type="time"
        value="<?=$start_date->format('H:i')?>"
      />
    </p>
    <p>
      <label for="">Data fine</label>
      <input
        id="mpop_event_end_date"
        name="mpop_event_end_date"
        type="date"
        value="<?=$end_date->format('Y-m-d')?>"
      />
      &nbsp;&nbsp;&nbsp;&nbsp;
      <label for="">Ora fine</label>
      <input
        id="mpop_event_end_time"
        name="mpop_event_end_time"
        type="time"
        value="<?=$end_date->format('H:i')?>"
      />
    </p>
    <script type="text/javascript">
    window.addEventListener('load', ()=>{
      const startDate = new Date(),
      endDate = new Date(),
      startDateEl = document.querySelector('#mpop_event_start_date'),
      startTimeEl = document.querySelector('#mpop_event_start_time'),
      endDateEl = document.querySelector('#mpop_event_end_date'),
      endTimeEl = document.querySelector('#mpop_event_end_time'),
      mpopEventDateSet = () => {
        if (startDateEl.value && startTimeEl.value) {
          startDate.setTime(new Date(startDateEl.value + 'T' + startTimeEl.value + ':00.000').getTime());
        } else {
          startDate.setTime(NaN);
        }
        if (endDateEl.value && endTimeEl.value) {
          endDate.setTime(new Date(endDateEl.value + 'T' + endTimeEl.value + ':00.000').getTime());
        } else {
          endDate.setTime(NaN);
        }
        if (!isNaN(startDate) && !isNaN(endDate) && startDate.getTime() <= endDate.getTime()) {
          wp.data.dispatch( 'core/editor' ).unlockPostSaving( 'mpopEventDateLock' );
        } else {
          wp.data.dispatch( 'core/editor' ).lockPostSaving( 'mpopEventDateLock' );
        }
      };
      mpopEventDateSet();
      [startDateEl, startTimeEl, endDateEl, endTimeEl].forEach(el => el.addEventListener('change', mpopEventDateSet));
    });
    </script>
  <?php
  }
  public static function extra_fields_save($post_id, $post) {
    $valid_date = false;
    if (
      !isset( $_POST['mpop_event_extra_fields_nonce'] )
      || !wp_verify_nonce( $_POST['mpop_event_extra_fields_nonce'], 'mpop_event_extra_fields_nonce_action' )
      || !current_user_can( 'edit_post', $post_id )
    ) {
      return;
    }
    try {
      $start_date = MultipopPlugin::validate_date($_POST['mpop_event_start_date']);
      $start_time = MultipopPlugin::validate_time($_POST['mpop_event_start_time']);
      $start_date->setTime($start_time[0], $start_time[1]);
      $end_date = MultipopPlugin::validate_date($_POST['mpop_event_end_date']);
      $end_time = MultipopPlugin::validate_time($_POST['mpop_event_end_time']);
      $end_date->setTime($end_time[0], $end_time[1]);
      if ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
        update_post_meta($post_id, '_mpop_event_start', $start_date->getTimestamp());
        update_post_meta($post_id, '_mpop_event_end', $end_date->getTimestamp());
        $valid_date = true;
      }
    } catch(Exception $e) {}
    if (!$valid_date && $post->post_status == 'publish') {
      wp_update_post(['ID'=>$post_id, 'post_status'=>'draft']);
    }
  }
}