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
import { ref, onMounted, defineProps, watch } from 'vue';
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
    default: []
  }
}),
elId = ref('mpop-map-' + makeId()),
addEventsToMap = () => props.events.forEach(ev => {
  eventsLayer.clearLayers();
  if (ev.location && typeof ev.lat != 'undefined' ) {
    const marker = L.marker([ev.lat, ev.lng]);
    marker.bindPopup(`<strong>${ev.title}</strong><br>${ev.location}`);
    eventsLayer.addLayer(marker);
  }
});
watch(props.events, (newValue) => {
  console.log('EDITED');
  console.log(newValue);
  console.log(props.events);
  console.log(eventsLayer);
  if (mounted) addEventsToMap();
});
onMounted(() => {
  console.log('MOUNTED');
  mounted = true;
  L.Marker.prototype.options.icon.options.imagePath = 'https://unpkg.com/leaflet@1.9.4/dist/images/';
  map = L.map(elId.value).setView([41.9028, 12.4964], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map);
  eventsLayer = L.layerGroup().addTo(map);
  addEventsToMap();
});
</script>

