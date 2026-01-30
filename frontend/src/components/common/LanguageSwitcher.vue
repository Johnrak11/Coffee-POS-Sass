<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { computed } from "vue";

const { locale } = useI18n();

const currentFlag = computed(() => {
  return locale.value === "en" ? "🇬🇧" : "🇰🇭";
});

function toggleLanguage() {
  // Toggle between en and kh
  locale.value = locale.value === "en" ? "kh" : "en";
  localStorage.setItem("locale", locale.value);
}
</script>

<template>
  <button
    @click="toggleLanguage"
    class="relative bg-white text-gray-700 w-10 h-10 rounded-full shadow-sm border border-gray-100 flex items-center justify-center hover:bg-gray-50 active:scale-95 transition-all"
    :title="locale === 'en' ? 'Switch to ភាសាខ្មែរ' : 'Switch to English'"
  >
    <span class="text-2xl leading-none">{{ currentFlag }}</span>
  </button>
</template>
