<template>
    <div :style="prova">MAPPA</div>
</template>
<script setup>
import { reactive } from 'vue';
import L from '/wp-content/plugins/multipop/js/leaflet.js';
console.log(L);
const prova = reactive({
    color: 'red'
});
</script>

