<template>
    <div :id="buttonId"></div>
</template>
<script setup>
    import { computed, onMounted } from 'vue';
    const props = defineProps({
        subscription: {
            required: true,
            type: Object
        },
        options: {
            required: true,
            type: Object
        }
    }),
    buttonId = computed(() => 'mpop-ppbtn-' + props.subscription.id );
    onMounted(() => paypal.Buttons(props.options).render('#'+buttonId.value));
</script>