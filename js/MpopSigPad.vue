<template>
  <div>
    <canvas ref="canvas">

    </canvas>
  </div>
</template>
<script setup>
import {useTemplateRefs, onMounted} from 'vue';
import SignaturePad from 'signature_pad';
const canvas = useTemplateRefs('canvas');
onMounted(()=>{
  console.log(SignaturePad);
  console.log(canvas);
});
</script>