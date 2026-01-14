<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { usePosStore } from "@/stores/pos";
import { useAuthStore } from "@/stores/auth";
import PaymentModal from "@/components/PaymentModal.vue";
import ReceiptModal from "@/components/ReceiptModal.vue";
import ProductCustomizeModal from "@/components/ProductCustomizeModal.vue";
import { toast } from "vue-sonner";

const posStore = usePosStore();
const authStore = useAuthStore();

const selectedCategory = ref<number | null>(null);
const showPaymentModal = ref(false);
const showReceiptModal = ref(false);
const showCustomizeModal = ref(false);
const selectedProduct = ref<any>(null);
const selectedPaymentMethod = ref<"cash" | "khqr">("cash");
const searchQuery = ref("");

// Receipt Data
const receiptData = ref({
  items: [] as any[],
  total: 0,
  cashReceived: 0,
  change: 0,
  orderNumber: "",
  shopName: "Lucky Cafe",
});

// Computed products list based on selected category
const displayedProducts = computed(() => {
  let products: any[] = [];
  if (!selectedCategory.value) {
    products = posStore.categories.flatMap((c: any) => c.products);
  } else {
    const category = posStore.categories.find(
      (c) => c.id === selectedCategory.value
    );
    products = category ? category.products : [];
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    return products.filter((p) => p.name.toLowerCase().includes(q));
  }

  return products;
});

onMounted(async () => {
  if (authStore.user) {
    // We'd normally use the shop slug from user/store
    await posStore.loadMenu("lucky-cafe");
    if (posStore.categories.length > 0) {
      selectedCategory.value = posStore.categories[0].id;
    }
  }
});

function formatPrice(price: number) {
  return Number(price).toFixed(2);
}

function initiatePayment(method: "cash" | "khqr") {
  if (posStore.currentOrderItems.length === 0) return;

  selectedPaymentMethod.value = method;
  if (method === "cash") {
    showPaymentModal.value = true;
  } else {
    // Direct KHQR process for now
    handleDirectPayment("khqr");
  }
}

async function handlePaymentConfirm(paymentData: {
  receivedAmount: number;
  currency: "USD" | "KHR";
}) {
  showPaymentModal.value = false;

  // Capture data for receipt before clearing order
  const items = [...posStore.currentOrderItems];
  const total = posStore.total;

  const success = await posStore.processPayment(
    1,
    "cash",
    paymentData.currency,
    paymentData.receivedAmount
  );

  if (success) {
    const symbol = paymentData.currency === "USD" ? "$" : "៛";
    const formattedReceived =
      paymentData.currency === "USD"
        ? paymentData.receivedAmount.toFixed(2)
        : new Intl.NumberFormat("en-US").format(paymentData.receivedAmount);

    toast.success("Payment Successful", {
      description: `Cash received: ${symbol}${formattedReceived}`,
    });

    // Calculate change (careful with currency)
    // For Receipt, we might wants to show the details.
    // Simplifying: If paid in KHR, we handle change in KHR usually?
    // Let's pass the raw data to ReceiptModal and let it format.

    receiptData.value = {
      items,
      total,
      cashReceived: paymentData.receivedAmount,
      change: 0, // Calculated in Modal or Store? Let's just pass raw and handle display there
      currency: paymentData.currency, // New field for ReceiptModal
      orderNumber: "ORD-" + Math.floor(Math.random() * 10000), // Mock until we get real response
      shopName: authStore.shop?.name || "Lucky Cafe",
    };
    showReceiptModal.value = true;
  } else {
    toast.error("Payment Failed", {
      description: "Please try again or use another method.",
    });
  }
}

function handleProductClick(product: any) {
  // If product has variants, show customize modal
  if (product.variants && product.variants.length > 0) {
    selectedProduct.value = product;
    showCustomizeModal.value = true;
  } else {
    // Direct add without customization
    posStore.addToOrder(product, null, []);
    toast.success("Added to order", {
      description: product.name,
    });
  }
}

function handleCustomizeAdd(data: any) {
  posStore.addToOrder(data.product, null, data.options);
  toast.success("Added to order", {
    description: data.product.name,
  });
}

async function handleDirectPayment(method: "khqr") {
  const items = [...posStore.currentOrderItems];
  const total = posStore.total;

  const success = await posStore.processPayment(1, method);

  if (success) {
    toast.success("KHQR Payment Received", {
      description: `Total: $${total.toFixed(2)}`,
    });
    receiptData.value = {
      items,
      total,
      cashReceived: total,
      change: 0,
      orderNumber: "ORD-" + Math.floor(Math.random() * 10000),
      shopName: authStore.shop?.name || "Lucky Cafe",
    };
    showReceiptModal.value = true;
  } else {
    toast.error("KHQR Payment Failed");
  }
}
</script>

<template>
  <div class="flex h-full gap-4 relative">
    <PaymentModal
      v-if="showPaymentModal"
      :total-amount="posStore.total"
      :is-open="showPaymentModal"
      @close="showPaymentModal = false"
      @confirm="handlePaymentConfirm"
    />

    <ReceiptModal
      v-if="showReceiptModal"
      :is-open="showReceiptModal"
      :order-items="receiptData.items"
      :total="receiptData.total"
      :cash-received="receiptData.cashReceived"
      :change="receiptData.change"
      :order-number="receiptData.orderNumber"
      :shop-name="receiptData.shopName"
      @close="showReceiptModal = false"
    />

    <!-- Left: Menu Grid -->
    <div
      class="flex-1 bg-app-surface rounded-2xl shadow-sm flex flex-col overflow-hidden border border-app-border"
    >
      <!-- Search Bar -->
      <div class="p-4 border-b border-app-border bg-app-bg/50">
        <div class="relative">
          <input
            v-model="searchQuery"
            type="text"
            placeholder="Search products (Ctrl+F)..."
            class="w-full bg-app-bg border border-app-border rounded-xl py-3 pl-11 pr-4 text-sm text-app-text placeholder-app-muted focus:ring-2 focus:ring-primary-500 outline-none transition-all"
          />
          <svg
            class="w-5 h-5 text-app-muted absolute left-3 top-1/2 -translate-y-1/2"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
            />
          </svg>
        </div>
      </div>

      <!-- Category Tabs -->
      <div
        class="p-4 border-b border-app-border overflow-x-auto whitespace-nowrap flex gap-2 no-scrollbar"
      >
        <button
          v-for="cat in posStore.categories"
          :key="cat.id"
          @click="selectedCategory = cat.id"
          :class="[
            'px-4 py-2 rounded-xl font-medium transition-colors',
            selectedCategory === cat.id
              ? 'bg-primary-600 text-white'
              : 'bg-app-bg text-app-muted hover:bg-app-border hover:text-app-text',
          ]"
        >
          {{ cat.name }}
        </button>
      </div>

      <!-- Products Grid -->
      <div class="flex-1 overflow-y-auto p-4">
        <div class="grid grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          <button
            v-for="product in displayedProducts"
            :key="product.id"
            @click="handleProductClick(product)"
            class="bg-app-surface border border-app-border rounded-xl p-3 hover:shadow-md transition-shadow text-left flex flex-col h-full active:scale-95 transition-transform duration-100 group"
          >
            <div
              class="aspect-video bg-app-bg rounded-lg mb-3 flex items-center justify-center overflow-hidden"
            >
              <img
                v-if="product.image_url"
                :src="product.image_url"
                class="w-full h-full object-cover"
              />
              <span v-else class="text-app-muted text-2xl font-bold">{{
                product.name.charAt(0)
              }}</span>
            </div>
            <h3 class="font-bold text-app-text line-clamp-2 leading-tight">
              {{ product.name }}
            </h3>
            <div class="mt-auto pt-2 flex justify-between items-center">
              <span class="text-primary-600 font-bold"
                >${{ formatPrice(product.price) }}</span
              >
              <div
                class="w-6 h-6 bg-primary-100 dark:bg-primary-900/30 text-primary-600 rounded-full flex items-center justify-center font-bold"
              >
                +
              </div>
            </div>
          </button>
        </div>
      </div>
    </div>

    <!-- Right: Current Order -->
    <div
      class="w-96 bg-app-surface rounded-2xl shadow-sm flex flex-col h-full border border-app-border"
    >
      <div
        class="p-4 border-b border-app-border flex justify-between items-center"
      >
        <div>
          <h2 class="font-bold text-lg text-app-text">Current Order</h2>
          <p class="text-sm text-app-muted">
            {{ authStore.user?.name || "Cashier" }}
          </p>
        </div>
        <button
          @click="posStore.clearOrder()"
          class="text-red-500 text-sm hover:bg-red-50 hover:dark:bg-red-900/20 px-2 py-1 rounded transition-colors"
        >
          Clear
        </button>
      </div>

      <div v-auto-animate class="flex-1 overflow-y-auto p-4 space-y-3">
        <div
          v-if="posStore.currentOrderItems.length === 0"
          class="h-full flex flex-col items-center justify-center text-app-muted"
        >
          <svg
            class="w-12 h-12 mb-2 opacity-50"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
            ></path>
          </svg>
          <p>No items added</p>
        </div>

        <div
          v-for="item in posStore.currentOrderItems"
          :key="item.id"
          class="flex gap-3 items-center animate-slide-up"
        >
          <div
            class="w-10 h-10 bg-app-bg rounded-lg flex items-center justify-center font-bold text-app-muted border border-app-border"
          >
            {{ item.quantity }}x
          </div>
          <div class="flex-1 min-w-0">
            <h4 class="font-bold text-app-text truncate">
              {{ item.product.name }}
            </h4>
            <p v-if="item.variant" class="text-xs text-app-muted">
              {{ item.variant.name }}: {{ item.variant.option_name }}
            </p>
            <div
              v-if="item.options && item.options.length > 0"
              class="text-xs text-app-muted mt-1 space-y-0.5"
            >
              <div v-for="(opt, idx) in item.options" :key="idx">
                {{ opt.group_name }}: {{ opt.option_name }}
              </div>
            </div>
            <p class="text-sm text-primary-600 mt-1">
              {{
                (() => {
                  let price = Number(item.product.price);
                  if (item.variant)
                    price += Number(item.variant.extra_price || 0);
                  if (item.options) {
                    item.options.forEach((opt) => {
                      price += Number(opt.extra_price || 0);
                    });
                  }
                  return "$" + formatPrice(price * item.quantity);
                })()
              }}
            </p>
          </div>
          <div class="flex flex-col gap-1">
            <button
              @click="posStore.updateQuantity(item.id, 1)"
              class="w-6 h-6 rounded bg-app-bg hover:bg-gray-200 dark:hover:bg-gray-700 text-app-text flex items-center justify-center border border-app-border transition-colors"
            >
              +
            </button>
            <button
              @click="posStore.updateQuantity(item.id, -1)"
              class="w-6 h-6 rounded bg-app-bg hover:bg-gray-200 dark:hover:bg-gray-700 text-app-text flex items-center justify-center border border-app-border transition-colors"
            >
              -
            </button>
          </div>
        </div>
      </div>

      <div class="p-4 bg-app-bg border-t border-app-border rounded-b-2xl">
        <div class="space-y-2 mb-4">
          <div class="flex justify-between text-app-muted">
            <span>Subtotal</span>
            <span>${{ formatPrice(posStore.subtotal) }}</span>
          </div>
          <div class="flex justify-between text-xl font-bold text-app-text">
            <span>Total</span>
            <span>${{ formatPrice(posStore.total) }}</span>
          </div>
        </div>

        <div class="grid grid-cols-2 gap-3">
          <button
            class="btn-accent py-3 text-lg disabled:opacity-50 disabled:cursor-not-allowed"
            @click="initiatePayment('khqr')"
            :disabled="posStore.currentOrderItems.length === 0"
          >
            KHQR
          </button>
          <button
            class="btn-primary py-3 text-lg disabled:opacity-50 disabled:cursor-not-allowed"
            @click="initiatePayment('cash')"
            :disabled="posStore.currentOrderItems.length === 0"
          >
            Cash
          </button>
        </div>
      </div>
    </div>

    <!-- Modals -->
    <PaymentModal
      :show="showPaymentModal"
      :total="posStore.total"
      @confirm="handlePaymentConfirm"
      @close="showPaymentModal = false"
    />

    <ReceiptModal
      :show="showReceiptModal"
      :receipt-data="receiptData"
      @close="showReceiptModal = false"
    />

    <ProductCustomizeModal
      :show="showCustomizeModal"
      :product="selectedProduct"
      @close="showCustomizeModal = false"
      @add-to-cart="handleCustomizeAdd"
    />
  </div>
</template>
