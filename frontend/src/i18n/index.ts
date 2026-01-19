import { createI18n } from "vue-i18n";

// Import translation files
import en from "./locales/en.json";
import kh from "./locales/kh.json";

// Type-safe messages
export type MessageSchema = typeof en;

const i18n = createI18n<[MessageSchema], "en" | "kh">({
  legacy: false, // Use Composition API mode
  locale: localStorage.getItem("locale") || "en", // Get saved locale or default to English
  fallbackLocale: "en",
  messages: {
    en,
    kh,
  },
  globalInjection: true, // Enable $t() in templates
  missingWarn: false,
  fallbackWarn: false,
});

export default i18n;
