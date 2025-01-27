<template>
    <div>
        <input type="file" ref="fileInput" style="display: none" readonly @change="handleFileChange"/>
        <button v-bind="$attrs" @click="fileUploadBegin"><slot>Upload</slot></button>
    </div>
</template>
<script setup>
    import { ref, onMounted, onBeforeUnmount } from 'vue';
    const model = defineModel({default: [], type: Array}),
    props = defineProps({
        acceptedMime: {
            default: true
        }
    }),
    fileInput = ref(null);

    function readFile(f, b64 = false) {
        return new Promise( r => {
            const fr = new FileReader();
            fr.onload = () => {
                r(b64 ? fr.result : new Uint8Array(fr.result));
            }
            b64 ? fr.readAsDataURL(f) : fr.readAsArrayBuffer(f);
        });
    }
    function fileUploadBegin() {
        fileInput.value.click();
    }
    async function handleFileChange() {
        if (fileInput.value.files.length) {
            const f = fileInput.value.files[0];
            let acceptedMime = typeof props.acceptedMime == 'string' ? [props.acceptedMime] : props.acceptedMime;
            if (
                (Array.isArray(acceptedMime) && f.type && acceptedMime.includes(f.type))
                || (!Array.isArray(acceptedMime) && acceptedMime)
            ) {
                const fileContent = await readFile(f, true);
                console.log(fileContent);
            }
        }
        console.log();
    }
    
</script>