import { createApp } from "vue";
import { createPinia } from "pinia";
import { autoAnimatePlugin } from "@formkit/auto-animate/vue";
import router from "./router";
import i18n from "./i18n";
import "./style.css";
import "./styles/design-tokens.css";
import "nprogress/nprogress.css";
import App from "./App.vue";

// Styles
import "nprogress/nprogress.css";
import "vue-sonner/style.css";
// API client configured in @/api

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);
app.use(i18n);
app.use(autoAnimatePlugin);
app.mount("#app");
