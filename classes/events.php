<?php

defined( 'ABSPATH' ) || exit;

class MultipopEventsPlugin {
  const DAY_NAMES = [
    'Domenica',
    'Lunedì',
    'Martedì',
    'Mercoledì',
    'Giovedì',
    'Venerdì',
    'Sabato'
  ];
  const MONTH_NAMES = [
    'Gennaio',
    'Febbraio',
    'Marzo',
    'Aprile',
    'Maggio',
    'Giugno',
    'Luglio',
    'Agosto',
    'Settembre',
    'Ottobre',
    'Novembre',
    'Dicembre'
  ];
  public static function get_day_name($d) {
    return self::DAY_NAMES[intval($d->format('w'))];
  }
  public static function get_month_name($d) {
    return self::MONTH_NAMES[intval($d->format('n'))-1];
  }
  public static function human_date($d) {
    return substr(self::get_day_name($d), 0, 3) . ', ' . $d->format('j') . ' ' . substr(self::get_month_name($d), 0, 3) . ' ' . $d->format('Y');
  }
  public static function init() {
    // $role = get_role('administrator');
    // $role->add_cap('edit_post');
    // $role->add_cap('read_post');
    // $role->add_cap('delete_post');
    // $role->add_cap('edit_posts');
    // $role->add_cap('delete_posts');
    // $role->add_cap('edit_others_posts');
    // $role->add_cap('publish_posts');
    // $role->add_cap('read_private_posts');

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
        'all_items'             => 'Tutti gli Eventi',
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

    add_filter('user_has_cap', function( $allcaps, $caps, $args, $user ) {
      if (
        !empty($user->roles)
        && str_starts_with($user->roles[0], 'multipopola')
        && isset( $args[0] )
        && in_array($args[0], [
          'edit_post',
          'delete_post',
          'edit_posts',
          'delete_posts',
          'edit_others_posts',
          'publish_posts',
          'read_private_posts'
        ])
      ) {
        $post_type = '';
        if (isset($args[2])) {
          $post = get_post($args[2]);
          $post_type = $post->post_type;
        } elseif (isset( $_REQUEST['post_type'] )) {
          $post_type = sanitize_text_field( $_REQUEST['post_type'] );
        }
        if ($post_type == 'mpop_event' && get_user_meta( $user->ID, '_edit_mpop_events', true )) $allcaps[ $args[0] ] = true;
      }
      return $allcaps;
    }, 10, 4);

    // SHORTCODES

    add_shortcode('mpop_event_start_date', function () {
      $post = get_post();
      if (!$post || $post->post_type != 'mpop_event' || !$post->_mpop_event_start) return '';
      $d = date_create('now', new DateTimeZone(current_time('e')));
      $d->setTimestamp(intval($post->_mpop_event_start));
      return self::human_date($d);
    });
    add_shortcode('mpop_event_start_time', function () {
      $post = get_post();
      if (!$post || $post->post_type != 'mpop_event' || !$post->_mpop_event_start) return '';
      $d = date_create('now', new DateTimeZone(current_time('e')));
      $d->setTimestamp(intval($post->_mpop_event_start));
      return $d->format('H:i');
    });
    add_shortcode('mpop_event_end_date', function () {
      $post = get_post();
      if (!$post || $post->post_type != 'mpop_event' || !$post->_mpop_event_end) return '';
      $d = date_create('now', new DateTimeZone(current_time('e')));
      $d->setTimestamp(intval($post->_mpop_event_end));
      return self::human_date($d);
    });
    add_shortcode('mpop_event_end_time', function () {
      $post = get_post();
      if (!$post || $post->post_type != 'mpop_event' || !$post->_mpop_event_end) return '';
      $d = date_create('now', new DateTimeZone(current_time('e')));
      $d->setTimestamp(intval($post->_mpop_event_end));
      return $d->format('H:i');
    });
    add_shortcode('mpop_event_details', function () {
      $post = get_post();
      if (!$post || $post->post_type != 'mpop_event' || !$post->_mpop_event_start) return '';
      $json_event = '<script type="application/ld+json">' . self::event2ld_json($post) . '</script>';
      $sd = date_create('now', new DateTimeZone(current_time('e')));
      $sd->setTimestamp(intval($post->_mpop_event_start));
      $start_date = self::human_date($sd);
      $start_time = $sd->format('H:i');
      if (!$post->_mpop_event_end) return $start_date . ' ' . $start_time;
      $ed = date_create('now', new DateTimeZone(current_time('e')));
      $ed->setTimestamp(intval($post->_mpop_event_end));
      $end_date = self::human_date($ed);
      $end_time = $ed->format('H:i');
      $end_string = $start_date != $end_date ? $end_date . ' ' . $end_time : ($start_time != $end_time ? $end_time : '');
      $location = '';
      if ($post->_mpop_event_location) {
        $location = '<br>'. MultipopPlugin::dashicon('location') .' <a href="https://www.google.com/maps/search/?api=1&query=' . urlencode($post->_mpop_event_location) . '" target="_blank">' . ($post->_mpop_event_location_name ? $post->_mpop_event_location_name . ' - ' : '') . $post->_mpop_event_location . '</a> ' . MultipopPlugin::dashicon('external');
      } elseif ($post->_mpop_event_location_name) {
        $location = '<br>'. $post->_mpop_event_location_name;
      }
      return $json_event . '<p>' . MultipopPlugin::dashicon('clock') .' ' . $start_date . ' ' . $start_time . ($end_string ? ' - ' . $end_string : '') . $location . '</p>';
    });

    add_shortcode('mpop_events_page', function () {
      return MultipopPlugin::html_to_string([self::class, 'events_page']);
    });

    add_filter('run_wptexturize', function($run_texturize) {
      $p = get_post();
      if (!$p) return $run_texturize;
      if (in_array((wp_get_theme()->get_page_templates()[$p->page_template] ?? ''), ['Eventi', 'Evento'])) return false;
      return $run_texturize;
    });

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
    $location_name = get_post_meta( $post->ID, '_mpop_event_location_name', true );
    $location = get_post_meta( $post->ID, '_mpop_event_location', true );
  ?>
    <p>
      <label for="mpop_event_start_date">Data inizio</label>
      <input
        id="mpop_event_start_date"
        name="mpop_event_start_date"
        type="date"
        value="<?=$start_date->format('Y-m-d')?>"
      />
      &nbsp;&nbsp;&nbsp;&nbsp;
      <label for="mpop_event_start_time">Ora inizio</label>
      <input
        id="mpop_event_start_time"
        name="mpop_event_start_time"
        type="time"
        value="<?=$start_date->format('H:i')?>"
      />
    </p>
    <p>
      <label for="mpop_event_end_date">Data fine</label>
      <input
        id="mpop_event_end_date"
        name="mpop_event_end_date"
        type="date"
        value="<?=$end_date->format('Y-m-d')?>"
      />
      &nbsp;&nbsp;&nbsp;&nbsp;
      <label for="mpop_event_end_time">Ora fine</label>
      <input
        id="mpop_event_end_time"
        name="mpop_event_end_time"
        type="time"
        value="<?=$end_date->format('H:i')?>"
      />
    </p>
    <p>
      <label for="mpop_event_location_name">Nome luogo</label>
      <input
        id="mpop_event_location_name"
        name="mpop_event_location_name"
        type="text"
        value="<?=$location_name?>"
      />
    </p>
    <p>
      <label for="mpop_event_location">Indirizzo</label>
      <input
        id="mpop_event_location"
        name="mpop_event_location"
        type="text"
        value="<?=$location?>"
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
    $edits = [
      'ID' => $post_id
    ];
    $event_template = array_search('Evento', wp_get_theme()->get_page_templates(), true);
    if ($event_template) {
      $edits['page_template'] = $event_template;
    }
    $meta_input = [];
    if (isset($_POST['mpop_event_location_name'])) {
      $meta_input['_mpop_event_location_name'] = trim($_POST['mpop_event_location_name']);
    }
    if (isset($_POST['mpop_event_location'])) {
      $location = trim($_POST['mpop_event_location']);
      $meta_input['_mpop_event_location'] = $location;
      $geo = self::geocode($location);
      if (!$geo) {
        delete_post_meta($post_id, '_mpop_event_lat');
        delete_post_meta($post_id, '_mpop_event_lng');
        delete_post_meta($post_id, '_mpop_event_zones');
      } else {
        $meta_input['_mpop_event_lat'] = $geo['lat'];
        $meta_input['_mpop_event_lng'] = $geo['lng'];
        if ($geo['zones']) {
          $meta_input['_mpop_event_zones'] = $geo['zones'];
        } else {
          delete_post_meta($post_id, '_mpop_event_zones');
        }
      }
    }
    try {
      $start_date = MultipopPlugin::validate_date($_POST['mpop_event_start_date']);
      $start_time = MultipopPlugin::validate_time($_POST['mpop_event_start_time']);
      $start_date->setTime($start_time[0], $start_time[1]);
      $end_date = MultipopPlugin::validate_date($_POST['mpop_event_end_date']);
      $end_time = MultipopPlugin::validate_time($_POST['mpop_event_end_time']);
      $end_date->setTime($end_time[0], $end_time[1]);
      if ($start_date->getTimestamp() <= $end_date->getTimestamp()) {
        $meta_input['_mpop_event_start'] = $start_date->getTimestamp();
        $meta_input['_mpop_event_end'] = $end_date->getTimestamp();
        $valid_date = true;
      }
    } catch(Exception $e) {}
    if (!$valid_date && $post->post_status == 'publish') {
      $edits['post_status'] = 'draft';
    }
    if (count($edits) > 1 || count($meta_input)) {
      $edits['meta_input'] = $meta_input;
      remove_action('save_post_mpop_event', [self::class, 'extra_fields_save'], 10);
      wp_update_post($edits);
      add_action('save_post_mpop_event', [self::class, 'extra_fields_save'], 10, 2);
    }
  }
  public static function events_page() {
    require MULTIPOP_PLUGIN_PATH . '/shortcodes/events.php';
  }
  public static function curl_init($url, $settings = []) {
    $curlObj = curl_init($url);
    $settings += [
      CURLOPT_AUTOREFERER => true,
      CURLOPT_RETURNTRANSFER => true,
      CURLOPT_FOLLOWLOCATION => true,
      CURLOPT_NOSIGNAL => true,
      CURLOPT_TIMEOUT => 5
    ];
    foreach($settings as $key => $value) {
      curl_setopt($curlObj, $key, $value);
    }
    return $curlObj;
  }
  public static function geocode($address, $key = '') {
    if (!$address) return false;
    $mpop_plugin = MultipopPlugin::$instances[0];
    if (!$key) {
      $key = $mpop_plugin->get_settings()['gmaps_api_key'];
    }
    $curlObj = self::curl_init('https://maps.googleapis.com/maps/api/geocode/json?address=' . urlencode($address) . "&key=$key");
    $data = curl_exec($curlObj);
    if (!$data) return false;
    $data = json_decode($data, true);
    if ($data['status'] != 'OK' || empty($data['results'])) return false;
    $data = $data['results'][0];
    $comune_name = false;
    $sigla = false;
    $res = $data['geometry']['location'] + [
      'zones' => false
    ];
    foreach($data['address_components'] as $c) {
      if (in_array('administrative_area_level_3', $c['types'])) {
        $comune_name = mb_strtoupper(isset($c['long_name']) && $c['long_name'] ? $c['long_name'] : $c['short_name'], 'UTF-8');
        if ($sigla) break;
      } else if (in_array('administrative_area_level_2', $c['types'])) {
        $sigla = $c['short_name'];
        if ($comune_name) break;
      }
    }
    if (!$comune_name || !$sigla) return $res;
    $comuni = array_map(function($c) use ($comune_name) {
      similar_text($c['nome'], $comune_name, $perc);
      $c['similarity'] = $perc;
      return $c;
    }, array_filter($mpop_plugin->get_comuni_all(), function($c) use ($sigla) {
      return $c['soppresso'] !== true && $c['provincia']['sigla'] == $sigla;
    }));
    $comune = false;
    foreach($comuni as $c) {
      if (!$comune || $c['similarity'] > $comune['similarity']) {
        $comune = $c;
      }
    }
    if ($comune) $res['zones'] = [
      $comune['codiceCatastale'],
      $comune['provincia']['sigla'],
      'reg_' . $comune['provincia']['regione']
    ];
    return $res;
  }
  public static function search_events_posts_orderby($orderby, $q) {
    if (isset($q->query_vars['mp_extra_sort'])) {
      global $wpdb;
      $orderby = '';
      foreach($q->query_vars['mp_extra_sort'] as $k=>$sort) {
        switch($k) {
          case 'title':
            $orderby .= "$wpdb->posts.post_title " . ($sort ? 'ASC' : 'DESC') . ', ';
            break;
          case 'start':
            $orderby .= "CAST(mt1.meta_value AS UNSIGNED) " . ($sort ? 'ASC' : 'DESC') . ', ';
            break;
          case 'end':
            $orderby .= "CAST($wpdb->postmeta.meta_value AS UNSIGNED) " . ($sort ? 'ASC' : 'DESC') . ', ';
            break;
        }
      }
      $orderby = substr($orderby,0,-2);
    }
    remove_filter('posts_orderby', [self::class, 'search_events_posts_orderby'], 10);
    return $orderby;
  }
  public static function search_events($options = []) {
    $default_sort = [
      'end' => true,
      'title' => true
    ];
    $options += [
      'txt' => '',
      'zones' => [],
      'min' => null,
      'max' => null,
      'sortby' => $default_sort,
      'pag' => '1'
    ];
    $page = 0;
    if (is_string($options['pag'])) {
      $page = intval($options['pag']);
      if ($page > 0) {
        $page--;
      } else {
        $page = 0;
      }
    } elseif(is_int($options['pag']) && $options['pag'] > 0) {
      $page = $options['pag']-1;
    } elseif($options['pag'] === true) {
      $page = true;
    } else {
      $page = 0;
    }
    if ($page !== true) $options['pag'] = $page +1;
    $query_args = [
      'post_type' => 'mpop_event',
      'post_status' => 'publish',
      'suppress_filters' => false,
      'numberposts' => $page === true ? -1 : 25,
      'paged' => $page === true ? 0 : $page
    ];
    $meta_q = ['relation' => 'AND'];
    if (is_string($options['txt']) && !empty(trim($options['txt']))) $query_args['s'] = trim($options['txt']);
    if (is_string($options['min'])) {
      $min_date = MultipopPlugin::validate_date($options['min']);
    } else {
      $min_date = date_create('now', new DateTimeZone(current_time('e')));
      $min_date->setTime(0,0,0,0);
    }
    $meta_q['_mpop_event_end'] = [
      'key' => '_mpop_event_end',
      'value' => $min_date->getTimestamp(),
      'compare' => '>=',
      'type' => 'UNSIGNED'
    ];
    if (is_string($options['max'])) {
      $max_date = MultipopPlugin::validate_date($options['max']);
    } else {
      $max_date = date_create('now', new DateTimeZone(current_time('e')));
      $max_date->setTimestamp($min_date->getTimestamp());
      $max_date->add(new DateInterval('P1M'));
    }
    $max_date->setTime(23,59,59);
    $meta_q['_mpop_event_start'] = [
      'key' => '_mpop_event_start',
      'value' => $max_date->getTimestamp(),
      'compare' => '<=',
      'type' => 'UNSIGNED'
    ];
    $zones = [];
    if (is_array($options['zones']) && count($options['zones'])) {
      $zones = array_filter($options['zones'], function($z) {return is_string($z) && (str_starts_with($z, 'reg_') || preg_match('/^([A-Z]{2})|([A-Z]\d{3})$/', $z));});
      $zones = array_unique($zones);
      $zones = array_values($zones);
    }
    if (count($zones)) {
      $meta_q['_mpop_event_zones'] = [
        'relation' => 'OR'
      ];
      foreach($zones as $zone) {
        $meta_q['_mpop_event_zones'][] = [
          'key' => '_mpop_event_zones',
          'value' => "\"$zone\"",
          'compare' => 'LIKE'
        ];
      }
    }
    $allowed_field_sorts = [
      'title'
    ];
    $allowed_meta_sorts = [
      'start',
      'end'
    ];
    if (!is_array($options['sortby'])) {
      $options['sortby'] = $default_sort;
    } else {
      $sort_keys = array_keys($options['sortby']);
      $fsort_by = [];
      foreach ($sort_keys as $k) {
        if (in_array($k, $allowed_field_sorts)) {
          $fsort_by[$k] = boolval($options['sortby'][$k]);
        } else if (in_array($k, $allowed_meta_sorts)) {
          $fsort_by[$k] = boolval($options['sortby'][$k]);
        }
      }
      $options['sortby'] = $fsort_by;
      if (empty($options['sortby'])) {
        $options['sortby'] = $default_sort;
      }
    }
    $query_args['mp_extra_sort'] = $options['sortby'];
    $query_args['meta_query'] = $meta_q;
    add_filter('posts_orderby', [self::class, 'search_events_posts_orderby'], 10, 2);
    $res = ['results' => get_posts($query_args), 'options' => [
      'txt' => $options['txt'],
      'min' => $min_date->format('Y-m-d'),
      'max' => $max_date->format('Y-m-d'),
      'sortby' => $options['sortby']
    ]];
    if ($page !== true) {
      $res['options']['pag'] = $options['pag'];
      $res['options']['zones'] = MultipopPlugin::$instances[0]->retrieve_zones_from_resp_zones($zones);
    }
    return $res;
  }
  public static function event2json($event) {
    $start_date = date_create('now', new DateTimeZone(current_time('e')));
    $start_date->setTimestamp(intval($event->_mpop_event_start));
    $end_date = date_create('now', new DateTimeZone(current_time('e')));
    $end_date->setTimestamp(intval($event->_mpop_event_end));
    return [
      'title' => $event->post_title,
      'excerpt' => $event->post_excerpt,
      'url' => get_permalink($event),
      'start' => $start_date->format('c'),
      'end' => $end_date->format('c'),
      'thumbnail' => get_the_post_thumbnail_url($event),
      'location_name' => $event->_mpop_event_location_name,
      'location' => $event->_mpop_event_location,
      'lat' => (double) $event->_mpop_event_lat,
      'lng' => (double) $event->_mpop_event_lng
    ];
  }
  public static function event2ld_json($event) {
    $start_date = date_create('now', new DateTimeZone(current_time('e')));
    $start_date->setTimestamp(intval($event->_mpop_event_start));
    $end_date = date_create('now', new DateTimeZone(current_time('e')));
    $end_date->setTimestamp(intval($event->_mpop_event_end));
    $location = [
      '@type' => 'Place'
    ];
    if ($event->_mpop_event_location_name) $location['name'] = $event->_mpop_event_location_name;
    if ($event->_mpop_event_location) $location['address'] = [
      '@type' => 'PostalAddress',
      'name' => $event->_mpop_event_location
    ];
    $thumbnail = get_the_post_thumbnail_url($event, 'full');
    $json = [
      '@context' => 'https://schema.org',
      '@type' => 'Event',
      'name' => $event->post_title,
      'startDate' => $start_date->format('c'),
      'endDate' => $end_date->format('c'),
      'eventStatus' => 'https://schema.org/EventScheduled',
      'image' => $thumbnail ? [$thumbnail] : [],
      'description' => $event->post_excerpt,
      'organizer' => [
        '@type' => 'Organization',
        'name' => 'Multipopolare A.P.S.',
        'url' => site_url('/')
      ]
    ];
    if (isset($location['name']) || isset($location['address'])) $json['location'] = $location;
    return json_encode($json, JSON_UNESCAPED_SLASHES);
  }
}