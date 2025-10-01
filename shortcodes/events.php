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
?>
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/vue-select.css">
<link rel="stylesheet" href="<?=plugins_url()?>/multipop/css/quasar.prod.css">
<div id="app" style="max-width: unset">
  <mpop-select @close="()=>console.log('ciao')">
    <template #search="{ attributes, events }">
      <input
        class="vs__search"
        :style="'display: ' + (eventSearchZoneOpen ? 'unset' : 'none')"
        v-bind="attributes"
        v-on="events"
      />
    </template>
  </mpop-select>
  <v-select
    multiple
    id="eventSearchZone-select"
    v-model="eventSearch.zones"
    :options="zoneSearch.events"
    @close="eventSearchZoneOpen = false"
    @open="searchOpen('eventSearchZone')"
    :get-option-label="(option) => option.untouched_label + addSuppressToLabel(option)"
    :filter="fuseSearch"
    @option:selected="zones => {
      const oldLen = zones.length;
      reduceZones(zones, eventSearch);
      //if (oldLen == zones.length) triggerSearchUsers();
    }"
    @option:deselected="triggerSearchUsers"
    @search="(searchTxt, loading) => {
      if (searchTxt.trim().length < 2) return loading(false);
      triggerSearch(searchTxt, loading, 'searchZones', 'events', eventSearch);
    }"
    >
      <template #search="{ attributes, events }">
        <input
          class="vs__search"
          :style="'display: ' + (eventSearchZoneOpen ? 'unset' : 'none')"
          v-bind="attributes"
          v-on="events"
        />
      </template>
      <template v-slot:option="zone">
        {{zone.untouched_label + addSuppressToLabel(zone)}}
      </template>
      <template v-slot:no-options="{search}">
        <template v-if="search.trim().length > 1">
          Nessun risultato per "{{search}}"
        </template>
        <template v-else>
          Inserisci almeno 2 caratteri
        </template>
      </template>
    </v-select>
    <mpop-map style="min-height: 500px;" :events="testEvents"></mpop-map>
</div>
<?php wp_nonce_field( 'mpop-events-page', 'mpop-events-page-nonce' ); ?>
<script src="<?=plugins_url()?>/multipop/js/vue.global.min.js"></script>
<script src="<?=plugins_url()?>/multipop/js/quasar.umd.prod.js"></script>
<script type="module" src="<?=plugins_url()?>/multipop/shortcodes/js/events.js"></script>
<?php