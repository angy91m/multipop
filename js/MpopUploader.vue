<template>
    <div>
        <input type="file" ref="fileInput" style="display: none" readonly @change="handleFileChange"/>
        <button v-bind="$attrs" @click="fileUploadBegin"><slot>Upload</slot></button>
    </div>
</template>
<script setup>
    import { ref } from 'vue';
    const model = defineModel({default: [], type: Array}),
    props = defineProps({
        acceptedMime: {
            default: true
        }
    }),
    fileInput = ref(null);
    
    function fileUploadBegin() {
        fileInput.value.click();
    }
    function handleFileChange() {
        console.log(fileInput.value);
    }
    
</script>