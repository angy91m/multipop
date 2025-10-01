<template>
  <VSelect
    v-model="modelValue"
    ref="element"
    v-on="$attrs"
    @open="onOpen"
    @close="open = false"
    @search="onSearch"
    :filter="filter"
  >
    <template v-if="!$slots.search" #search="{attributes, events}">
      <input
        class="vs__search"
        :style="'display: ' + (open || (typeof $attrs.multiple != 'undefined' ? true : !modelValue) ? 'unset' : 'none')"
        v-bind="attributes"
        v-on="events"
      />
    </template>
    <template v-if="!$slots['no-options']" #no-options="{search}">
      <template v-if="(props.trim ? search.trim() : search).length >= props.minLen">
        Nessun risultato per "{{search}}"
      </template>
      <template v-else>
        Inserisci almeno {{ props.minLen }} caratteri
      </template>
    </template>
    <template v-for="(_, name) in $slots" v-slot:[name]="slotData"><slot :name="name" v-bind="slotData" /></template>
  </VSelect>
</template>
<script setup>
import VSelect from '/wp-content/plugins/multipop/js/vue-select.js';
import {ref, defineModel, defineProps, computed, defineExpose, defineEmits, useAttrs} from 'vue';
import Fuse from 'fuse';
function fuseSearch(options, search) {
  const fuse = new Fuse(options, {
    keys: ['label'],
    shouldSort: true
  });
  return (props.trim ? search.trim() : search).length ? fuse.search(search).map(({item}) => item) : fuse.list;
}
const element = ref('element'),
modelValue = defineModel(),
props = defineProps({
  filter: {
    type: Function,
    default: undefined
  },
  minLen: {
    type: Number,
    default: 0
  },
  trim: {
    default: true
  }
}),
attrs = useAttrs(),
open = ref(false),
filter = computed(() => props.filter || (typeof attrs['fuse-search'] != 'undefined' ?  fuseSearch : undefined)),
emit = defineEmits(['search']);
defineExpose({open});
function onOpen() {
  open.value = true;
  setTimeout(()=>element.value.$el.querySelector('input.vs__search').select(), 300);
}
function onSearch(searchTxt, loading) {
  searchTxt = props.trim ? searchTxt.trim() : searchTxt;
  if ( searchTxt.length < props.minLen) return loading(false);
  emit('search', searchTxt, loading);
}
</script>