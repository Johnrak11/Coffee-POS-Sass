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

// Fetch cart on mount to handle page refreshes
import { onMounted } from "vue";
onMounted(async () => {
  if (sessionStore.sessionToken) {
    await cartStore.fetchCart();
  }
});

// KHQR State
const showKhqrModal = ref(false);
const khqrString = ref<string | null>(null);
const khqrMd5 = ref<string | null>(null);
const khqrLoading = ref(false);
let pollInterval: any = null;

async function deleteItem(id: number) {
  const success = await cartStore.removeItem(id);
  if (success) {
    toast.success("Item removed");
  } else {
    toast.error("Failed to remove item");
  }
}

async function updateItemQuantity(item: any, newQty: number) {
  if (newQty < 1) {
    // If < 1, confirm delete or just delete? User asked for remove logic separate, possibly fine to delete on 0?
    // Let's call delete logic if 0.
    await deleteItem(item.id);
    return;
  }
  // No max limit logic unless product has stock? Assuming unlimited for now.
  await cartStore.updateQuantity(item.id, newQty);
}

// Currency Toggle for KHQR (Default USD, but often KHQR is KHR preferred by locals)
// The generate endpoint accepts currency.
const khqrCurrency = ref<"USD" | "KHR">("USD");

async function checkout(paymentMethod: "cash" | "khqr") {
  if (cartStore.items.length === 0) return;
  if (!sessionStore.sessionToken) return;

  if (paymentMethod === "khqr") {
    // Start KHQR Flow without disabling the main button immediately (modal covers it)
    // or set a different state if needed. But user said "skip loading on top".
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

    // Check for session expiry
    if (error.response?.status === 404) {
      toast.error("Session Expired", {
        description:
          "Your session has expired. Please scan the QR code on your table again.",
        duration: 8000,
      });
    } else {
      toast.error("Checkout Failed", {
        description: error.response?.data?.error || error.message,
      });
    }
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
  } catch (error: any) {
    console.error(error);
    if (error.response?.status === 404) {
      toast.error("Session Expired", {
        description:
          "Your session has expired. Please scan the QR code on your table again.",
        duration: 8000,
      });
    } else {
      toast.error("Failed to generate QR Code");
    }
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

async function shareQrCode() {
  // Find the canvas element (qrcode.vue renders a canvas)
  const canvas = document.querySelector("#khqr-canvas") as HTMLCanvasElement;
  if (!canvas) {
    toast.error("QR Code not ready");
    return;
  }

  canvas.toBlob(async (blob) => {
    if (!blob) return;

    const file = new File([blob], "khqr-payment.png", { type: "image/png" });
    const shareData = {
      files: [file],
      title: "Pay with KHQR",
      text: "Scan this QR code to pay",
    };

    if (navigator.canShare && navigator.canShare(shareData)) {
      try {
        await navigator.share(shareData);
      } catch (err: any) {
        if (err.name !== "AbortError") {
          console.error("Share failed:", err);
          fallbackDownload(blob);
        }
      }
    } else {
      fallbackDownload(blob);
    }
  });
}

function fallbackDownload(blob: Blob) {
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = "khqr-payment.png";
  link.click();
  toast.success("QR Code saved to gallery");
}

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-4 pb-12">
    <div class="max-w-lg mx-auto pt-20">
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
          <h1 class="text-xl font-bold text-gray-900">Your Order</h1>
        </div>
      </div>

      <div v-auto-animate class="space-y-4 mb-8">
        <div
          v-for="item in cartStore.items"
          :key="item.id"
          class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4 relative group"
        >
          <button
            @click="deleteItem(item.id)"
            class="absolute -top-2 -right-2 bg-white text-red-500 rounded-full p-2 shadow-md border border-gray-100 transition-colors hover:bg-red-50 z-10"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>

          <div
            class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center overflow-hidden flex-shrink-0"
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

          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-gray-800 truncate">
              {{ item.product.name }}
            </h3>

            <!-- Quantity Controls -->
            <div class="flex items-center gap-3 mt-1">
              <button
                @click="updateItemQuantity(item, item.quantity - 1)"
                class="w-6 h-6 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gray-200 active:scale-95 transition-all"
                :disabled="isSubmitting"
              >
                <svg
                  class="w-3 h-3"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M20 12H4"
                  />
                </svg>
              </button>

              <span
                class="text-sm font-bold text-gray-900 min-w-[1.5rem] text-center"
                >{{ item.quantity }}</span
              >

              <button
                @click="updateItemQuantity(item, item.quantity + 1)"
                class="w-6 h-6 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 hover:bg-primary-100 active:scale-95 transition-all"
                :disabled="isSubmitting"
              >
                <svg
                  class="w-3 h-3"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M12 4v16m8-8H4"
                  />
                </svg>
              </button>
            </div>
          </div>

          <p class="font-bold text-primary-600 whitespace-nowrap">
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
            v-if="sessionStore.cashPaymentAllowed"
            @click="checkout('cash')"
            :disabled="isSubmitting"
            class="w-full py-5 bg-gray-100 rounded-2xl font-bold text-gray-900 hover:bg-gray-200 transition-all active:scale-[0.98]"
          >
            Pay with Cash
          </button>
          <div
            v-else
            class="text-center text-sm text-gray-500 bg-gray-100 p-4 rounded-2xl border border-gray-200"
          >
            <p class="font-bold mb-1">🔒 Cash Payment Unavailable</p>
            <p class="text-xs">
              Please connect to the Shop Wi-Fi to pay with Cash.
            </p>
          </div>
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
            class="bg-white p-2 rounded-xl shadow-inner border border-gray-100 mb-6 relative"
          >
            <QrcodeVue
              id="khqr-canvas"
              :value="khqrString"
              :size="240"
              level="H"
            />
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

          <!-- Share / Pay Button (Moved to bottom) -->
          <button
            @click="shareQrCode"
            class="mb-6 flex items-center gap-2 px-5 py-2.5 bg-blue-50 text-blue-600 rounded-xl font-bold text-sm hover:bg-blue-100 transition-colors w-full justify-center"
          >
            <svg
              class="w-5 h-5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"
              />
            </svg>
            Share / Pay with App
          </button>

          <div
            class="flex items-center gap-2 text-xs font-bold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-full mb-2"
          >
            <svg
              class="w-4 h-4"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
              />
            </svg>
            QR code is for one-time use only
          </div>

          <div
            class="flex items-center gap-2 text-xs font-bold text-gray-400 animate-pulse"
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
