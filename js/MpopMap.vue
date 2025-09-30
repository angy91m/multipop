<template>
    <div :id="elId">MAPPA</div>
</template>
<style>
@import '/wp-content/plugins/multipop/css/leaflet.css';
</style>
<script setup>
import { reactive, onBeforeMount } from 'vue';
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
onBeforeMount(() => {
   map.value = L.map(elId.value).setView([41.9028, 12.4964], 6);
});
</script>

