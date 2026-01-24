<script setup lang="ts">
import { ref, onUnmounted, onMounted, watchEffect } from "vue";
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

// Partial Payment State
const isPartialPayment = ref(false);
const partialOrder = ref<any>(null);
const remainingAmount = ref(0);

// Partial Payment State Sync
watchEffect(() => {
  if (cartStore.partialOrder) {
    isPartialPayment.value = true;
    partialOrder.value = cartStore.partialOrder;
    remainingAmount.value = cartStore.partialOrder.remaining_amount;
  }
});

let khqrPollInterval: any = null;
let cartPollInterval: any = null;

function startCartPolling() {
  stopCartPolling();
  cartPollInterval = setInterval(() => {
    if (sessionStore.sessionToken) {
      cartStore.fetchCart(true); // skipLoading=true
    }
  }, 5000);
}

function stopCartPolling() {
  if (cartPollInterval !== null) {
    clearInterval(cartPollInterval);
    cartPollInterval = null;
  }
}

function startKhqrPolling() {
  stopCartPolling(); // Stop cart updates while checking payment

  if (khqrPollInterval) clearInterval(khqrPollInterval);

  khqrPollInterval = setInterval(async () => {
    if (!khqrMd5.value) return;

    try {
      const res = await guestApi.checkStatusSingle(khqrMd5.value!);
      const data = res.data.data?.[0];
      if (data && data.responseCode === 0) {
        await handleKhqrSuccess();
      }
    } catch (e) {
      // ignore poll error
    }
  }, 5000);
}

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
    await deleteItem(item.id);
    return;
  }
  await cartStore.updateQuantity(item.id, newQty);
}

async function checkout(paymentMethod: "cash" | "khqr") {
  // Check if cart is empty, UNLESS it's a partial payment
  if (cartStore.items.length === 0 && !isPartialPayment.value) return;
  if (!sessionStore.sessionToken) return;

  if (paymentMethod === "khqr") {
    stopCartPolling();
    showKhqrModal.value = true;
    await generateKhqr();
    return;
  }

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
    const amount = isPartialPayment.value
      ? remainingAmount.value
      : cartStore.total;

    const response = await guestApi.generateKhqr(
      amount,
      "USD",
      sessionStore.sessionToken!,
    );

    const data = response.data.data || response.data;

    if (data && data.qr_string && data.md5) {
      khqrString.value = data.qr_string;
      khqrMd5.value = data.md5;
      startKhqrPolling();
    } else {
      throw new Error("Invalid KHQR response");
    }
  } catch (error: any) {
    // console.error(error); // Cleared for security
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
    startCartPolling(); // Resume if failed
  } finally {
    khqrLoading.value = false;
  }
}

async function handleKhqrSuccess() {
  if (khqrPollInterval) clearInterval(khqrPollInterval);

  const loadingToast = toast.loading("Payment verified! Creating order...");
  showKhqrModal.value = false;

  try {
    let res;

    if (isPartialPayment.value && partialOrder.value) {
      // Finalize REMAINING Payment
      res = await guestApi.finalizeOrderPayment(
        partialOrder.value.id,
        khqrMd5.value!,
      );
    } else {
      // First Payment
      res = await guestApi.finalizeKhqrOrder(
        sessionStore.sessionToken!,
        khqrMd5.value!,
      );
    }

    if (res.data.success) {
      // Check Status
      if (res.data.status === "partial") {
        // Still partial (maybe paid another small chunk?)
        toast.dismiss(loadingToast);
        toast.warning(
          res.data.message || "Payment received. Remaining balance updated.",
        );

        isPartialPayment.value = true;
        partialOrder.value = res.data.order;
        remainingAmount.value = res.data.remaining_amount;

        // Close modal so user sees the new Dashboard UI
        showKhqrModal.value = false;
      } else {
        // Success (Full Payment)
        toast.dismiss(loadingToast);
        toast.success("Order confirmed!");

        // Clear state
        isPartialPayment.value = false;
        partialOrder.value = null;
        remainingAmount.value = 0;

        cartStore.clearCart();
        router.push(`/success/${res.data.order.id}`);
      }
    }
  } catch (e: any) {
    toast.dismiss(loadingToast);
    if (e.response && e.response.status === 409) {
      toast.error("Cart updated during payment. Please regenerate QR.");
      await cartStore.fetchCart();
    } else {
      toast.error("Failed to update order status. Please contact staff.");
    }
    // console.error(e); // Cleared
    startCartPolling(); // Resume on error
  }
}

function closeKhqrModal() {
  showKhqrModal.value = false;
  if (khqrPollInterval) clearInterval(khqrPollInterval);
  startCartPolling(); // Resume cart polling
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

// Real-time access check
async function checkAccess() {
  if (!sessionStore.shopSlug) return;
  try {
    const res = await guestApi.checkAccess(sessionStore.shopSlug);
    // Update store state with real-time result
    sessionStore.cashPaymentAllowed = res.data.cash_payment_allowed;
  } catch (e) {
    // Fail silently or minimal log if really needed during dev, for prod remove
  }
}

onMounted(async () => {
  // Check if we still have access (e.g. still on Wi-Fi)
  await checkAccess();
  // Start auto-refreshing cart
  startCartPolling();
});

onUnmounted(() => {
  if (khqrPollInterval) clearInterval(khqrPollInterval);
  if (cartPollInterval) clearInterval(cartPollInterval);
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

      <!-- Partial Dashboard -->
      <div v-if="isPartialPayment" class="space-y-6 mb-8">
        <div
          class="bg-amber-50 p-6 rounded-[32px] border border-amber-100 text-center space-y-4 shadow-sm"
        >
          <div
            class="w-16 h-16 bg-amber-100 text-amber-600 rounded-full flex items-center justify-center mx-auto"
          >
            <svg
              class="w-8 h-8"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
          </div>

          <div>
            <h2 class="text-xl font-black text-gray-900">Partial Payment</h2>
            <p class="text-sm text-gray-600">
              You have paid
              <span class="font-bold text-green-600"
                >${{
                  Number(partialOrder.total_amount - remainingAmount).toFixed(2)
                }}</span
              >
              of the total amount.
            </p>
          </div>

          <div class="bg-white p-4 rounded-2xl border border-gray-100">
            <p
              class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1"
            >
              Remaining Balance
            </p>
            <p class="text-4xl font-black text-gray-900">
              ${{ Number(remainingAmount).toFixed(2) }}
            </p>
          </div>

          <button
            @click="checkout('khqr')"
            class="w-full py-3 bg-[#E61F25] rounded-xl font-bold text-white shadow-lg shadow-red-500/30 hover:bg-red-600 transition-all active:scale-95 flex items-center justify-center gap-2"
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
                d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
              />
            </svg>
            Pay Remaining Balance
          </button>
        </div>
      </div>

      <!-- Normal Cart List -->
      <div
        v-if="cartStore.items.length > 0"
        v-auto-animate
        class="space-y-4 mb-8"
      >
        <div
          v-if="isPartialPayment"
          class="bg-blue-50 text-blue-700 px-4 py-3 rounded-2xl text-sm font-bold flex items-center gap-2"
        >
          <svg
            class="w-5 h-5 flex-shrink-0"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
            />
          </svg>
          Items in cart will be available after paying the balance.
        </div>
        <div
          v-for="item in cartStore.items"
          :key="item.id"
          class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4 relative group"
          :class="{ 'opacity-60 grayscale-[0.5]': isPartialPayment }"
        >
          <button
            @click="deleteItem(item.id)"
            class="absolute -top-2 -right-2 bg-white text-red-500 rounded-full p-2 shadow-md border border-gray-100 transition-colors hover:bg-red-50 z-10"
            :disabled="isPartialPayment"
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
                class="w-8 h-8 rounded-full bg-gray-100 flex items-center justify-center text-gray-600 hover:bg-gray-200 active:scale-95 transition-all"
                :disabled="isSubmitting"
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
                    d="M20 12H4"
                  />
                </svg>
              </button>

              <span
                class="text-base font-bold text-gray-900 min-w-[1.5rem] text-center"
                >{{ item.quantity }}</span
              >

              <button
                @click="updateItemQuantity(item, item.quantity + 1)"
                class="w-8 h-8 rounded-full bg-primary-50 flex items-center justify-center text-primary-600 hover:bg-primary-100 active:scale-95 transition-all"
                :disabled="isSubmitting"
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
        <h2 class="text-xl font-bold text-gray-900 mb-2">Cart is empty</h2>
        <p class="text-gray-500 mb-6">
          Looks like you haven't added anything yet.
        </p>
        <button
          @click="router.push(`/menu/${sessionStore.shopSlug}`)"
          class="px-6 py-3 bg-primary-600 text-white rounded-xl font-bold hover:bg-primary-700 transition-colors"
        >
          Go to Menu
        </button>
      </div>

      <!-- Payment Actions (Only show for Normal Checkout) -->
      <div
        v-if="!isPartialPayment && cartStore.items.length > 0"
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
            Pay with KHQR
          </button>
          <button
            v-if="sessionStore.cashPaymentAllowed"
            @click="checkout('cash')"
            :disabled="isSubmitting"
            class="w-full py-3 bg-gray-100 rounded-2xl font-bold text-gray-900 hover:bg-gray-200 transition-all active:scale-[0.98]"
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
      ></div>
      <div
        class="relative bg-white rounded-[32px] w-full max-w-sm overflow-hidden shadow-2xl animate-scale-in"
      >
        <!-- Header with Notch -->
        <div
          class="bg-[#E61F25] p-6 pb-8 text-center text-white relative rounded-t-[32px]"
        >
          <!-- Close Button (Re-added) -->
          <button
            @click="closeKhqrModal"
            class="absolute right-4 top-4 p-2 bg-black/10 rounded-full hover:bg-black/20 z-10 transition-colors"
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

          <!-- KHQR Text Logo -->
          <div class="flex justify-center">
            <h2 class="text-3xl font-black tracking-widest font-sans">KHQR</h2>
          </div>
        </div>

        <div
          class="px-8 pb-8 pt-6 flex flex-col items-center bg-white rounded-b-[32px]"
        >
          <!-- Merchant & Amount Info (Reordered) -->
          <div class="w-full mb-6">
            <div class="flex flex-col text-left">
              <p class="text-sm font-medium text-gray-500 mb-2">
                {{ sessionStore.shopName || "Coffee Shop" }}
              </p>

              <div class="flex items-baseline gap-2 mb-6">
                <span class="text-4xl font-bold text-gray-900">{{
                  Number(
                    isPartialPayment ? remainingAmount : cartStore.total,
                  ).toLocaleString("en-US", { minimumFractionDigits: 2 })
                }}</span>
                <span class="text-xl font-medium text-gray-500">USD</span>
              </div>

              <div
                class="border-b-2 border-dashed border-gray-200 w-full mb-6"
              ></div>
            </div>

            <!-- QR Code Area (Now below divider) -->
            <div class="relative flex justify-center">
              <div
                v-if="khqrLoading"
                class="w-64 h-64 flex items-center justify-center bg-gray-50 rounded-2xl"
              >
                <div
                  class="animate-spin rounded-full h-12 w-12 border-4 border-red-500 border-t-transparent"
                ></div>
              </div>

              <div v-else-if="khqrString" class="bg-white">
                <QrcodeVue
                  id="khqr-canvas"
                  :value="khqrString"
                  :size="240"
                  level="H"
                  render-as="svg"
                  class="mx-auto"
                />

                <!-- Center Logo Overlay -->
                <div
                  class="absolute inset-0 flex items-center justify-center pointer-events-none"
                >
                  <div class="bg-white p-1.5 rounded-full">
                    <div
                      class="w-9 h-9 bg-black rounded-full flex items-center justify-center text-white font-bold text-sm"
                    >
                      $
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Share Button -->
          <button
            @click="shareQrCode"
            class="flex items-center gap-2 px-6 py-3 bg-gray-50 text-gray-900 rounded-xl font-bold text-sm hover:bg-gray-100 transition-colors w-full justify-center"
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
                d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
              />
            </svg>
            Save Image
          </button>

          <div
            class="flex items-center gap-2 text-xs font-bold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-full"
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
