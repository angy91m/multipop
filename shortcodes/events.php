<?php
defined( 'ABSPATH' ) || exit;
if (
  !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-admin' )
  && !str_starts_with( $_SERVER['REQUEST_URI'], '/wp-json' )
  && $_SERVER['REQUEST_METHOD'] == 'POST'
) {
  require_once('post/events.php');
  exit;
}
$found_events = MultipopEventsPlugin::search_events($_GET);
?>
<script type="application/ld+json">
{
  "@context": "https://schema.org",
  "@graph": [<?=implode(',', array_map([MultipopEventsPlugin::class, 'event2ld_json'], $found_events['results']))?>]
}
</script>
<script id="search-options" type="application/json">
<?=json_encode($found_events['options'], JSON_UNESCAPED_SLASHES)?>
</script>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-select.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/quasar.prod.css">
<?=html_dump($_GET)?>
<div id="app" style="max-width: unset">
  <mpop-select
    multiple
    fuse-search
    :minLen="2"
    v-model="eventSearch.zones"
    :options="zoneSearch.events"
    label="untouched_label"
    @option:selected="zones => {
      const oldLen = zones.length;
      reduceZones(zones, eventSearch);
      //if (oldLen == zones.length) triggerSearchUsers();
    }"
    @option:deselected="triggerSearchUsers"
    @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'searchZones', 'events', eventSearch)"
  >
  </mpop-select>
  <mpop-map
    ref="mapEl"
    :events="testEvents"
    @vue:mounted="mapMounted"
    style="min-height: 550px; margin: 10px 50px;">
  </mpop-map>
</div>
<?php wp_nonce_field( 'mpop-events-page', 'mpop-events-page-nonce' ); ?>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script src="<?=plugins_url()?>/multipop/js/quasar.umd.prod.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/events.js"></script>
<?php