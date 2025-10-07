<template>
    <div class="row">
        <div class="column col">
            <q-select v-model="activeKey" :options="props.options" v-bind="$attrs" />
        </div>
        <div class="column col">
            <q-btn :icon="activeOrder ? 'arrow_drop_up' : 'arrow_drop_down'" @click="invertOrder"/>
        </div>
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
activeOrder = computed(()=>Object.values(modelValue.value)[0]);
function invertOrder() {
    modelValue.value[Object.keys(modelValue.value)[0]] = !Object.values(modelValue.value)[0];
}
</script>