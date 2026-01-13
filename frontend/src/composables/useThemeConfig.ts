import { watch } from "vue";
import { useAuthStore } from "@/stores/auth";

export function useThemeConfig() {
  const authStore = useAuthStore();

  function applyTheme() {
    const shop = authStore.shop;

    console.log("Applying theme with shop data:", shop);

    if (!shop) return;

    const root = document.documentElement;

    // Apply primary color
    const primaryColor = shop.primary_color || "#F97316";
    console.log("Setting primary color:", primaryColor);
    root.style.setProperty("--color-primary", primaryColor);

    // Generate color variants (lighter and darker shades)
    root.style.setProperty("--color-primary-50", lightenHex(primaryColor, 95));
    root.style.setProperty("--color-primary-100", lightenHex(primaryColor, 90));
    root.style.setProperty("--color-primary-200", lightenHex(primaryColor, 75));
    root.style.setProperty("--color-primary-500", primaryColor);
    root.style.setProperty("--color-primary-600", darkenHex(primaryColor, 10));
    root.style.setProperty("--color-primary-700", darkenHex(primaryColor, 20));

    // Apply dark/light mode
    const themeMode = shop.theme_mode || "light";
    console.log("Setting theme mode:", themeMode);
    if (themeMode === "dark") {
      root.classList.add("dark");
    } else {
      root.classList.remove("dark");
    }

    console.log("Theme applied successfully!");
  }

  // Watch for shop changes
  watch(
    () => authStore.shop,
    () => {
      applyTheme();
    },
    { deep: true, immediate: true }
  );

  return {
    applyTheme,
  };
}

// Helper to lighten a hex color
function lightenHex(hex: string, percent: number): string {
  const num = parseInt(hex.replace("#", ""), 16);
  const amt = Math.round(2.55 * percent);
  const R = (num >> 16) + amt;
  const G = ((num >> 8) & 0x00ff) + amt;
  const B = (num & 0x0000ff) + amt;
  return (
    "#" +
    (
      0x1000000 +
      (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
      (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
      (B < 255 ? (B < 1 ? 0 : B) : 255)
    )
      .toString(16)
      .slice(1)
  );
}

// Helper to darken a hex color
function darkenHex(hex: string, percent: number): string {
  const num = parseInt(hex.replace("#", ""), 16);
  const amt = Math.round(2.55 * percent);
  const R = (num >> 16) - amt;
  const G = ((num >> 8) & 0x00ff) - amt;
  const B = (num & 0x0000ff) - amt;
  return (
    "#" +
    (
      0x1000000 +
      (R < 255 ? (R < 1 ? 0 : R) : 255) * 0x10000 +
      (G < 255 ? (G < 1 ? 0 : G) : 255) * 0x100 +
      (B < 255 ? (B < 1 ? 0 : B) : 255)
    )
      .toString(16)
      .slice(1)
  );
}
