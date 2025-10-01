<template>
  <VSelect
    v-model="model"
    ref="element"
    v-on="$attrs"
  >
    <template v-for="(_, name) in $slots" v-slot:[name]="slotData"><slot :name="name" v-bind="slotData" /></template>
  </VSelect>
</template>
<script setup>
import VSelect from '/wp-content/plugins/multipop/js/vue-select.js';
import {ref, onMounted, defineModel, useAttrs} from 'vue';
const element = ref('element'),
model = defineModel(),
attrs = useAttrs();
console.log(attrs);
onMounted(() => {
  console.log(element.value);
});
</script>