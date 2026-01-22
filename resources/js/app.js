import './bootstrap';
import { createApp } from 'vue'
import router from "../vue/router";
import VueTheMask from "vue-the-mask";
import App from "../vue/App.vue";

createApp(App)
    .use(router)
    .use(VueTheMask)
    .mount("#app")
