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

  // Helper: Calculate Discount amount - APPLIES ALL PROMOTIONS
  function calculateDiscountForOrder(items: PosCartItem[], subtotal: number): { discountAmount: number, promotionId: number | null, promotionName: string } {
      if (promotions.value.length === 0) return { discountAmount: 0, promotionId: null, promotionName: '' };

      // Track which products have been discounted to prevent double-discounting
      const discountedProductIds: number[] = [];
      let totalDiscount = 0;
      const appliedPromotions: any[] = [];

      // 1. Apply BOGO promotions first (they're more specific)
      promotions.value.forEach(promo => {
          if (promo.type === 'buy_x_get_y') {
             const rules = promo.rules || {};
             const buyIds: number[] = rules.buy_product_ids || [];
             const buyQty = Number(rules.buy_quantity || 1);
             const getIds: number[] = rules.get_product_ids || [];
             const getQty = Number(rules.get_quantity || 1);
             const discountPercent = Number(rules.discount_percent || 100);

             // Flatten items for easier processing
             let buyItemCount = 0;
             const possibleGetItems: number[] = []; // Stores BASE prices only

             items.forEach(item => {
                 const basePrice = Number(item.product.price); // BASE PRICE ONLY for BOGO

                 if (buyIds.includes(item.product.id)) {
                     buyItemCount += item.quantity;
                 }
                 
                 if (getIds.includes(item.product.id)) {
                     // Add BASE price for each unit (options NOT included in discount)
                     for(let i=0; i<item.quantity; i++) possibleGetItems.push(basePrice);
                 }
             });

             const intersect = buyIds.filter(id => getIds.includes(id));
             const isOverlap = intersect.length > 0;
             
             let currentDiscount = 0;

             if (isOverlap) {
                 const groupSize = buyQty + getQty;
                 const numGroups = Math.floor(buyItemCount / groupSize);
                 const numDiscountable = numGroups * getQty;
                 
                 possibleGetItems.sort((a, b) => a - b); // Cheapest first
                 
                 for(let i=0; i<Math.min(numDiscountable, possibleGetItems.length); i++) {
                     const price = possibleGetItems[i];
                     if (price !== undefined) currentDiscount += price * (discountPercent / 100);
                 }
             } else {
                 // Disjoint lists (Buy Coffee Get Cookie)
                 const sets = Math.floor(buyItemCount / buyQty);
                 const maxGet = sets * getQty;
                 possibleGetItems.sort((a, b) => a - b);
                 
                 for(let i=0; i<Math.min(maxGet, possibleGetItems.length); i++) {
                     const price = possibleGetItems[i];
                     if (price !== undefined) currentDiscount += price * (discountPercent/100);
                 }
             }

             if (currentDiscount > 0) {
                 totalDiscount += currentDiscount;
                 appliedPromotions.push(promo);
                 // Mark products as discounted
                 discountedProductIds.push(...buyIds, ...getIds);
             }
          }
      });

      // 2. Apply percentage discounts to remaining products
      promotions.value.forEach(promo => {
          if (promo.type === 'percentage') {
              const rules = promo.rules || {};
              const applicableIds: number[] = rules.applicable_product_ids || [];
              
              // Filter out already-discounted products
              const remainingItems = items.filter(item => 
                  !discountedProductIds.includes(item.product.id)
              );

              if (remainingItems.length === 0 && applicableIds.length > 0) {
                  return; // Skip if no applicable items remaining
              }

              let currentDiscount = 0;

              if (applicableIds.length === 0) {
                  // Apply to all remaining items
                  remainingItems.forEach(item => {
                      let price = Number(item.product.price);
                      // Include options for percentage discount
                      if (item.variant) price += Number(item.variant.extra_price);
                      if (item.options) item.options.forEach(o => price += Number(o.extra_price || 0));
                      currentDiscount += (price * item.quantity) * (promo.value / 100);
                  });
              } else {
                  // Specific products only
                  remainingItems.forEach(item => {
                      if (applicableIds.includes(item.product.id)) {
                           let price = Number(item.product.price);
                           // Include options for percentage discount
                           if (item.variant) price += Number(item.variant.extra_price);
                           if (item.options) item.options.forEach(o => price += Number(o.extra_price || 0));
                           currentDiscount += (price * item.quantity) * (promo.value / 100);
                      }
                  });
              }

              if (currentDiscount > 0) {
                  totalDiscount += currentDiscount;
                  appliedPromotions.push(promo);
                  
                  // Mark products as discounted
                  if (applicableIds.length === 0) {
                      remainingItems.forEach(item => {
                          if (!discountedProductIds.includes(item.product.id)) {
                              discountedProductIds.push(item.product.id);
                          }
                      });
                  } else {
                      discountedProductIds.push(...applicableIds);
                  }
              }
          }
      });

      // Return first promotion for backwards compatibility
      const firstPromo = appliedPromotions.length > 0 ? appliedPromotions[0] : null;

      return { 
          discountAmount: totalDiscount, 
          promotionId: firstPromo?.id || null,
          promotionName: appliedPromotions.map(p => p.name).join(', ') || ''
      };
  }

  // For this demo, tax is 0 or included
  const discountDetails = computed(() => {
      // Recalc subtotal for computed trigger
      const sub = subtotal.value;
      return calculateDiscountForOrder(currentOrderItems.value, sub);
  });

  const total = computed(() => {
      return Math.max(0, subtotal.value - discountDetails.value.discountAmount);
  });



  const promotions = ref<any[]>([]);

  async function loadMenu(shopSlug: string) {
    loading.value = true;
    try {
      const [menuRes, promoRes] = await Promise.all([
        guestApi.getMenu(shopSlug),
        guestApi.getPromotions(shopSlug),
      ]);
      categories.value = menuRes.data.categories;
      promotions.value = promoRes.data;
    } catch (error) {
      console.error("Failed to load menu data", error);
    } finally {
      loading.value = false;
    }
  }

  function getDiscountedPrice(product: any) {
    let bestPrice = Number(product.price);
    let hasDiscount = false;
    let label = "";
    let badge = ""; // For non-price changing promos (like Buy X Get Y)

    promotions.value.forEach((promo) => {
      // Percentage Logic
      if (promo.type === "percentage") {
        const rules = promo.rules || {};
        const applicableIds = rules.applicable_product_ids || [];

        if (applicableIds.length === 0 || applicableIds.includes(product.id)) {
          const discountAmount = Number(product.price) * (promo.value / 100);
          const currentPrice = Number(product.price) - discountAmount;

          if (currentPrice < bestPrice) {
            bestPrice = currentPrice;
            hasDiscount = true;
            label = `-${promo.value}%`;
            badge = `-${promo.value}%`;
          }
        }
      }
      // Buy X Get Y Logic (Badge only, no unit price change displayed)
      else if (promo.type === "buy_x_get_y") {
        const rules = promo.rules || {};
        const buyIds = rules.buy_product_ids || [];
        
        if (buyIds.includes(product.id)) {
            // Set badge if not already set
            if (!hasDiscount) {
                const buyQty = rules.buy_quantity || 1;
                const getQty = rules.get_quantity || 1;
                badge = `Buy ${buyQty} Get ${getQty}`;
            }
        }
      }
    });

    return {
      originalPrice: Number(product.price),
      finalPrice: bestPrice,
      hasDiscount,
      label,
      badge,
    };
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
    promotions,
    subtotal,
    total,
    discountDetails,
    loadMenu,
    getDiscountedPrice,
    addToOrder,
    removeFromOrder,
    updateQuantity,
    clearOrder,
    processPayment,
    updatePaymentStatus,
  };
});
