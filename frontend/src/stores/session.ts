import { defineStore } from "pinia";
import { ref, computed } from "vue";
import guestApi from "@/api/guest";

export const useSessionStore = defineStore("session", () => {
  const sessionToken = ref<string | null>(
    localStorage.getItem("session_token"),
  );
  const tableNumber = ref<string | null>(localStorage.getItem("table_number"));
  const shopName = ref<string | null>(localStorage.getItem("shop_name"));
  const shopSlug = ref<string | null>(localStorage.getItem("shop_slug"));
  const shopLogo = ref<string | null>(localStorage.getItem("shop_logo"));

  const isActive = computed(() => !!sessionToken.value);

  async function scanTable(qrToken: string) {
    try {
      const response = await guestApi.scanTable(qrToken);
      const data = response.data;

      sessionToken.value = data.session_token;
      tableNumber.value = data.table_number;
      shopName.value = data.shop.name;
      shopSlug.value = data.shop.slug;
      shopLogo.value = data.shop.logo_url;

      // Persist to localStorage
      localStorage.setItem("session_token", data.session_token);
      localStorage.setItem("table_number", data.table_number);
      localStorage.setItem("shop_name", data.shop.name);
      localStorage.setItem("shop_slug", data.shop.slug);
      if (data.shop.logo_url) {
        localStorage.setItem("shop_logo", data.shop.logo_url);
      }

      return true;
    } catch (error) {
      console.error("Failed to scan table:", error);
      return false;
    }
  }

  function clearSession() {
    sessionToken.value = null;
    tableNumber.value = null;
    shopName.value = null;
    shopSlug.value = null;
    shopLogo.value = null;

    localStorage.removeItem("session_token");
    localStorage.removeItem("table_number");
    localStorage.removeItem("shop_name");
    localStorage.removeItem("shop_slug");
    localStorage.removeItem("shop_logo");
  }

  return {
    sessionToken,
    tableNumber,
    shopName,
    shopSlug,
    shopLogo,
    isActive,
    scanTable,
    clearSession,
  };
});
