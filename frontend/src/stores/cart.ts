import { defineStore } from "pinia";
import { ref, computed } from "vue";
import guestApi from "@/api/guest";
import { useSessionStore } from "./session";

import type { CartItem } from "@/api/guest";

export const useCartStore = defineStore("cart", () => {
  const sessionStore = useSessionStore();
  const items = ref<CartItem[]>([]);
  const total = ref(0);
  const itemCount = ref(0);
  const loading = ref(false);

  const isEmpty = computed(() => items.value.length === 0);

  async function fetchCart() {
    if (!sessionStore.sessionToken) return;

    loading.value = true;
    try {
      const response = await guestApi.getCart(sessionStore.sessionToken);
      items.value = response.data.items || [];
      total.value = response.data.total || 0;
      itemCount.value = response.data.item_count || 0;
    } catch (error) {
      console.error("Failed to fetch cart:", error);
    } finally {
      loading.value = false;
    }
  }

  async function addItem(
    productId: number,
    quantity: number = 1,
    notes: string | null = null
  ) {
    if (!sessionStore.sessionToken) return false;

    try {
      await guestApi.addToCart({
        session_token: sessionStore.sessionToken,
        product_id: productId,
        quantity,
        notes: notes || undefined,
      });
      await fetchCart();
      return true;
      return true;
    } catch (error: any) {
      if (error.response?.status === 404) {
        import("vue-sonner").then(({ toast }) => {
          toast.error("Session Expired", {
            description:
              "Your session has expired. Please scan the QR code on your table again.",
            duration: 8000,
          });
        });
        return false;
      }
      console.error("Failed to add item:", error);
      return false;
    }
  }

  async function updateQuantity(cartItemId: number, quantity: number) {
    try {
      await guestApi.updateCartItem(cartItemId, quantity);
      await fetchCart();
      return true;
    } catch (error) {
      console.error("Failed to update quantity:", error);
      return false;
    }
  }

  async function removeItem(cartItemId: number) {
    try {
      await guestApi.removeCartItem(cartItemId);
      await fetchCart();
      return true;
    } catch (error) {
      console.error("Failed to remove item:", error);
      return false;
    }
  }

  function clearCart() {
    items.value = [];
    total.value = 0;
    itemCount.value = 0;
  }

  return {
    items,
    total,
    itemCount,
    isEmpty,
    loading,
    fetchCart,
    addItem,
    updateQuantity,
    removeItem,
    clearCart,
  };
});
