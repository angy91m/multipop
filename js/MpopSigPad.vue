<template>
  <div>
    <canvas ref="canvas">

    </canvas>
  </div>
</template>
<script setup>
import {useTemplateRef, onMounted} from 'vue';
import SignaturePad from 'signature_pad';
const canvas = useTemplateRef('canvas');
onMounted(()=>{
  console.log(SignaturePad);
  console.log(canvas);
});
</script>