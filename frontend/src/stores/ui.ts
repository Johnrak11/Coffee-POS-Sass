import { defineStore } from "pinia";
import { ref, watch } from "vue";
import { useI18n } from "vue-i18n";

export const useUIStore = defineStore("ui", () => {
  const { locale } = useI18n();

  // Theme management
  const isDarkMode = ref<boolean>(
    localStorage.getItem("darkMode") === "true" ||
      window.matchMedia("(prefers-color-scheme: dark)").matches,
  );

  // Language management (synced with i18n)
  const currentLocale = ref<"en" | "kh">(
    (localStorage.getItem("locale") as "en" | "kh") || "en",
  );

  // Sidebar state (for mobile responsiveness)
  const isSidebarOpen = ref(true);
  const isMobile = ref(window.innerWidth < 1024);

  // Toast/notification system
  const toasts = ref<
    Array<{
      id: number;
      type: "success" | "error" | "warning" | "info";
      message: string;
      duration?: number;
    }>
  >([]);

  let toastId = 0;

  // Actions
  function toggleTheme() {
    isDarkMode.value = !isDarkMode.value;
    localStorage.setItem("darkMode", isDarkMode.value.toString());
    updateThemeClass();
  }

  function setTheme(dark: boolean) {
    isDarkMode.value = dark;
    localStorage.setItem("darkMode", dark.toString());
    updateThemeClass();
  }

  function updateThemeClass() {
    if (isDarkMode.value) {
      document.documentElement.classList.add("dark");
    } else {
      document.documentElement.classList.remove("dark");
    }
  }

  function setLocale(newLocale: "en" | "kh") {
    currentLocale.value = newLocale;
    locale.value = newLocale;
    localStorage.setItem("locale", newLocale);
  }

  function toggleSidebar() {
    isSidebarOpen.value = !isSidebarOpen.value;
  }

  function showToast(
    type: "success" | "error" | "warning" | "info",
    message: string,
    duration = 3000,
  ) {
    const id = ++toastId;
    toasts.value.push({ id, type, message, duration });

    if (duration > 0) {
      setTimeout(() => {
        removeToast(id);
      }, duration);
    }
  }

  function removeToast(id: number) {
    const index = toasts.value.findIndex((t) => t.id === id);
    if (index > -1) {
      toasts.value.splice(index, 1);
    }
  }

  // Initialize theme on store creation
  updateThemeClass();

  // Watch for theme changes
  watch(isDarkMode, () => {
    updateThemeClass();
  });

  // Order Refresh Signal
  const orderRefreshSignal = ref(0);

  function triggerOrderRefresh() {
    orderRefreshSignal.value++;
  }

  // Handle window resize for mobile detection
  function handleResize() {
    isMobile.value = window.innerWidth < 1024;
    if (!isMobile.value) {
      isSidebarOpen.value = true;
    }
  }

  if (typeof window !== "undefined") {
    window.addEventListener("resize", handleResize);
  }

  return {
    // State
    isDarkMode,
    currentLocale,
    isSidebarOpen,
    isMobile,
    toasts,

    // Actions
    toggleTheme,
    setTheme,
    setLocale,
    toggleSidebar,
    showToast,
    removeToast,
    orderRefreshSignal,
    triggerOrderRefresh,
  };
});
