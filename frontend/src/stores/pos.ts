import { defineStore } from "pinia";
import { ref, computed } from "vue";
import apiClient from "@/api";
import guestApi from "@/api/guest";

interface PosCartItem {
  id: string; // temporary frontend ID
  product: any;
  variant?: any; // Legacy support
  options?: any[]; // New options system
  quantity: number;
  notes?: string;
}

export const usePosStore = defineStore("pos", () => {
  const currentOrderItems = ref<PosCartItem[]>([]);
  const categories = ref<any[]>([]);
  const loading = ref(false);
  const processingPayment = ref(false);

  const subtotal = computed(() => {
    return currentOrderItems.value.reduce((sum, item) => {
      let price = Number(item.product.price);
      // Legacy variant support
      if (item.variant) {
        price += Number(item.variant.extra_price);
      }
      // New options support
      if (item.options && Array.isArray(item.options)) {
        item.options.forEach((opt: any) => {
          price += Number(opt.extra_price || 0);
        });
      }
      return sum + price * item.quantity;
    }, 0);
  });

  // For this demo, tax is 0 or included
  const total = computed(() => subtotal.value);

  async function loadMenu(shopSlug: string) {
    loading.value = true;
    try {
      const response = await guestApi.getMenu(shopSlug);
      categories.value = response.data.categories;
    } catch (error) {
      console.error("Failed to load menu", error);
    } finally {
      loading.value = false;
    }
  }

  function addToOrder(product: any, variant: any = null, options: any[] = []) {
    // Check if same product+variant+options exists
    const existing = currentOrderItems.value.find(
      (item) =>
        item.product.id === product.id &&
        JSON.stringify(item.variant) === JSON.stringify(variant) &&
        JSON.stringify(item.options) === JSON.stringify(options),
    );

    if (existing) {
      existing.quantity++;
    } else {
      currentOrderItems.value.push({
        id: Date.now().toString() + Math.random(),
        product,
        variant,
        options,
        quantity: 1,
      });
    }
  }

  function removeFromOrder(itemId: string) {
    const index = currentOrderItems.value.findIndex((i) => i.id === itemId);
    if (index !== -1) {
      currentOrderItems.value.splice(index, 1);
    }
  }

  function updateQuantity(itemId: string, delta: number) {
    const item = currentOrderItems.value.find((i) => i.id === itemId);
    if (item) {
      item.quantity += delta;
      if (item.quantity <= 0) {
        removeFromOrder(itemId);
      }
    }
  }

  function clearOrder() {
    currentOrderItems.value = [];
  }

  async function processPayment(
    shopId: number,
    paymentMethod: "cash" | "khqr",
    paymentCurrency: "USD" | "KHR" = "USD",
    receivedAmount: number = 0,
  ) {
    processingPayment.value = true;
    try {
      const payload = {
        shop_id: shopId,
        payment_method: paymentMethod,
        payment_currency: paymentCurrency,
        received_amount: receivedAmount,
        items: currentOrderItems.value.map((item) => {
          const itemData: any = {
            product_id: item.product.id,
            product_variant_id: item.variant?.id || null,
            quantity: item.quantity,
            price: parseFloat(item.product.price),
            variant_price: item.variant
              ? parseFloat(item.variant.extra_price)
              : 0,
          };

          // Add options if present
          if (item.options && item.options.length > 0) {
            itemData.options = item.options;
          }

          return itemData;
        }),
      };

      const response = await apiClient.post("/staff/orders", payload, {
        skipLoading: paymentMethod === "khqr",
      } as any);

      if (response.data.success) {
        // Only clear cart immediately for direct payments (Cash/Dashboard)
        // For KHQR, we stick the cart until confirmed or allow "Cancel" to keep data
        if (paymentMethod !== "khqr") {
          clearOrder();
        }
        return response.data; // Return full data including order & qr_data
      }
      return null;
    } catch (e) {
      console.error("Payment failed", e);
      return null;
    } finally {
      processingPayment.value = false;
    }
  }

  async function updatePaymentStatus(
    orderId: number,
    status: string,
    options: any = {},
  ) {
    loading.value = true;
    try {
      const response = await apiClient.put(
        `/staff/orders/${orderId}/payment-status`,
        {
          status,
          ...options, // e.g., received_amount, payment_method, etc.
        },
      );
      return response.data;
    } catch (e) {
      console.error("Update status failed", e);
      return null;
    } finally {
      loading.value = false;
    }
  }

  return {
    currentOrderItems,
    categories,
    loading,
    subtotal,
    total,
    loadMenu,
    addToOrder,
    removeFromOrder,
    updateQuantity,
    clearOrder,
    processPayment,
    updatePaymentStatus,
  };
});
