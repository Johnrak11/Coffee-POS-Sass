<script setup lang="ts">
import { onMounted, ref, onUnmounted } from "vue";
import { useRoute, useRouter } from "vue-router";
import { guestApi } from "@/services/api";
import { toast } from "vue-sonner";
import QrcodeVue from "qrcode.vue";

const route = useRoute();
const router = useRouter();
const orderId = route.params.orderId as string;
const processing = ref(false);
const order = ref<any>(null);
const loading = ref(true);
let pollInterval: any = null;

async function fetchOrderDetails() {
  try {
    const response = await guestApi.getOrderStatus(Number(orderId));
    order.value = response.data;

    if (order.value.payment_status === "paid") {
      handleSuccess();
    }
  } catch (error) {
    console.error("Failed to fetch order details:", error);
    toast.error("Could not load order details");
  } finally {
    loading.value = false;
  }
}

function handleSuccess() {
  if (pollInterval) clearInterval(pollInterval);
  processing.value = true;
  setTimeout(() => {
    router.push(`/success/${orderId}`);
  }, 1500);
}

onMounted(async () => {
  await fetchOrderDetails();

  // Poll every 5 seconds for payment status
  pollInterval = setInterval(fetchOrderDetails, 5000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});

async function mockSuccess() {
  // In a real scenario, this would be triggered by a webhook or polling
  // For demo, we just simulate the navigation
  handleSuccess();
}

function formatPrice(price: number) {
  return Number(price).toFixed(2);
}
</script>

<template>
  <div class="min-h-screen bg-[#F0F2F5] flex flex-col items-center p-6 pt-12">
    <!-- Bakong Branding Header -->
    <div class="w-full max-w-md flex justify-between items-center mb-10">
      <div class="flex items-center gap-2">
        <div
          class="w-10 h-10 bg-red-600 rounded-xl flex items-center justify-center text-white font-bold text-xl shadow-lg shadow-red-200"
        >
          B
        </div>
        <h1 class="text-xl font-bold text-gray-800">Bakong Pay</h1>
      </div>
      <div
        class="bg-gray-200/50 px-3 py-1 rounded-full text-[10px] font-bold text-gray-500 tracking-wider"
      >
        SECURE PAYMENT
      </div>
    </div>

    <div
      class="bg-white p-10 rounded-[48px] shadow-2xl shadow-gray-200 max-w-sm w-full text-center relative overflow-hidden"
    >
      <!-- Success Overlay -->
      <div
        v-if="processing"
        class="absolute inset-0 bg-white/90 backdrop-blur-sm z-50 flex flex-col items-center justify-center p-8 animate-fade-in"
      >
        <div
          class="w-20 h-20 bg-green-100 text-green-600 rounded-full flex items-center justify-center mb-4"
        >
          <svg
            class="w-10 h-10 animate-bounce"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="3"
              d="M5 13l4 4L19 7"
            ></path>
          </svg>
        </div>
        <h2 class="text-xl font-bold text-gray-900">Processing...</h2>
        <p class="text-gray-500 text-sm mt-2">
          Verifying your transaction with the bank
        </p>
      </div>

      <div class="mb-8">
        <p
          class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1"
        >
          Total Amount
        </p>
        <h2
          v-if="loading"
          class="text-4xl font-extrabold text-gray-900 animate-pulse"
        >
          ...
        </h2>
        <h2 v-else class="text-4xl font-extrabold text-gray-900">
          ${{ order?.amount ? formatPrice(order.amount) : "0.00" }}
        </h2>
      </div>

      <div class="relative group">
        <!-- QR Code Frame -->
        <div
          class="bg-white p-6 rounded-[40px] border-2 border-dashed border-gray-100 mb-8 inline-block shadow-inner group-hover:border-red-600 transition-all duration-500"
        >
          <div
            class="w-56 h-56 bg-white relative flex items-center justify-center"
          >
            <!-- Corner SVG for focus feel -->
            <div class="absolute inset-0 flex flex-col justify-between -m-2">
              <div class="flex justify-between">
                <div
                  class="w-5 h-5 border-t-4 border-l-4 border-red-600 rounded-tl-xl"
                ></div>
                <div
                  class="w-5 h-5 border-t-4 border-r-4 border-red-600 rounded-tr-xl"
                ></div>
              </div>
              <div class="flex justify-between">
                <div
                  class="w-5 h-5 border-b-4 border-l-4 border-red-600 rounded-bl-xl"
                ></div>
                <div
                  class="w-5 h-5 border-b-4 border-r-4 border-red-600 rounded-br-xl"
                ></div>
              </div>
            </div>

            <!-- Real QR Code -->
            <div
              v-if="loading"
              class="w-full h-full bg-gray-50 animate-pulse rounded-2xl flex items-center justify-center"
            >
              <svg
                class="w-12 h-12 text-gray-200"
                fill="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  d="M3 3h8v8H3V3zm2 2v4h4V5H5zM3 13h8v8H3v-8zm2 2v4h4v-4H5zM13 3h8v8h-8V3zm2 2v4h4V5h-4zM13 13h2v2h-2v-2zm4 0h2v2h-2v-2zm-4 4h2v2h-2v-2zm4 0h2v2h-2v-2zm-2-2h2v2h-2v-2z"
                />
              </svg>
            </div>
            <QrcodeVue
              v-else-if="order?.khqr_string"
              :value="order.khqr_string"
              :size="200"
              level="H"
              class="rounded-xl overflow-hidden"
            />
            <div v-else class="text-xs text-gray-300 italic">
              QR unavailable
            </div>
          </div>
        </div>
      </div>

      <div class="space-y-6">
        <div class="flex items-center justify-center gap-3">
          <div class="w-2 h-2 bg-red-600 rounded-full animate-ping"></div>
          <span
            class="text-xs font-bold text-gray-400 uppercase tracking-widest"
            >Awaiting transfer...</span
          >
        </div>

        <button
          @click="mockSuccess"
          class="w-full py-4 bg-gray-50 border border-gray-100 rounded-2xl text-xs font-bold text-gray-500 hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all uppercase tracking-widest"
        >
          Simulate Payment Success
        </button>
      </div>
    </div>

    <button
      @click="router.back()"
      class="mt-8 text-gray-400 text-sm font-medium hover:text-gray-600 transition-colors flex items-center gap-2"
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
          d="M10 19l-7-7 7-7"
        ></path>
      </svg>
      Cancel & Return
    </button>
  </div>
</template>

<style scoped>
.animate-fade-in {
  animation: fadeIn 0.3s ease-out;
}
@keyframes fadeIn {
  from {
    opacity: 0;
  }
  to {
    opacity: 1;
  }
}
</style>
