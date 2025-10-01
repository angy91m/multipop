<template>
    <VSelect
        ref="element"
    >
    </VSelect>
</template>
<script setup>
    import VSelect from '/wp-content/plugins/multipop/js/vue-select.js';
    import {ref, onBeforeMount} from 'vue';
    const element = ref('element')
    onBeforeMount(() => {
        console.log(element.value);
    });
</script>