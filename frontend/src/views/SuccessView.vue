<script setup lang="ts">
import { onMounted, ref, computed, watch, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useSessionStore } from "@/stores/session";
import { guestApi } from "@/api";
import ReceiptModal from "@/components/ReceiptModal.vue";
import ConfettiAnimation from "@/components/order-tracking/ConfettiAnimation.vue";
import PulseIcon from "@/components/order-tracking/PulseIcon.vue";
import StatusTimeline from "@/components/order-tracking/StatusTimeline.vue";

const route = useRoute();
const router = useRouter();
const { t } = useI18n();
const sessionStore = useSessionStore();
const orderId = route.params.orderId as string;
const orderDetails = ref<any>(null);
const loading = ref(true);
const showReceipt = ref(false);
const showConfetti = ref(false);
const previousStatus = ref<string>("");

// Polling
let pollInterval: any = null;
const POLL_INTERVALS = {
  queue: 5000, // 5s when in queue
  preparing: 3000, // 3s when preparing
  served: 0, // stop when served
};

const currentStage = computed(() => {
  if (!orderDetails.value) return "queue";

  const payment = orderDetails.value.payment_status;
  const fulfillment = orderDetails.value.fulfillment_status;

  // CRITICAL: Check for rejection first
  if (payment === "rejected" || payment === "failed") {
    return "rejected";
  }

  // Both CASH and KHQR: By the time user is on success page, order is created
  // - CASH: Order created, payment on pickup
  // - KHQR: Payment already verified, order created
  // So we can directly show queue/preparing/served based on fulfillment status

  if (fulfillment === "queue") return "queue";
  if (fulfillment === "preparing") return "preparing";
  if (fulfillment === "served") return "served";

  return "queue";
});

const receiptData = computed(() => {
  if (!orderDetails.value) return null;
  const o = orderDetails.value;
  return {
    items: o.items || [],
    total: Number(o.total_amount),
    cashReceived: Number(o.received_amount || o.total_amount),
    change: Number(o.change || 0),
    orderNumber: o.order_number,
    queueNumber: o.queue_number,
    shopName: o.shop?.name || sessionStore.shopName,
    date: new Date(o.created_at).toLocaleString(),
    currency: o.payment_currency || "USD",
    shopAddress: o.shop?.address,
    shopPhone: o.shop?.phone,
    wifiSsid: o.shop?.wifi_ssid,
    wifiPassword: o.shop?.wifi_password,
    discountAmount: Number(o.discount_amount || 0),
  };
});

async function fetchOrderStatus() {
  try {
    const response = await guestApi.getOrderStatus(Number(orderId));
    orderDetails.value = response.data;

    // Safeguard: Redirect back if partial
    if (orderDetails.value.payment_status === "partial") {
      router.replace("/checkout");
    }
  } catch (error) {
    console.error("Failed to load order info:", error);
  }
}

function startPolling() {
  stopPolling();

  const interval =
    POLL_INTERVALS[currentStage.value as keyof typeof POLL_INTERVALS] ?? 5000;

  if (interval > 0) {
    pollInterval = setInterval(fetchOrderStatus, interval);
  }
}

function stopPolling() {
  if (pollInterval) {
    clearInterval(pollInterval);
    pollInterval = null;
  }
}

// Watch for stage changes to trigger animations
watch(currentStage, (newStage, oldStage) => {
  if (oldStage && newStage !== oldStage) {
    previousStatus.value = oldStage;

    // Trigger confetti only when order is served
    if (newStage === "served") {
      triggerConfetti();
    }

    // Trigger vibration if supported
    if ("vibrate" in navigator) {
      navigator.vibrate(200);
    }

    // Restart polling with new interval
    startPolling();
  }
});

function triggerConfetti() {
  showConfetti.value = true;
  setTimeout(() => {
    showConfetti.value = false;
  }, 3000);
}

onMounted(async () => {
  await fetchOrderStatus();
  loading.value = false;

  // Only start polling if order is not already served
  if (currentStage.value !== "served") {
    startPolling();
  }
});

onUnmounted(() => {
  stopPolling();
});

function handleDownloadReceipt() {
  showReceipt.value = true;
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 p-6">
    <!-- Confetti Effect -->
    <ConfettiAnimation v-if="showConfetti" />

    <div class="max-w-md w-full">
      <div
        class="bg-white p-8 rounded-[40px] shadow-2xl shadow-gray-200 text-center relative overflow-hidden"
      >
        <!-- Status Timeline (hide for rejected) -->
        <StatusTimeline
          v-if="currentStage !== 'rejected'"
          :current-stage="currentStage"
          class="mb-8"
        />

        <!-- Stage Content with Transition -->
        <transition name="stage-fade" mode="out-in">
          <!-- REJECTED -->
          <div v-if="currentStage === 'rejected'" class="py-4">
            <!-- Large X Icon -->
            <div
              class="inline-flex items-center justify-center w-28 h-28 bg-red-100 rounded-full mb-6"
            >
              <svg
                class="w-14 h-14 text-red-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="3"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </div>

            <h1 class="text-3xl font-black text-red-600 mb-2">
              {{ $t("tracking.orderRejected") }}
            </h1>
            <p class="text-gray-600 font-medium mb-6">
              {{ $t("tracking.sorryCouldNotProcess") }}
            </p>

            <!-- Order Number -->
            <div class="bg-gray-50 rounded-2xl p-4 mb-6 inline-block">
              <div class="flex items-center gap-2">
                <svg
                  class="w-4 h-4 text-gray-400"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                  />
                </svg>
                <span class="text-xs font-mono font-medium text-gray-600">{{
                  orderDetails?.order_number
                }}</span>
              </div>
            </div>

            <!-- Rejection Notice -->
            <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-5 mb-6">
              <p class="text-sm font-bold text-red-900 mb-1">
                📋 {{ $t("tracking.whatShouldYouDo") }}
              </p>
              <p class="text-xs text-red-800 leading-relaxed">
                {{ $t("tracking.contactStaffOrReturn") }}
              </p>
            </div>

            <!-- Button -->
            <button
              @click="$router.push(`/menu/${sessionStore.shopSlug}`)"
              class="w-full py-4 bg-gray-900 text-white font-bold text-base rounded-xl hover:bg-gray-800 transition-colors active:scale-[0.98] shadow-lg"
            >
              {{ $t("tracking.backToMenu") }}
            </button>
          </div>

          <!-- In Queue -->
          <div v-else-if="currentStage === 'queue'" class="py-4">
            <!-- Success Checkmark -->
            <div class="relative mb-6">
              <div
                class="w-28 h-28 bg-gradient-to-br from-green-400 to-green-600 rounded-full mx-auto flex items-center justify-center shadow-2xl shadow-green-300/50 animate-scale-bounce relative"
              >
                <svg
                  class="w-16 h-16 text-white drop-shadow-lg"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="3"
                    d="M5 13l4 4L19 7"
                  />
                </svg>
                <!-- Animated Ring -->
                <div
                  class="absolute inset-0 rounded-full border-4 border-green-300 animate-ping opacity-75"
                ></div>
              </div>
            </div>

            <h1 class="text-3xl font-black text-gray-900 mb-1">
              {{ $t("tracking.orderConfirmed") }}
            </h1>
            <p class="text-gray-500 font-semibold mb-6 text-base">
              {{ $t("tracking.preparingOrder") }}
            </p>

            <!-- Queue Number Card - More Prominent -->
            <div
              class="bg-gradient-to-br from-gray-50 to-white rounded-3xl p-6 shadow-lg border-2 border-gray-100 mb-6 relative overflow-hidden"
            >
              <!-- Decorative gradient bar -->
              <div
                class="absolute top-0 left-0 w-full h-3 bg-gradient-to-r from-green-400 via-emerald-500 to-green-600"
              ></div>

              <div class="text-center pt-2">
                <span
                  class="text-xs font-black text-gray-400 uppercase tracking-[0.2em] block mb-3"
                  >{{ $t("tracking.yourQueueNumber") }}</span
                >
                <div
                  class="text-[120px] font-black bg-gradient-to-br from-gray-800 to-gray-600 bg-clip-text text-transparent leading-none mb-3"
                >
                  {{ orderDetails?.queue_number || "-" }}
                </div>

                <div
                  class="inline-flex items-center gap-2 bg-gray-100 px-5 py-2.5 rounded-full"
                >
                  <svg
                    class="w-4 h-4 text-gray-500"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                    />
                  </svg>
                  <span class="text-sm font-mono font-bold text-gray-700">{{
                    orderDetails?.order_number
                  }}</span>
                </div>
              </div>
            </div>

            <!-- Cash Payment Reminder -->
            <div
              v-if="orderDetails?.payment_method === 'cash'"
              class="mb-6 bg-amber-50 border-2 border-amber-300 rounded-3xl p-5 shadow-sm"
            >
              <div class="flex items-start gap-4">
                <div class="text-3xl leading-none pt-0.5">💵</div>
                <div class="flex-1 space-y-1">
                  <p class="font-black text-base text-amber-900 leading-tight">
                    {{ $t("tracking.paymentOnPickup") }}
                  </p>
                  <p class="text-sm text-amber-700 font-medium leading-relaxed">
                    {{ $t("tracking.payAtCounterWhenCollecting") }}
                  </p>
                </div>
              </div>
            </div>
          </div>

          <!-- Preparing -->
          <div v-else-if="currentStage === 'preparing'" class="py-8">
            <div class="relative mb-6">
              <PulseIcon color="#3B82F6" size="lg" speed="fast" class="mx-auto">
                <span class="text-4xl">👨‍🍳</span>
              </PulseIcon>
              <!-- Steam Animation -->
              <div class="absolute -top-2 left-1/2 -translate-x-1/2">
                <div
                  class="w-4 h-8 bg-blue-200 opacity-50 rounded-full animate-steam"
                ></div>
              </div>
            </div>

            <h1 class="text-3xl font-black text-gray-900 mb-2">
              {{ $t("tracking.currentlyPreparing") }}!
              {{ $t("tracking.currentlyPreparingLast") }}
            </h1>
            <p class="text-gray-500 font-medium mb-8">
              Queue #{{ orderDetails?.queue_number }}
            </p>

            <!-- Progress Bar -->
            <div
              class="w-full bg-gray-200 rounded-full h-3 mb-6 overflow-hidden"
            >
              <div class="bg-blue-500 h-3 rounded-full animate-progress"></div>
            </div>
          </div>

          <!-- Served / Ready -->
          <div v-else-if="currentStage === 'served'" class="py-8">
            <div class="relative mb-6">
              <div
                class="w-24 h-24 bg-purple-500 rounded-full mx-auto flex items-center justify-center shadow-lg shadow-purple-200 animate-bounce-slow"
              >
                <span class="text-5xl animate-ring">🔔</span>
              </div>
              <div
                class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-purple-50 rounded-full animate-ping opacity-75"
              ></div>
            </div>

            <h1 class="text-4xl font-black text-gray-900 mb-2">
              {{ $t("tracking.orderReady") }}
            </h1>
            <p class="text-purple-600 font-bold text-xl mb-4">
              Queue #{{ orderDetails?.queue_number }}
            </p>

            <!-- Cash Payment Reminder -->
            <div
              v-if="orderDetails?.payment_method === 'cash'"
              class="mb-4 bg-amber-50 border-2 border-amber-300 rounded-3xl p-5 shadow-sm animate-pulse-slow"
            >
              <div class="flex items-start gap-4">
                <div class="text-3xl leading-none pt-0.5">💰</div>
                <div class="flex-1 space-y-1">
                  <p class="font-black text-base text-amber-900 leading-tight">
                    Ready to Pay!
                  </p>
                  <p class="text-sm text-amber-700 font-bold leading-relaxed">
                    ${{ Number(orderDetails?.total_amount).toFixed(2) }} at
                    counter
                  </p>
                </div>
              </div>
            </div>

            <p class="text-gray-500 font-medium">
              {{ $t("tracking.pickupAtCounter") }}
            </p>
          </div>
        </transition>

        <!-- Action Buttons (hide for rejected) -->
        <div
          class="space-y-3 mt-8"
          v-if="!loading && currentStage !== 'rejected'"
        >
          <button
            v-if="orderDetails?.payment_method !== 'cash'"
            @click="handleDownloadReceipt"
            class="w-full py-4 bg-white border-2 border-gray-100 rounded-2xl font-bold text-gray-700 hover:bg-gray-50 hover:border-gray-200 transition-all active:scale-[0.98] flex items-center justify-center gap-2"
          >
            <svg
              class="w-5 h-5 text-gray-500"
              fill="none"
              viewBox="0 0 24 24"
              stroke="currentColor"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
              />
            </svg>
            {{ $t("tracking.downloadReceipt") }}
          </button>

          <button
            @click="router.push(`/menu/${sessionStore.shopSlug}`)"
            class="w-full py-4 bg-gray-900 rounded-2xl font-bold text-white shadow-lg shadow-gray-900/40 hover:bg-gray-800 transition-all active:scale-[0.98]"
          >
            {{ $t("tracking.orderMore") }}
          </button>
        </div>

        <p
          class="mt-8 text-center text-gray-300 text-xs font-medium uppercase tracking-wider"
        >
          {{ sessionStore.shopName }}
        </p>
      </div>
    </div>

    <!-- Receipt Modal -->
    <ReceiptModal
      v-if="showReceipt && receiptData"
      :show="showReceipt"
      :receipt-data="receiptData"
      @close="showReceipt = false"
    />
  </div>
</template>

<style scoped>
@keyframes scaleBounce {
  0% {
    transform: scale(0);
  }
  70% {
    transform: scale(1.1);
  }
  100% {
    transform: scale(1);
  }
}

.animate-scale-bounce {
  animation: scaleBounce 0.6s cubic-bezier(0.34, 1.56, 0.64, 1) forwards;
}

@keyframes steam {
  0% {
    transform: translateY(0) scale(1);
    opacity: 0.5;
  }
  100% {
    transform: translateY(-30px) scale(1.5);
    opacity: 0;
  }
}

.animate-steam {
  animation: steam 2s ease-in-out infinite;
}

@keyframes progress {
  0% {
    width: 0%;
  }
  100% {
    width: 75%;
  }
}

.animate-progress {
  animation: progress 3s ease-in-out infinite;
}

@keyframes ring {
  0%,
  100% {
    transform: rotate(-15deg);
  }
  50% {
    transform: rotate(15deg);
  }
}

.animate-ring {
  display: inline-block;
  animation: ring 0.5s ease-in-out infinite;
}

@keyframes bounce-slow {
  0%,
  100% {
    transform: translateY(0);
  }
  50% {
    transform: translateY(-10px);
  }
}

/* Stage Transition Animations - Enhanced */
.stage-fade-enter-active {
  transition: all 0.6s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.stage-fade-leave-active {
  transition: all 0.4s cubic-bezier(0.4, 0, 0.6, 1);
}

.stage-fade-enter-from {
  opacity: 0;
  transform: translateY(30px) scale(0.95);
}

.stage-fade-leave-to {
  opacity: 0;
  transform: translateY(-30px) scale(1.05);
}

.stage-fade-enter-to,
.stage-fade-leave-from {
  opacity: 1;
  transform: translateY(0) scale(1);
}
</style>
