<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { ref, watch } from "vue";

const { locale, availableLocales } = useI18n();

const showDropdown = ref(false);

const languages = [
  { code: "en", name: "English", flag: "🇬🇧" },
  { code: "kh", name: "ខ្មែរ", flag: "🇰🇭" },
];

const currentLanguage = ref(
  languages.find((lang) => lang.code === locale.value) || languages[0]
);

function changeLanguage(langCode: string) {
  locale.value = langCode;
  currentLanguage.value =
    languages.find((lang) => lang.code === langCode) || languages[0];
  localStorage.setItem("locale", langCode);
  showDropdown.value = false;
}

// Watch for external locale changes
watch(locale, (newLocale) => {
  currentLanguage.value =
    languages.find((lang) => lang.code === newLocale) || languages[0];
});
</script>

<template>
  <div class="language-selector relative inline-block">
    <button
      @click="showDropdown = !showDropdown"
      class="flex items-center gap-2 px-3 py-2 rounded-lg border border-gray-200 hover:bg-gray-50 dark:border-gray-700 dark:hover:bg-gray-800 transition-colors"
      type="button"
    >
      <span class="text-lg">{{ currentLanguage.flag }}</span>
      <span class="text-sm font-medium text-gray-700 dark:text-gray-300">
        {{ currentLanguage.code.toUpperCase() }}
      </span>
      <svg
        class="w-4 h-4 text-gray-500 transition-transform"
        :class="{ 'rotate-180': showDropdown }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </button>

    <!-- Dropdown -->
    <transition
      enter-active-class="transition ease-out duration-100"
      enter-from-class="transform opacity-0 scale-95"
      enter-to-class="transform opacity-100 scale-100"
      leave-active-class="transition ease-in duration-75"
      leave-from-class="transform opacity-100 scale-100"
      leave-to-class="transform opacity-0 scale-95"
    >
      <div
        v-if="showDropdown"
        class="absolute right-0 mt-2 w-48 rounded-lg shadow-lg bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 z-50"
      >
        <div class="py-1">
          <button
            v-for="lang in languages"
            :key="lang.code"
            @click="changeLanguage(lang.code)"
            class="w-full flex items-center gap-3 px-4 py-2 text-sm hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors"
            :class="{
              'bg-primary-50 dark:bg-primary-900/20 text-primary-600 dark:text-primary-400':
                lang.code === locale,
            }"
          >
            <span class="text-lg">{{ lang.flag }}</span>
            <span class="font-medium">{{ lang.name }}</span>
            <svg
              v-if="lang.code === locale"
              class="ml-auto w-5 h-5 text-primary-600 dark:text-primary-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                fill-rule="evenodd"
                d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                clip-rule="evenodd"
              />
            </svg>
          </button>
        </div>
      </div>
    </transition>
  </div>
</template>

<style scoped>
/* Optional: Add custom styles if needed */
</style>
