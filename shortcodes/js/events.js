const { createApp } = Vue;

createApp({
    setup() {
        return {};
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