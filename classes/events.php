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
        'has_archive'        => true,
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
  }
  public static function extra_fields() {
    wp_nonce_field( 'mpop_event_extra_fields_nonce_action', 'mpop_event_extra_fields_nonce' );
  ?>
    <p>
      <label for="">Inizio</label>
      <input
        id="mpop_event_start"
        name="mpop_event_start"
        type="datetime-local"
      />
    </p>
    <p>
      <label for="">Fine</label>
      <input
        id="mpop_event_end"
        name="mpop_event_end"
        type="datetime-local"
      />
    </p>
  <?php
  }
}