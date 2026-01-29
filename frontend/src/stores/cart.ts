import { defineStore } from "pinia";
import { ref, computed } from "vue";
import guestApi from "@/api/guest";
import { useSessionStore } from "./session";

import type { CartItem } from "@/api/guest";

export const useCartStore = defineStore("cart", () => {
  const sessionStore = useSessionStore();
  const items = ref<CartItem[]>([]);
  const total = ref(0);
  const subtotal = ref(0);
  const discountAmount = ref(0);
  const promotionId = ref<number | null>(null);
  const itemCount = ref(0);
  const loading = ref(false);

  const partialOrder = ref<any>(null);

  const isEmpty = computed(() => items.value.length === 0);

  async function fetchCart(skipLoading = false) {
    if (!sessionStore.sessionToken) return;

    if (!skipLoading) loading.value = true;
    try {
      const response = await guestApi.getCart(sessionStore.sessionToken, {
        skipLoading,
      });
      items.value = response.data.items || [];
      total.value = response.data.total || 0;
      subtotal.value = response.data.subtotal || response.data.total || 0;
      discountAmount.value = response.data.discount_amount || 0;
      promotionId.value = response.data.promotion_id || null;
      itemCount.value = response.data.item_count || 0;
      partialOrder.value = response.data.partial_order || null;
    } catch (error) {
      // console.error("Failed to fetch cart:", error);
    } finally {
      if (!skipLoading) loading.value = false;
    }
  }

  async function addItem(
    product: any, // Changed to receive full product object for optimistic update
    quantity: number = 1,
    notes: string | null = null,
    options: any[] = [],
  ) {
    if (!sessionStore.sessionToken) return false;

    // Optimistic Update
    const previousItems = [...items.value];
    const previousTotal = total.value;
    const previousSubtotal = subtotal.value;
    const previousCount = itemCount.value;

    // Calculate Option Price
    const optionsTotal = options.reduce((sum, opt) => sum + Number(opt.extra_price || 0), 0);
    const unitPrice = Number(product.price) + optionsTotal;

    // Create a temporary item structure matching CartItem interface
    items.value.push({
        id: -1, // Temporary ID
        cart_id: -1,
        product_id: product.id,
        quantity: quantity,
        price: unitPrice, 
        notes: notes,
        product: product, // Store full product for display
        options: options
    } as any);
    

    // Update counts
    itemCount.value += quantity;
    subtotal.value += unitPrice * quantity;
    total.value += unitPrice * quantity;

    try {
      await guestApi.addToCart({
        session_token: sessionStore.sessionToken,
        product_id: product.id,
        quantity,
        notes: notes || undefined,
        options: options,
      });
      // Re-fetch to ensure data consistency with server (prices, taxes, etc)
      // Pass 'true' to skip loading indicator since we already showed the result
      await fetchCart(true);
      return true;
    } catch (error: any) {
      // Revert on failure
      items.value = previousItems;
      total.value = previousTotal;
      subtotal.value = previousSubtotal;
      itemCount.value = previousCount;

      if ((error as any).response?.status === 404) {
        import("vue-sonner").then(({ toast }) => {
          toast.error("Session Expired", {
            description:
              "Your session has expired. Please scan the QR code on your table again.",
            duration: 8000,
          });
        });
        return false;
      }
      // console.error("Failed to add item:", error);
      return false;
    }
  }

  async function updateQuantity(cartItemId: number, quantity: number) {
    if (!sessionStore.sessionToken) return false;

    // Optimistic Update
    const previousItems = [...items.value];
    const previousTotal = total.value;
    const previousCount = itemCount.value;

    const itemIndex = items.value.findIndex((i) => i.id === cartItemId);
    const item = items.value[itemIndex];

    if (itemIndex > -1 && item) {
      const diff = quantity - item.quantity;
      // Check if product exists before accessing price
      const basePrice = item.product ? Number(item.product.price) : 0;
      const optionsPrice = (item.options || []).reduce((sum: number, opt: any) => sum + Number(opt.extra_price || 0), 0);
      const unitPrice = basePrice + optionsPrice;

      item.quantity = quantity;
      total.value += unitPrice * diff;
      itemCount.value += diff;
    }

    try {
      await guestApi.updateCartItem(cartItemId, quantity);
      // Re-fetch to ensure consistency (especially if backend has logic)
      await fetchCart(true);
      return true;
    } catch (error) {
      // Revert
      items.value = previousItems;
      total.value = previousTotal;
      itemCount.value = previousCount;
      // console.error("Failed to update quantity:", error);
      return false;
    }
  }

  async function removeItem(cartItemId: number) {
    if (!sessionStore.sessionToken) return false;

    // Optimistic Update
    const previousItems = [...items.value];
    const previousTotal = total.value;
    const previousCount = itemCount.value;

    const itemIndex = items.value.findIndex((i) => i.id === cartItemId);
    const item = items.value[itemIndex];

    if (itemIndex > -1 && item) {
      // Calculate full item price (base + options)
      const basePrice = item.product ? Number(item.product.price) : 0;
      const optionsPrice = (item.options || []).reduce((sum: number, opt: any) => sum + Number(opt.extra_price || 0), 0);
      const unitPrice = basePrice + optionsPrice;
      
      total.value -= unitPrice * item.quantity;
      itemCount.value -= item.quantity;
      items.value.splice(itemIndex, 1);
    }

    try {
      await guestApi.removeCartItem(cartItemId);
      // Re-fetch to ensure consistency
      await fetchCart(true);
      return true;
    } catch (error: any) {
      // Revert
      items.value = previousItems;
      total.value = previousTotal;
      itemCount.value = previousCount;

      // console.error("Failed to remove item:", error);
      if ((error as any).response?.status === 404) {
        import("vue-sonner").then(({ toast }) => {
          toast.error("Session Expired", {
            description: "Please scan QR code again.",
          });
        });
      }
      return false;
    }
  }

  function clearCart() {
    items.value = [];
    total.value = 0;
    subtotal.value = 0;
    discountAmount.value = 0;
    promotionId.value = null;
    itemCount.value = 0;
    partialOrder.value = null;
  }

  return {
    items,
    total,
    subtotal,
    discountAmount,
    promotionId,
    itemCount,
    isEmpty,
    loading,
    partialOrder,
    fetchCart,
    addItem,
    updateQuantity,
    removeItem,
    clearCart,
  };
});

