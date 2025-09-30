<template>
    <div :style="prova">MAPPA</div>
</template>
<script setup>
import { reactive } from 'vue';
const prova = reactive({
    color: 'red'
});
</script>

