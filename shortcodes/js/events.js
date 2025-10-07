import '/wp-content/plugins/multipop/js/vue3-sfc-loader.js';
import Fuse from '/wp-content/plugins/multipop/js/fuse.js';
const { createApp, defineAsyncComponent, ref, reactive, onBeforeMount, useTemplateRef, onUnmounted, watch } = Vue,
{ loadModule } = window['vue3-sfc-loader'],
loadVueModule = (...modules) => {
  const loaded = [];
  modules.forEach(module => loaded.push(loadModule('/wp-content/plugins/multipop/js/'+ (typeof module == 'string' ? module : module.path), {
    moduleCache: { vue: Vue, ...(typeof module.modules == 'undefined' ? {} : module.modules) },
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
[mpopMap, mpopSelect] = loadVueModule('MpopMap.vue', {path: 'MpopSelect.vue', modules: {fuse: Fuse}}),
eventsPageNonce = document.getElementById('mpop-events-page-nonce').value;
let triggerSearchTimeout, searchEventsTimeout;

createApp({
  components: {
    'mpop-map': defineAsyncComponent(() => mpopMap),
    'mpop-select': defineAsyncComponent(() => mpopSelect)
  },
  setup() {
    const mapEl = useTemplateRef('mapEl'),
    eventTab = ref('list'),
    events = reactive([]),
    eventSearch = reactive(JSON.parse(document.getElementById('search-options').innerText)),
    zoneSearch = reactive({
      events: JSON.parse(JSON.stringify(eventSearch.zones))
    });
    function reduceZones(zones, target, zonesKey = 'zones') {
      if (!zones.length) return;
      const added = zones[zones.length - 1];
      if (added.type == 'nazione') {
        if (added.code == 'ita') {
          target[zonesKey] = target[zonesKey].filter(z => z.type == 'nazione');
        } else if (added.code == 'ext') {
          target[zonesKey] = target[zonesKey].filter(z => z.type != 'nazione' || ['ita','ext'].includes(z.code));
        } else {
          if (target[zonesKey].find(z => z.type == 'nazione' && z.code == 'ext')) {
            target[zonesKey].pop();
          }
        }
        return;
      }
      if (target[zonesKey].find(z => z.type == 'nazione' && z.code == 'ita')) {
        target[zonesKey].pop();
        return;
      }
      if (added.type == 'comune') {
        if (target[zonesKey].find(z => (z.type == 'provincia' && z.codice == added.provincia.codice) || (z.type == 'regione' && z.nome == added.provincia.regione) ) ) {
          target[zonesKey].pop();
        }
      }
      if (added.type == 'provincia') {
        if (target[zonesKey].find(z => (z.type == 'regione' && z.nome == added.regione) ) ) {
          target[zonesKey].pop();
        } else {
          target[zonesKey] = target[zonesKey].filter(z => z.type != 'comune' || z.provincia.codice != added.codice);
        }
      }
      if (added.type == 'regione') {
        target[zonesKey] = target[zonesKey].filter(z => z.type == 'regione' || (z.type == 'provincia' && z.regione != added.nome) || (z.type == 'comune' && z.provincia.regione != added.nome));
      }
    }
    reduceZones(JSON.parse(JSON.stringify(eventSearch.zones)), eventSearch);
    function triggerSearch(txt, loading, callable, ...args) {
      clearTimeout(triggerSearchTimeout);
      loading(true);
      const func = eval(callable);
      triggerSearchTimeout = setTimeout( () => func(txt, ...args).then(() => loading(false)), 500);
    }
    async function searchZones(txt, ctx, target, zonesKey = 'zones') {
      if (zoneSearch[ctx]) {
        const res = await serverReq({
          action: 'search_zones',
          search: txt.trim()
        });
        if (res.ok) {
          const zones = await res.json();
          if (zones.data) {
            zoneSearch[ctx].length = 0;
            zoneSearch[ctx].push(...target[zonesKey]);
            zoneSearch[ctx].push(...zones.data.filter(z => !zoneSearch[ctx].find(zz => z.type == zz.type && (z.type == 'regione' ? z.nome == zz.nome : ( z.type == 'nazione' ? z.code == zz.code : z.codice == zz.codice)))));
          } else {
            console.error('Unknown error');
          }
        } else {
          console.error('Unknown error');
        }
      }
    }
    function onDateInput(value, old) {
      if (!value.min) value.min = old.min;
      if (!value.max) value.max = old.max;
    }
    watch(eventSearch, onDateInput);
    function serverReq(obj) {
      return fetch(location.origin + location.pathname, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          ...obj,
          'mpop-events-page-nonce': eventsPageNonce
        })
      });
    }
    function setUrlOptions(replace = false) {
      const searchParams = new URLSearchParams();
      for(const k in eventSearch) {
        if (k == 'zones') {
          Array.from(new Set(eventSearch.zones.map(z => z.type == 'regione' ? 'reg_' + z.nome : (z.type == 'provincia' ? z.sigla : z.codiceCatastale)))).forEach(el => searchParams.append(`${k}[]`, el));
        } else if (k == 'sortby') {
          for (const kk in eventSearch[k]) searchParams.append(`${k}[${kk}]`, eventSearch[k][kk] ? 1 : 0);
        } else {
          searchParams.append(k, eventSearch[k]);
        }
      }
      history[replace ? 'replaceState' : 'pushState'](JSON.parse(JSON.stringify(eventSearch)), '', location.origin + location.pathname + '?' + searchParams.toString());
    }
    async function searchEvents(back = false, init = false) {
      const res = await serverReq({
        action: 'search_events',
        ...eventSearch,
        zones: Array.from(new Set(eventSearch.zones.map(z => z.type == 'regione' ? 'reg_' + z.nome : (z.type == 'provincia' ? z.sigla : z.codiceCatastale)))),
        pag: true
      });
      if (res.ok) {
        const {data} = await res.json();
        Object.assign(eventSearch, data.options);
        events.length = 0;
        events.push(...data.results);
        if (!back) {
          setUrlOptions(init);
        }
      } else {
        try {
          console.error(await res.json());
        } catch {
          console.error(await res.text());
        }
      }
    }
    function triggerSearchEvents() {
      clearTimeout(searchEventsTimeout);
      searchEventsTimeout = setTimeout(searchEvents, 500);
    }
    function dateString(d = new Date()) {
      return d.getFullYear() + '-' + ('0'+(d.getMonth()+1)).slice(-2) + '-' + ('0'+d.getDate()).slice(-2);
    } 
    function onPopState(e) {
      if (typeof e.state == 'object') {
        Object.assign(eventSearch, e.state);
        searchEvents(false);
      }
    }
    onBeforeMount(()=>{
      searchEvents(false, true);
      addEventListener('popstate', onPopState);
    });
    onUnmounted(()=>{
      removeEventListener('popstate', onPopState);
    });
    return {
      events,
      eventSearch,
      zoneSearch,
      reduceZones,
      triggerSearch,
      triggerSearchEvents,
      searchEvents,
      eventTab,
      mapEl,
      dateString,
      onDateInput
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