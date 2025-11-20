<template>
  <canvas ref="canvas"></canvas>
</template>
<style scoped>
canvas {
  border: 1px #bbb solid;
}
</style>
<script setup>
import {useTemplateRef, onMounted, onBeforeUnmount, defineExpose} from 'vue';
import SignaturePad from 'signature_pad';
const canvasRef = useTemplateRef('canvas');
let sigPad;
function resizeCanvas() {
  const canvas = canvasRef.value,
  ratio =  Math.max(devicePixelRatio || 1, 1);
  canvas.width = canvas.offsetWidth * ratio;
  canvas.height = canvas.offsetHeight * ratio;
  console.log(canvas.offsetWidth * ratio);
  console.log(canvas.offsetHeight * ratio);
  canvas.getContext("2d").scale(ratio, ratio);
  sigPad.clear();
}
defineExpose({
  canvas: canvasRef,
  signaturePad: sigPad
});
onMounted(()=>{
  sigPad = new SignaturePad(canvasRef.value);
  addEventListener('resize', resizeCanvas);
  resizeCanvas();
});
onBeforeUnmount(()=>{
  removeEventListener('resize', resizeCanvas);
});
</script>