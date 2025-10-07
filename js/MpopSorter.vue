<template>
    <div class="row">
        <q-select v-model="activeValue" :options="props.options" :label="props.label" v-bind="$attrs" />
    </div>
</template>
<script setup>
import {defineProps, defineModel, computed} from 'vue';
defineOptions({
  inheritAttrs: false
});
const props = defineProps({
    options: {
        type: Array,
        default: []
    },
    label: {
        type: String,
        default: 'Ordina per: '
    }
}),
modelValue = defineModel({default: []}),
activeValue = computed({
    get: () => Object.keys(modelValue.value)[0],
    set: v => {
        console.log(v);
    }
});
</script>