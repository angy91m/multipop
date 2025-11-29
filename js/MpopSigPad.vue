<template>
  <canvas ref="canvas"></canvas>
</template>
<script setup>
import {useTemplateRef, onMounted, onBeforeUnmount, defineExpose, defineProps, ref, nextTick} from 'vue';
import SignaturePad from 'signature_pad';
const initiated = ref(false),
signaturePad = ref(null),
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
  },
  fromData: {
    default: undefined
  },
  fromDataUrl: {
    default: undefined
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
function initSigPad() {
  sigPad = new SignaturePad(canvasRef.value);
  sigPad.edits = [];
  sigPad.clearAll = function() {
    initiated.value = false;
    this.edits.length = 0
    return this.clear();
  };
  sigPad.addEventListener('beginStroke', ()=>{
    initiated.value = true;
    sigPad.edits.push(sigPad.toDataURL());
    console.log('fatto');
  });
  sigPad.undo = function() {
    this.clear();
    let l = this.edits.length;
    const edit = this.edits.splice(--l,1)[0];
    console.log(edit);
    if (l) this.fromDataURL(edit);
    console.log(initiated.value);
    if (!l) initiated.value = false;
    console.log(initiated.value);
  };
  return sigPad;
}
defineExpose({
  canvas: canvasRef,
  signaturePad,
  initiated
});
onMounted(()=>{
  const {style} = canvasRef.value;
  style.width = props.width;
  style.height = props.height;
  style['border-width'] = props.borderWidth;
  style['border-color'] = props.borderColor;
  style['border-style'] = props.borderStyle;
  signaturePad.value = initSigPad();
  addEventListener('resize', resizeCanvas);
  resizeCanvas();
  if (props.fromData) {
    sigPad.fromData(props.fromData);
    initiated.value = true;
  }
  if (props.fromDataUrl) {
    sigPad.fromDataURL(props.fromDataUrl);
    initiated.value = true;
  }
});
onBeforeUnmount(()=>{
  removeEventListener('resize', resizeCanvas);
  sigPad.off();
});
</script>