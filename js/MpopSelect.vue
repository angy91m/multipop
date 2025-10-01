<template>
    <VSelect
        ref="element"
    >
    </VSelect>
</template>
<script setup>
    import VSelect from '/wp-content/plugins/multipop/js/vue-select.js';
    import {ref, onMounted} from 'vue';
    const element = ref('element')
    onMounted(() => {
        console.log(element.value);
    });
</script>