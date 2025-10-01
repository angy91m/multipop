<template>
  <VSelect
    v-model="model"
    ref="element"
    v-on="$attrs"
    @open="onOpen"
    @close="open = false"
    :filter="filter"
  >
    <template v-if="!$slots.search" #search="{attributes, events}">
      <input
        class="vs__search"
        :style="'display: ' + (open ? 'unset' : 'none')"
        v-bind="attributes"
        v-on="events"
      />
    </template>
    <template v-if="!$slots['no-options']" #no-options="{search}">
      <template v-if="search.trim().length >= props.minChars">
        Nessun risultato per "{{search}}"
      </template>
      <template v-else>
        Inserisci almeno {{ props.minChars }} caratteri
      </template>
    </template>
    <template v-for="(_, name) in $slots" v-slot:[name]="slotData"><slot :name="name" v-bind="slotData" /></template>
  </VSelect>
</template>
<script setup>
import VSelect from '/wp-content/plugins/multipop/js/vue-select.js';
import {ref, defineModel, defineProps, computed, defineExpose, defineEmits} from 'vue';
import Fuse from 'fuse';
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
    type: Function
  },
  minChars: {
    type: Number,
    default: 2
  }
}),
open = ref(false),
filter = computed(() => props.filter || fuseSearch),
emit = defineEmits(['search']);
defineExpose({open});
function onOpen() {
  open.value = false;
  setTimeout(()=>element.value.$el.querySelector('input.vs__search').select(), 300);
}
</script>