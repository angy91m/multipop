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
  rect = canvas.getBoundingClientRect(),
  ratio =  Math.max(devicePixelRatio || 1, 1);

  console.log(canvas.width);
  console.log(canvas.height);
  console.log(devicePixelRatio);
  canvas.width = rect.width * ratio;
  canvas.height = rect.height * ratio;
  console.log(canvas.width);
  console.log(canvas.height);
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