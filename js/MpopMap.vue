<template>
  <div :id="elId"></div>
</template>
<style>
@import 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
.leaflet-control-attribution > :first-child, .leaflet-control-attribution > :nth-child(2) {
  display: none;
}
.event-marker-popup {
  cursor: default;
}
</style>
<script setup>
import { ref, onMounted, defineProps, watch, defineExpose, defineEmits } from 'vue';
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
emit = defineEmits(['eventClick']),
mapRef = ref(null),
elId = ref('mpop-map-' + makeId()),
listenerClears = [];
function humanTime(d = new Date()) {
  return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
}
function showEventDate(event) {
  const start = new Date(event.start),
  end = new Date(event.end),
  startDate = start.toLocaleDateString(),
  endDate = end.toLocaleDateString(),
  startTime = humanTime(start),
  endTime = humanTime(end);
  return startDate + ' ' + startTime + (startDate == endDate ? (startTime == endTime ? '' : ' - ' + endTime) : '<br>' + endDate + ' ' + endTime );
}
function addEventsToMap () {
  listenerClears.forEach(cb => cb());
  eventsLayer.clearLayers();
  props.events.forEach(ev => {
    if (ev.location && typeof ev.lat != 'undefined' ) {
      const content = L.DomUtil.create('span', 'event-marker-popup'),
      marker = L.marker([ev.lat, ev.lng]);
      let html =`${showEventDate(ev)}<br><strong>${ev.title}</strong>`;
      if (ev.location_name) html += `<br>${ev.location_name}`;
      if (ev.location) html += `<br>${ev.location}`;
      content.innerHTML = html;
      const onClick = () => emit('eventClick', ev);
      listenerClears.push(()=>content.removeEventListener('click', onClick));
      content.addEventListener('click', onClick);
      marker.bindPopup(content);
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

