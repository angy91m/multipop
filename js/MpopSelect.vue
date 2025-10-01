<template>
  <VSelect
    v-model="model"
    ref="element"
    v-on="$attrs"
    @open="onOpen"
    @close="open = false"
    :filter="filter"
  >
    <template v-for="(_, name) in $slots" v-slot:[name]="slotData"><slot :name="name" v-bind="slotData" /></template>
  </VSelect>
</template>
<script setup>
import VSelect from '/wp-content/plugins/multipop/js/vue-select.js';
import Fuse from '/wp-content/plugins/multipop/js/fuse.js';
import {ref, onMounted, defineModel, defineProps, computed, useSlots} from 'vue';
function fuseSearch(options, search) {
  const fuse = new Fuse(options, {
    keys: ['label'],
    shouldSort: true
  });
  return search.trim().length ? fuse.search(search).map(({item}) => item) : fuse.list;
}
const element = ref('element'),
model = defineModel(),
props = defineProps({
  filter: {
    type: Function,
    required: false
  }
}),
open = ref(false),
filter = computed(() => props.filter || fuseSearch);

const slots = useSlots();
console.log(slots);
function onOpen() {
  open.value = false;
  setTimeout(()=>element.value.$el.querySelector('input.vs__search').select(), 300);
}

onMounted(() => {
  console.log(element.value);
});
</script>