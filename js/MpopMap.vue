<template>
  <div :id="elId" style="min-height: 450px;"></div>
</template>
<style>
@import '/wp-content/plugins/multipop/css/leaflet.css';
.leaflet-control-attribution > :first-child, .leaflet-control-attribution > :nth-child(2) {
  display: none;
}
</style>
<script setup>
import { ref, onMounted } from 'vue';
import L from '/wp-content/plugins/multipop/js/leaflet.js';
const makeId = (length = 5) => {
  let result = '';
  const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
  for ( let i = 0; i < length; i++ ) {
    result += characters.charAt(Math.floor(Math.random() * characters.length));
  }
  return result;
},
elId = ref('mpop-map-' + makeId('5')),
map = ref(null);
onMounted(() => {
  map.value = L.map(elId.value).setView([41.9028, 12.4964], 6);
  L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '&copy; OpenStreetMap contributors'
  }).addTo(map.value);
});
</script>

