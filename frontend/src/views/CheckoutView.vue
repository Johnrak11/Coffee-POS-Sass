<script setup lang="ts">
import { ref, onUnmounted, onMounted, watchEffect, watch } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "@/stores/cart";
import { useSessionStore } from "@/stores/session";
import { guestApi } from "@/api";
import { toast } from "vue-sonner";
import { useI18n } from "vue-i18n";

// Components
import KhqrPaymentModal from "@/components/checkout/KhqrPaymentModal.vue";
import CartItemList from "@/components/checkout/CartItemList.vue";
import PartialPaymentDashboard from "@/components/checkout/PartialPaymentDashboard.vue";

const router = useRouter();
const cartStore = useCartStore();
const sessionStore = useSessionStore();
const isSubmitting = ref(false);
const { t } = useI18n();

// Fetch cart on mount
onMounted(async () => {
  if (sessionStore.sessionToken) {
    await cartStore.fetchCart();
  }
});

// KHQR State
const showKhqrModal = ref(false);

// Partial Payment State
const isPartialPayment = ref(false);
const partialOrder = ref<any>(null);
const remainingAmount = ref(0);

// Sync Partial State
watchEffect(() => {
  if (cartStore.partialOrder) {
    isPartialPayment.value = true;
    partialOrder.value = cartStore.partialOrder;
    remainingAmount.value = cartStore.partialOrder.remaining_amount;
  }
});

let cartPollInterval: any = null;

function startCartPolling() {
  stopCartPolling();
  cartPollInterval = setInterval(() => {
    if (sessionStore.sessionToken) {
      cartStore.fetchCart(true);
    }
  }, 5000);
}

function stopCartPolling() {
  if (cartPollInterval !== null) {
    clearInterval(cartPollInterval);
    cartPollInterval = null;
  }
}

// Watch Modal to toggle polling
watch(showKhqrModal, (isOpen) => {
  if (isOpen) {
    stopCartPolling();
  } else {
    startCartPolling();
  }
});

async function deleteItem(id: number) {
  const success = await cartStore.removeItem(id);
  if (success) {
    toast.success(t("customer.itemRemoved"));
  } else {
    toast.error(t("customer.removeFailed"));
  }
}

async function updateItemQuantity(item: any, newQty: number) {
  if (newQty < 1) {
    await deleteItem(item.id);
    return;
  }
  await cartStore.updateQuantity(item.id, newQty);
}

async function checkout(paymentMethod: "cash" | "khqr") {
  if (cartStore.items.length === 0 && !isPartialPayment.value) return;
  if (!sessionStore.sessionToken) return;

  if (paymentMethod === "khqr") {
    // Just open modal, it handles generation and polling
    showKhqrModal.value = true;
    return;
  }

  await processCashCheckout();
}

function handleKhqrSuccess(result: any) {
  // Modal handles API and Toast. We just handle Navigation.
  toast.success(t("customer.orderConfirmed"));

  if (result.status === "partial") {
    toast.warning(result.message || t("customer.paymentReceivedPartial"));
    // Update local state is handled by watchEffect on cartStore usually,
    // but we might need to refresh cart logic or trust the result?
    // result.order should have updated data.
    // Let's force a fetch to be safe and sync state.
    cartStore.fetchCart(true);
  } else {
    // Full Success
    cartStore.clearCart();
    router.push(`/success/${result.order.id}`);
  }
}

async function processCashCheckout() {
  isSubmitting.value = true;
  const loadingToast = toast.loading(t("customer.processingOrder"));

  try {
    const response = await guestApi.checkout({
      session_token: sessionStore.sessionToken!,
      payment_method: "cash",
    });

    if (response.data.success) {
      toast.dismiss(loadingToast);
      toast.success(t("customer.orderPlacedSuccess"));
      const orderId = response.data.order.id;
      cartStore.clearCart();
      router.push(`/success/${orderId}`);
    } else {
      throw new Error(response.data.error || t("customer.checkoutFailed"));
    }
  } catch (error: any) {
    toast.dismiss(loadingToast);
    if (error.response?.status === 404) {
      toast.error(t("customer.sessionExpired"), {
        description: t("customer.scanQrAgain"),
        duration: 8000,
      });
    } else {
      toast.error(t("customer.checkoutFailed"), {
        description: error.response?.data?.error || error.message,
      });
    }
  } finally {
    isSubmitting.value = false;
  }
}

// Real-time access check
async function checkAccess() {
  if (!sessionStore.shopSlug) return;
  try {
    const res = await guestApi.checkAccess(sessionStore.shopSlug);
    sessionStore.cashPaymentAllowed = res.data.cash_payment_allowed;
  } catch (e) {
    // silent
  }
}

onMounted(async () => {
  await checkAccess();
  startCartPolling();
});

onUnmounted(() => {
  stopCartPolling();
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-4 pb-12">
    <div class="max-w-lg mx-auto pt-20">
      <!-- Header -->
      <div
        class="fixed top-0 left-0 right-0 z-40 bg-gray-50/90 backdrop-blur-md p-4"
      >
        <div class="max-w-lg mx-auto flex items-center gap-4">
          <button
            @click="router.back()"
            class="w-10 h-10 bg-white rounded-full shadow-sm flex items-center justify-center hover:bg-gray-100 transition-colors border border-gray-100"
          >
            <svg
              class="w-5 h-5 text-gray-600"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M15 19l-7-7 7-7"
              ></path>
            </svg>
          </button>
          <h1 class="text-xl font-bold text-gray-900">
            {{ t("customer.yourOrder") }}
          </h1>
        </div>
      </div>

      <!-- Partial Dashboard -->
      <PartialPaymentDashboard
        v-if="isPartialPayment"
        :partial-order="partialOrder"
        :remaining-amount="remainingAmount"
        @pay-remaining="checkout('khqr')"
      />

      <!-- Cart List -->
      <CartItemList
        v-if="cartStore.items.length > 0"
        :items="cartStore.items"
        :is-partial-payment="isPartialPayment"
        :is-submitting="isSubmitting"
        @delete="deleteItem"
        @update-quantity="updateItemQuantity"
      />

      <!-- Empty State -->
      <div v-else-if="!isPartialPayment" class="text-center py-20">
        <div
          class="w-20 h-20 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4"
        >
          <svg
            class="w-10 h-10 text-gray-400"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
            />
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900 mb-2">
          {{ t("customer.cartEmpty") }}
        </h2>
        <p class="text-gray-500 mb-6">
          {{ t("customer.cartEmptyDesc") }}
        </p>
        <button
          @click="router.push(`/menu/${sessionStore.shopSlug}`)"
          class="px-6 py-3 bg-primary-600 text-white rounded-xl font-bold hover:bg-primary-700 transition-colors"
        >
          {{ t("customer.goToMenu") }}
        </button>
      </div>

      <!-- Payment Actions -->
      <div
        v-if="!isPartialPayment && cartStore.items.length > 0"
        class="bg-white p-8 rounded-[40px] shadow-xl border border-gray-100 space-y-6"
      >
        <div class="space-y-3">
          <div class="flex justify-between text-gray-500 font-medium">
            <span>{{ t("customer.subtotal") }}</span>
            <span
              >${{
                Number(cartStore.subtotal || cartStore.total).toFixed(2)
              }}</span
            >
          </div>

          <div
            v-if="(cartStore.discountAmount || 0) > 0"
            class="flex justify-between text-green-600 font-bold"
          >
            <span>Discount</span>
            <span
              >- ${{ Number(cartStore.discountAmount || 0).toFixed(2) }}</span
            >
          </div>

          <div
            class="pt-3 border-t border-gray-100 flex justify-between text-3xl font-black text-gray-900"
          >
            <span>{{ t("customer.total") }}</span>
            <span>${{ Number(cartStore.total).toFixed(2) }}</span>
          </div>
        </div>

        <div class="space-y-3 pt-4">
          <button
            @click="checkout('khqr')"
            :disabled="isSubmitting"
            class="w-full py-3 bg-[#E61F25] rounded-2xl font-bold text-white shadow-lg shadow-red-500/30 hover:bg-red-600 transition-all active:scale-[0.98] flex items-center justify-center gap-2"
          >
            <svg
              class="w-6 h-6"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
              />
            </svg>
            {{ t("customer.payWithKhqr") }}
          </button>
          <button
            v-if="sessionStore.cashPaymentAllowed"
            @click="checkout('cash')"
            :disabled="isSubmitting"
            class="w-full py-3 bg-gray-100 rounded-2xl font-bold text-gray-900 hover:bg-gray-200 transition-all active:scale-[0.98]"
          >
            {{ t("customer.payWithCash") }}
          </button>
          <div
            v-else
            class="text-center text-sm text-gray-500 bg-gray-100 p-4 rounded-2xl border border-gray-200"
          >
            <p class="font-bold mb-1">🔒 {{ t("customer.cashUnavailable") }}</p>
            <p class="text-xs">
              {{ t("customer.connectToWifiForCash") }}
            </p>
          </div>
        </div>
      </div>
    </div>

    <!-- KHQR Modal Component -->
    <KhqrPaymentModal
      v-model="showKhqrModal"
      :session-token="sessionStore.sessionToken!"
      :amount="isPartialPayment ? remainingAmount : cartStore.total"
      :shop-name="sessionStore.shopName || 'Coffee Shop'"
      :is-partial="isPartialPayment"
      :partial-order-id="partialOrder?.id"
      @success="handleKhqrSuccess"
    />
  </div>
</template>
