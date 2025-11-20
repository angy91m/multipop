<template>
  <canvas ref="canvas"></canvas>
</template>
<script setup>
import {useTemplateRef, onMounted, onBeforeUnmount, defineExpose, defineProps, ref} from 'vue';
import SignaturePad from 'signature_pad';
const initiated = ref(false),
props = defineProps({
  width: {
    type: String,
    default: '600px'
  },
  height: {
    type: String,
    default: '200px'
  },
  borderWidth: {
    type: String,
    default: '1px'
  },
  borderColor: {
    type: String,
    default: '#bbb'
  },
  borderStyle: {
    type: String,
    default: 'solid'
  }
}),
canvasRef = useTemplateRef('canvas');
let sigPad;
function resizeCanvas() {
  const canvas = canvasRef.value,
  ratio =  Math.max(window.devicePixelRatio || 1, 1);
  const filled = !sigPad.isEmpty();
  let data;
  if (filled) data = sigPad.toData();
  canvas.width = canvas.offsetWidth * ratio;
  canvas.height = canvas.offsetHeight * ratio;
  canvas.getContext("2d").scale(ratio, ratio);
  sigPad.clear();
  if (filled) sigPad.fromData(data);
}
defineExpose({
  canvas: canvasRef,
  signaturePad: sigPad,
  initiated
});
onMounted(()=>{
  const {style} = canvasRef.value;
  style.width = props.width;
  style.height = props.height;
  style['border-width'] = props.borderWidth;
  style['border-color'] = props.borderColor;
  style['border-style'] = props.borderStyle;
  sigPad = new SignaturePad(canvasRef.value);
  const origClear = sigPad.clear;
  sigPad.clear = function(...args) {
    initiated.value = false;
    return origClear.call(this, ...args);
  };
  sigPad.addEventListener('beginStroke', ()=>initiated.value=true);
  addEventListener('resize', resizeCanvas);
  resizeCanvas();
});
onBeforeUnmount(()=>{
  removeEventListener('resize', resizeCanvas);
  sigPad.off();
});
</script>