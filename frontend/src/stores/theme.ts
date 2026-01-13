import { defineStore } from "pinia";
import { ref, watch } from "vue";

export const useThemeStore = defineStore("theme", () => {
  // --- Dark Mode ---
  const isDark = ref(localStorage.getItem("theme") === "dark");

  function toggleTheme() {
    isDark.value = !isDark.value;
  }

  // Apply theme to DOM
  watch(
    isDark,
    (val) => {
      const root = document.documentElement;
      if (val) {
        root.classList.add("dark");
        localStorage.setItem("theme", "dark");
      } else {
        root.classList.remove("dark");
        localStorage.setItem("theme", "light");
      }
    },
    { immediate: true }
  );

  // --- Sidebar ---
  const isSidebarCollapsed = ref(
    localStorage.getItem("sidebar_collapsed") === "true"
  );

  function toggleSidebar() {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
    localStorage.setItem("sidebar_collapsed", String(isSidebarCollapsed.value));
  }

  return {
    isDark,
    toggleTheme,
    isSidebarCollapsed,
    toggleSidebar,
  };
});
