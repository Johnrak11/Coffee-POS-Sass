import { defineStore } from "pinia";
import { ref, computed } from "vue";
import apiClient from "@/api";

export const useAuthStore = defineStore("auth", () => {
  const user = ref<any>(null);
  const token = ref<string | null>(localStorage.getItem("staff_token"));
  const shop = ref<any>(null);

  const isAuthenticated = computed(() => !!token.value);
  const isOwner = computed(() => user.value?.role === "owner");

  async function getStaffList(shopSlug: string) {
    try {
      const response = await apiClient.get(`/staff/users/${shopSlug}`);
      shop.value = response.data.shop;
      localStorage.setItem("staff_shop", JSON.stringify(response.data.shop));
      return response.data.users;
    } catch (error) {
      console.error("Failed to load staff:", error);
      return [];
    }
  }

  async function login(userId: number, pinCode: string) {
    try {
      const response = await apiClient.post(`/staff/auth`, {
        user_id: userId,
        pin_code: pinCode,
      });

      if (response.data.success) {
        user.value = response.data.user;
        token.value = response.data.token;
        localStorage.setItem("staff_token", response.data.token);
        localStorage.setItem("staff_role", response.data.user.role);
        localStorage.setItem("staff_user", JSON.stringify(response.data.user));
        return true;
      }
      return false;
    } catch (error) {
      console.error("Login failed:", error);
      return false;
    }
  }

  function logoutUser() {
    user.value = null;
    token.value = null;
    localStorage.removeItem("staff_token");
    localStorage.removeItem("staff_role");
    localStorage.removeItem("staff_user");
    // Keep last_shop_slug
  }

  function logoutShop() {
    logoutUser();
    shop.value = null;
    localStorage.removeItem("last_shop_slug");
    localStorage.removeItem("staff_shop");
  }

  // Try to restore session
  function restoreSession() {
    const storedUser = localStorage.getItem("staff_user");
    if (storedUser && token.value) {
      user.value = JSON.parse(storedUser);
    }

    const storedShop = localStorage.getItem("staff_shop");
    if (storedShop) {
      shop.value = JSON.parse(storedShop);
    }
  }

  return {
    user,
    token,
    shop,
    isAuthenticated,
    isOwner,
    getStaffList,
    login,
    logoutUser,
    logoutShop,
    restoreSession,
  };
});
