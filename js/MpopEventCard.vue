<template>
  <q-card ref="cardEl" class="event-card" flat bordered style="margin-bottom: 10px;">
    <q-card-section horizontal>
      <q-card-section class="q-pt-xs" style="padding: 0 10px">
        <div
          class="text-overline"
          v-html="showEventDate(props.event) + '<br>' + locationString(event)"
          style="text-wrap: nowrap; text-transform: uppercase;"
        ></div>
      </q-card-section>
    </q-card-section>
    <q-card-section horizontal class="event-card-description">
      <q-card-section class="q-pt-xs col-grow">
        <div class="text-h5 q-mt-sm q-mb-xs">{{props.event.title}}</div>
        <div class="text-caption text-grey">{{props.event.excerpt}}</div>
      </q-card-section>
      <q-card-section v-if="props.event.thumbnail" class="event-card-img col-5 flex flex-center">
        <q-img
          class="rounded-borders"
          :src="props.event.thumbnail"
        />
      </q-card-section>
    </q-card-section>
  </q-card>
</template>
<script setup>
import {defineProps, defineEmits, useTemplateRef, onMounted} from 'vue';
const dayNames = [
  'Domenica',
  'Lunedì',
  'Martedì',
  'Mercoledì',
  'Giovedì',
  'Venerdì',
  'Sabato'
],
monthNames = [
  'Gennaio',
  'Febbraio',
  'Marzo',
  'Aprile',
  'Maggio',
  'Giugno',
  'Luglio',
  'Agosto',
  'Settembre',
  'Ottobre',
  'Novembre',
  'Dicembre'
],
props = defineProps({
  event: {
    type: Object,
    required: true
  }
}),
cardEl = useTemplateRef('cardEl'),
emit = defineEmits(['clicked']);
function humanDate(d = new Date()) {
  return dayNames[d.getDay()].slice(0,3) + ' ' + d.getDate() + ' ' + monthNames[d.getMonth()].slice(0,3) + ' ' + d.getFullYear();
}
function humanTime(d = new Date()) {
  return ('0' + d.getHours()).slice(-2) + ':' + ('0' + d.getMinutes()).slice(-2);
}
function showEventDate(event) {
  const start = new Date(event.start),
  end = new Date(event.end),
  startDate = humanDate(start),
  endDate = humanDate(end),
  startTime = humanTime(start),
  endTime = humanTime(end),
  res = startDate + ' ' + startTime + (startDate == endDate ? (startTime == endTime ? '' : ' - ' + endTime) : '<br><i class="q-icon mdi mdi-source-commit-end" aria-hidden="true" role="presentation" style="font-size: medium; margin-right: 5px"></i>' + endDate + ' ' + endTime );
  return (res.includes('<br>') ? '<i class="q-icon mdi mdi-source-commit-start" aria-hidden="true" role="presentation" style="top:-3px; font-size: medium; margin-right: 5px"></i>' : '') + res;
}
function stripTags(html) {
  if (!html) return '';
  const div = document.createElement('div');
  div.innerHTML = html;
  return div.textContent || div.innerText || '';
}
function locationString(event) {
  return (event.location ? '<i class="q-icon material-icons notranslate" aria-hidden="true" role="presentation" style="font-size: medium; margin-right: 5px; top: -1px">location_on</i>' : '') + [
    stripTags(event.location_name),
    stripTags(event.location)
  ].filter(v => v).join(' - ');
}
onMounted(()=> {
  cardEl.value.$el.addEventListener('mouseup', e => {
    if (!e.button) emit('clicked');
  });
});
</script>