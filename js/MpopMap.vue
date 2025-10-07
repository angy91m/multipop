<template>
  <div :id="elId"></div>
</template>
<style>
@import 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
.leaflet-control-attribution > :first-child, .leaflet-control-attribution > :nth-child(2) {
  display: none;
}
</style>
<script setup>
import { ref, onMounted, defineProps, watch, defineExpose } from 'vue';
import L from '/wp-content/plugins/multipop/js/leaflet.js';
let map, eventsLayer, mounted;
const makeId = (length = 5) => {
  let result = '';
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  for ( let i = 0; i < length; i++ ) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return result;
},
props = defineProps({
  events: {
    type: Array,
    default: []
  },
  lat: {
    type: Number,
    default: 41.9028
  },
  lng: {
    type: Number,
    default: 12.4964
  },
  zoom: {
    type: Number,
    default: 6
  }
}),
mapRef = ref(null),
elId = ref('mpop-map-' + makeId()),
addEventsToMap = () => {
  eventsLayer.clearLayers();
  props.events.forEach(ev => {
    console.log(ev);
    if (ev.location && typeof ev.lat != 'undefined' ) {
      const marker = L.marker([ev.lat, ev.lng]);
      marker.bindPopup(`<strong>${ev.title}</strong><br>${ev.location}`);
      eventsLayer.addLayer(marker);
    }
  })
};
defineExpose({
  map: mapRef,
  L
});
watch(props.events, () => {
  if (mounted) addEventsToMap();
});
onMounted(() => {
  mounted = true;
  L.Marker.prototype.options.icon.options.imagePath = 'https://unpkg.com/leaflet@1.9.4/dist/images/';
  map = L.map(elId.value).setView([props.lat, props.lng], props.zoom);
  mapRef.value = map;
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  eventsLayer = L.layerGroup().addTo(map);
  addEventsToMap();
});
</script>

