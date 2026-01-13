import { createApp } from "vue";
import { createPinia } from "pinia";
import router from "./router";
import "./style.css";
import App from "./App.vue";

import { autoAnimatePlugin } from "@formkit/auto-animate/vue";
// Axios config moved to @/services/api

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(autoAnimatePlugin);
app.mount("#app");
