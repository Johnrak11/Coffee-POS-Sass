<script setup lang="ts">
import { ref, onUnmounted, computed } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "@/stores/cart";
import { useSessionStore } from "@/stores/session";
import { guestApi } from "@/api";
import { toast } from "vue-sonner";
import QrcodeVue from "qrcode.vue";

const router = useRouter();
const cartStore = useCartStore();
const sessionStore = useSessionStore();
const isSubmitting = ref(false);

// KHQR State
const showKhqrModal = ref(false);
const khqrString = ref<string | null>(null);
const khqrMd5 = ref<string | null>(null);
const khqrLoading = ref(false);
let pollInterval: any = null;

// Currency Toggle for KHQR (Default USD, but often KHQR is KHR preferred by locals)
// The generate endpoint accepts currency.
const khqrCurrency = ref<"USD" | "KHR">("USD");

async function checkout(paymentMethod: "cash" | "khqr") {
  if (cartStore.items.length === 0) return;
  if (!sessionStore.sessionToken) return;

  if (paymentMethod === "khqr") {
    // Start KHQR Flow
    showKhqrModal.value = true;
    await generateKhqr();
    return;
  }

  // Cash Flow (Standard)
  await processCashCheckout();
}

async function processCashCheckout() {
  isSubmitting.value = true;
  const loadingToast = toast.loading("Processing your order...");

  try {
    const response = await guestApi.checkout({
      session_token: sessionStore.sessionToken!,
      payment_method: "cash",
    });

    if (response.data.success) {
      toast.dismiss(loadingToast);
      toast.success("Order placed successfully!");
      const orderId = response.data.order.id;
      cartStore.clearCart();
      router.push(`/success/${orderId}`);
    } else {
      throw new Error(response.data.error || "Checkout failed");
    }
  } catch (error: any) {
    toast.dismiss(loadingToast);
    toast.error("Checkout Failed", {
      description: error.response?.data?.error || error.message,
    });
  } finally {
    isSubmitting.value = false;
  }
}

// KHQR Functions
async function generateKhqr() {
  khqrLoading.value = true;
  khqrString.value = null;
  khqrMd5.value = null;

  try {
    // Calculate Total (Guest API doesn't allow overriding amount easily, we send total from cart logic?
    // Wait, generateKhqr expects amount.
    // We should trust the backend calc usually, but for Guest Generate, we send what we think.
    // Ideally backend validates, but for pure generation it blindly gens.
    // The Finalize step will recreate order from Cart and VALIDATE total then.

    // We need to handle Currency conversion if KHR selected.
    // Assuming simple rate or 4100 for display?
    // Ideally we fetch rate. For now send USD and let backend handle or send USD amount.
    // If user wants KHR QR, we need to convert.
    // Simplification: Always generate USD for Guest for now unless we add toggle.
    // Let's stick to USD to match cart total for safety.

    const response = await guestApi.generateKhqr(cartStore.total, "USD");
    // Response structure: { qr_string: "...", md5: "..." } or nested data
    const data = response.data.data || response.data;

    if (data && data.qr_string && data.md5) {
      khqrString.value = data.qr_string;
      khqrMd5.value = data.md5;
      startPolling();
    } else {
      throw new Error("Invalid KHQR response");
    }
  } catch (error) {
    console.error(error);
    toast.error("Failed to generate QR Code");
    showKhqrModal.value = false;
  } finally {
    khqrLoading.value = false;
  }
}

function startPolling() {
  if (pollInterval) clearInterval(pollInterval);

  pollInterval = setInterval(async () => {
    if (!khqrMd5.value) return;

    try {
      const res = await guestApi.checkStatusSingle(khqrMd5.value!);
      // Check success
      const data = res.data.data?.[0];
      if (data && data.responseCode === 0) {
        await handleKhqrSuccess();
      }
    } catch (e) {
      // ignore poll error
    }
  }, 3000); // 3s polling
}

async function handleKhqrSuccess() {
  if (pollInterval) clearInterval(pollInterval);

  const loadingToast = toast.loading("Payment verified! Creating order...");
  showKhqrModal.value = false; // Close modal

  try {
    const res = await guestApi.finalizeKhqrOrder(
      sessionStore.sessionToken!,
      khqrMd5.value!,
    );

    if (res.data.success) {
      toast.dismiss(loadingToast);
      toast.success("Order confirmed!");
      cartStore.clearCart();
      router.push(`/success/${res.data.order.id}`);
    }
  } catch (e) {
    toast.dismiss(loadingToast);
    toast.error("Failed to update order status. Please contact staff.");
    console.error(e);
  }
}

function closeKhqrModal() {
  showKhqrModal.value = false;
  if (pollInterval) clearInterval(pollInterval);
}

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-4 pb-12">
    <div class="max-w-lg mx-auto">
      <div class="flex items-center gap-4 mb-8">
        <button
          @click="router.back()"
          class="w-10 h-10 bg-white rounded-full shadow-sm flex items-center justify-center hover:bg-gray-50 transition-colors"
        >
          <svg
            class="w-5 h-5 text-gray-500"
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
        <h1 class="text-2xl font-bold text-gray-900">Your Order</h1>
      </div>

      <div v-auto-animate class="space-y-4 mb-8">
        <div
          v-for="item in cartStore.items"
          :key="item.id"
          class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4"
        >
          <div
            class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center overflow-hidden"
          >
            <img
              v-if="item.product.image_url"
              :src="item.product.image_url"
              class="w-full h-full object-cover"
            />
            <span v-else class="font-bold text-gray-300 text-xl">{{
              item.product.name[0]
            }}</span>
          </div>
          <div class="flex-1">
            <h3 class="font-bold text-gray-800">{{ item.product.name }}</h3>
            <p class="text-xs text-gray-400">Qty: {{ item.quantity }}</p>
          </div>
          <p class="font-bold text-gray-900">
            ${{ (Number(item.product.price) * item.quantity).toFixed(2) }}
          </p>
        </div>
      </div>

      <div
        class="bg-white p-8 rounded-[40px] shadow-xl border border-gray-100 space-y-6"
      >
        <div class="space-y-3">
          <div class="flex justify-between text-gray-500 font-medium">
            <span>Subtotal</span>
            <span>${{ Number(cartStore.total).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-gray-500 font-medium">
            <span>Tax (Included)</span>
            <span>$0.00</span>
          </div>
          <div
            class="pt-3 border-t border-gray-100 flex justify-between text-3xl font-black text-gray-900"
          >
            <span>Total</span>
            <span>${{ Number(cartStore.total).toFixed(2) }}</span>
          </div>
        </div>

        <div class="space-y-3 pt-4">
          <button
            @click="checkout('khqr')"
            :disabled="isSubmitting"
            class="w-full py-5 bg-[#E61F25] rounded-2xl font-bold text-white shadow-lg shadow-red-500/30 hover:bg-red-600 transition-all active:scale-[0.98] flex items-center justify-center gap-2"
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
            Pay with KHQR
          </button>
          <button
            @click="checkout('cash')"
            :disabled="isSubmitting"
            class="w-full py-5 bg-gray-100 rounded-2xl font-bold text-gray-900 hover:bg-gray-200 transition-all active:scale-[0.98]"
          >
            Pay with Cash
          </button>
        </div>
      </div>
    </div>

    <!-- KHQR Modal -->
    <div
      v-if="showKhqrModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <div
        class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity"
        @click="closeKhqrModal"
      ></div>
      <div
        class="relative bg-white rounded-[32px] w-full max-w-sm overflow-hidden shadow-2xl animate-scale-in"
      >
        <div class="bg-[#E61F25] p-6 text-center text-white relative">
          <button
            @click="closeKhqrModal"
            class="absolute right-4 top-4 p-2 bg-black/10 rounded-full hover:bg-black/20"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
          <h2 class="text-2xl font-black tracking-wide mb-1">KHQR</h2>
          <p class="text-white/80 text-sm">Scan to Pay</p>
        </div>

        <div class="p-8 flex flex-col items-center">
          <div
            v-if="khqrLoading"
            class="w-64 h-64 flex items-center justify-center bg-gray-50 rounded-2xl"
          >
            <div
              class="animate-spin rounded-full h-12 w-12 border-4 border-red-500 border-t-transparent"
            ></div>
          </div>

          <div
            v-else-if="khqrString"
            class="bg-white p-2 rounded-xl shadow-inner border border-gray-100 mb-6"
          >
            <QrcodeVue :value="khqrString" :size="240" level="H" />
          </div>

          <div class="text-center space-y-1 mb-6">
            <p
              class="text-gray-400 text-xs font-bold uppercase tracking-widest"
            >
              Total Amount
            </p>
            <p class="text-3xl font-black text-gray-900">
              ${{ Number(cartStore.total).toFixed(2) }}
            </p>
          </div>

          <div
            class="flex items-center gap-2 text-xs font-bold text-gray-400 animate-pulse bg-gray-50 px-3 py-1.5 rounded-full"
          >
            <div class="w-2 h-2 bg-red-500 rounded-full"></div>
            Waiting for payment...
          </div>
        </div>

        <div class="p-4 bg-gray-50 border-t border-gray-100 text-center">
          <p class="text-xs text-gray-400">
            Please verify the Merchant Name matches exactly.
          </p>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-scale-in {
  animation: scaleIn 0.2s ease-out;
}
@keyframes scaleIn {
  from {
    transform: scale(0.95);
    opacity: 0;
  }
  to {
    transform: scale(1);
    opacity: 1;
  }
}
</style>
