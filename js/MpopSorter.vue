<template>
    <div class="row">
        <q-select v-model="activeKey" :options="props.options" v-bind="$attrs" />
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
    }
}),
modelValue = defineModel({default: []}),
activeKey = computed({
    get: () => Object.keys(modelValue.value)[0],
    set: v => {
        console.log(v);
    }
}),
activeOrder = computed({
    get: () => Object.values(modelValue.value)[0],
    set: v => {
        console.log(v);
    }
});
</script>