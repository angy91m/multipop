<template>
    <div class="row">
        <div class="column col">
            <q-select v-model="activeOption" :options="props.options" v-bind="$attrs" />
        </div>
        <div class="column col">
            <q-btn :icon="activeOrder ? 'arrow_drop_up' : 'arrow_drop_down'" @click="invertOrder"/>
        </div>
    </div>
</template>
<script setup>
import {defineProps, defineModel, computed, defineEmits} from 'vue';
defineOptions({
  inheritAttrs: false
});
const props = defineProps({
    options: {
        type: Array,
        default: []
    }
}),
modelValue = defineModel({
    type: Object,
    required: true
}),
emit = defineEmits(['change']),
activeOption = computed({
    get: () => props.options.find(o => o.value == Object.keys(modelValue.value)[0]),
    set: v => {
        const obj = modelValue.value;
        delete obj[Object.keys(obj)[1]];
        modelValue.value = {
            [v.value]: true,
            ...obj
        };
        emit('change');
    }
}),
activeOrder = computed(()=>Object.values(modelValue.value)[0]);
function invertOrder() {
    modelValue.value[Object.keys(modelValue.value)[0]] = !Object.values(modelValue.value)[0];
    emit('change');
}
</script>