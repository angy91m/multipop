<template>
  <canvas ref="canvas" :width="props.width" :height="props.height"></canvas>
</template>
<style scoped>
canvas {
  border: 1px #bbb solid;
}
</style>
<script setup>
import {useTemplateRef, onMounted, onBeforeUnmount, defineExpose, defineProps} from 'vue';
import SignaturePad from 'signature_pad';
const props = defineProps({
  width: {
    default: 600
  },
  height: {
    default: 200
  }
}),
canvasRef = useTemplateRef('canvas');
let sigPad;
function resizeCanvas() {
  const canvas = canvasRef.value,
  ratio =  Math.max(window.devicePixelRatio || 1, 1);
  canvas.width = parseInt(props.width) * ratio;
  canvas.height = parseInt(props.height) * canvas.offsetHeight * ratio;
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