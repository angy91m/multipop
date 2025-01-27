<template>
    <div>
        <input type="file" ref="fileInput" style="display: none" readonly @change="handleFileChange"/>
        <button v-bind="$attrs" @click="fileUploadBegin"><slot>Upload</slot></button>
    </div>
</template>
<script setup>
    import { ref, nextTick } from 'vue';
    const model = defineModel({default: [], type: Array}),
    props = defineProps({
        acceptedMime: {
            default: true
        },
        binary: {
            default: false
        }
    }),
    emit = defineEmits(['change']),
    fileInput = ref(null);

    function readFile(f) {
        return new Promise( (resolve, reject) => {
            const fr = new FileReader();
            fr.onload = () => {
                resolve(props.binary ? new Uint8Array(fr.result) : fr.result);
            }
            fr.onerror = e => reject(e);
            props.binary ? fr.readAsArrayBuffer(f) : fr.readAsDataURL(f);
        });
    }
    function fileUploadBegin() {
        fileInput.value.click();
    }
    async function handleFileChange() {
        console.log('ciao0');
        if (fileInput.value.files.length) {
            console.log('ciao1');
            try {
                const f = fileInput.value.files[0];
                let acceptedMime = typeof props.acceptedMime == 'string' ? [props.acceptedMime] : props.acceptedMime;
                if (
                    (Array.isArray(acceptedMime) && acceptedMime.includes(f.type || 'application/octet-stream'))
                    || (!Array.isArray(acceptedMime) && acceptedMime)
                ) {
                    const fileContent = await readFile(f),
                    fileRead = {meta: f, content: fileContent};
                    model.value.push(fileRead);
                    emit('change', fileRead);
                }
            } finally {
                nextTick(() => {fileInput.value.value = ''; console.log(fileInput.value)});
            }
        }
    }
    
</script>