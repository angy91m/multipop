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
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/events.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/shortcodes/css/fonts.css">
<div id="app" style="max-width: unset">
  <q-layout view="hHh lpR fFf">
    <q-page-container>
      <div class="row">
        <div class="column col-grow event-search-option">
          <label>Titolo o descrizione</label><br>
          <q-input v-model="eventSearch.txt" filled @update:model-value="triggerSearchEvents"></q-input>
        </div>
        <div class="column col-grow">
          <div class="row">
            <div class="column col-grow event-search-option">
              <label>Dal</label><br>
              <q-input v-model="eventSearch.min" type="date" filled @blur="()=>searchEvents()"></q-input>
            </div>
            <div class="column col-grow event-search-option">
              <label>Al</label><br>
              <q-input v-model="eventSearch.max" type="date" filled @blur="()=>searchEvents()"></q-input>
            </div>
          </div>
        </div>
        <div class="column col-grow event-search-option" style="min-width:220px">
          <label>Luogo</label>
          <mpop-select
            class="col-grow"
            multiple
            fuse-search
            :minLen="2"
            v-model="eventSearch.zones"
            :options="zoneSearch.events"
            label="untouched_label"
            @option:selected="zones => {
              const oldLen = zones.length;
              reduceZones(zones, eventSearch);
              if (oldLen == zones.length) triggerSearchEvents();
            }"
            @option:deselected="triggerSearchEvents"
            @search="(searchTxt, loading) => triggerSearch(searchTxt, loading, 'searchZones', 'events', eventSearch)"
          >
          </mpop-select>
        </div>
      </div>
      <q-tabs
        v-model="eventTab"
        align="left"
      >
        <q-tab name="list" icon="list" label="Elenco"></q-tab> 
        <q-tab name="map" icon="map" label="Mappa"></q-tab>
      </q-tabs>
      <div class="row" v-if="eventTab == 'list'">
        <div class="col-grow">
          <div class="row justify-center" v-for="(event, k) in eventsToShow" :key="k">
            <mpop-event-card
              flat bordered
              class="event-card"
              style="margin-bottom: 10px;"
              :event="event"
              @clicked="()=>window.location.href=event.url"
            ><mpop-event-card>
          </div>
        </div>
      </div>
      <mpop-map
        v-if="eventTab == 'map'"
        ref="mapEl"
        :events="events"
        style="min-height: 550px; margin: 10px 50px;">
      </mpop-map>
    </q-page-container>
  </q-layout>
</div>
<?php wp_nonce_field( 'mpop-events-page', 'mpop-events-page-nonce' ); ?>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script src="<?=plugins_url()?>/multipop/js/quasar.umd.prod.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/events.js"></script>
<?php