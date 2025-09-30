import '/wp-content/plugins/multipop/js/vue3-sfc-loader.js';
const { createApp, defineAsyncComponent, reactive, onMounted } = Vue,
{ loadModule } = window['vue3-sfc-loader'],
loadVueModule = (...modules) => {
  const loaded = [];
  modules.forEach(path => loaded.push(loadModule('/wp-content/plugins/multipop/js/'+ path, {
    moduleCache: { vue: Vue },
    async getFile(url) {
      const response = await fetch(url);
      if ( !response.ok ){
        console.error({message:'Import failed ' + url, response})
        throw new Error('Import failed ' + url)
      }
      return { getContentData: asBinary => asBinary ? response.arrayBuffer() : response.text()};
    },
    addStyle(textContent) {
      const style = Object.assign(document.createElement('style'), { textContent }),
      ref = document.head.getElementsByTagName('style')[0] || null;
      document.head.insertBefore(style, ref);
    }
  })));
  return loaded;
},
[mpopMap] = loadVueModule('MpopMap.vue');

createApp({
  components: {
    'mpop-map': defineAsyncComponent(() => mpopMap)
  },
  setup() {
    const testEvents = reactive([{
      title: 'Prova',
      location: 'Via Laurentina, 3 Roma',
      lat: 41.8503514,
      lng: 12.4777725
    }]);
    onMounted(()=>{
      setTimeout(() => testEvents.length = 0, 10000);
    });
    return {
      testEvents
    };
  }
})
.use(Quasar, {
  config: {
    nofity: {
      type: 'info'
    }
  }
})
.mount('#app');