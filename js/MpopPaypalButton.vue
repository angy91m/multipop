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
            type: Function
        }
    }),
    buttonId = computed(() => 'mpop-ppbtn-' + props.subscription.id );
    onMounted(() => paypal.Buttons(props.options(props.subscription, buttonId.value)).render('#'+buttonId.value));
</script>