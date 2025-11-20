<template>
  <div>
    <canvas ref="canvas"></canvas>
  </div>
</template>
<script setup>
import {useTemplateRef, onMounted, beforeUnmount, defineExpose} from 'vue';
import SignaturePad from 'signature_pad';
const canvasRef = useTemplateRef('canvas');
let sigPad;
function resizeCanvas() {
  const canvas = canvasRef.value,
  ratio =  Math.max(devicePixelRatio || 1, 1);
  canvas.width = canvas.offsetWidth * ratio;
  canvas.height = canvas.offsetHeight * ratio;
  canvas.getContext("2d").scale(ratio, ratio);
  sigPad.clear();
}
defineExpose({
  canvas: canvasRef,
  sigPad
});
onMounted(()=>{
  sigPad = new SignaturePad(canvasRef.value);
  addEventListener('resize', resizeCanvas);
});
beforeUnmount(()=>{
  removeEventListener('resize', resizeCanvas);
});
</script>