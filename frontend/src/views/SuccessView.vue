<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useSessionStore } from "@/stores/session";
import { guestApi } from "@/api";
import ReceiptModal from "@/components/ReceiptModal.vue";

const route = useRoute();
const router = useRouter();
const sessionStore = useSessionStore();
const orderId = route.params.orderId as string;
const orderDetails = ref<any>(null);
const loading = ref(true);
const showReceipt = ref(false);

const receiptData = computed(() => {
  if (!orderDetails.value) return null;
  const o = orderDetails.value;
  return {
    items: o.items || [],
    total: Number(o.total_amount),
    cashReceived: Number(o.received_amount || o.total_amount),
    change: Number(o.change || 0),
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
  };
});

onMounted(async () => {
  try {
    const response = await guestApi.getOrderStatus(Number(orderId));
    orderDetails.value = response.data;
  } catch (error) {
    console.error("Failed to load order info:", error);
  } finally {
    loading.value = false;
  }
});

function handleDownloadReceipt() {
  showReceipt.value = true;
  setTimeout(() => {
    // window.print() is handled by the "Print" button inside modal manually now
  }, 100);
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-50 p-6">
    <div class="max-w-md w-full">
      <div
        class="bg-white p-8 rounded-[40px] shadow-2xl shadow-gray-200 text-center relative overflow-hidden"
      >
        <!-- Success Icon -->
        <div class="mb-6 relative">
          <div
            class="w-24 h-24 bg-green-500 rounded-full mx-auto flex items-center justify-center shadow-lg shadow-green-200 relative z-10 animate-scale-bounce"
          >
            <svg
              class="w-12 h-12 text-white"
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
          </div>
          <!-- Ripple effect -->
          <div
            class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-32 h-32 bg-green-50 rounded-full animate-ping opacity-75"
          ></div>
        </div>

        <h1 class="text-3xl font-black text-gray-900 mb-2">
          Payment Successful!
        </h1>
        <p class="text-gray-500 font-medium mb-8">
          Your order has been confirmed.
        </p>

        <!-- Queue Number Card (Prominent) -->
        <div
          class="bg-white rounded-3xl p-8 shadow-sm border border-gray-100 flex flex-col items-center justify-center mb-6 relative overflow-hidden"
        >
          <div
            class="absolute top-0 left-0 w-full h-2 bg-gradient-to-r from-success-400 to-green-600"
          ></div>
          <span
            class="text-xs font-bold text-gray-400 uppercase tracking-widest mb-2"
            >Queue Number</span
          >
          <div
            v-if="loading"
            class="h-16 bg-gray-200 animate-pulse rounded-lg w-24 mb-2"
          ></div>
          <div
            v-else
            class="text-8xl font-black text-gray-900 tracking-tighter mb-4"
          >
            {{ orderDetails?.queue_number || "-" }}
          </div>

          <div
            class="flex items-center gap-2 bg-gray-50 px-4 py-2 rounded-full border border-gray-100"
          >
            <span class="text-xs font-bold text-gray-400 uppercase"
              >Order ID</span
            >
            <span class="text-sm font-mono font-bold text-gray-600">{{
              orderDetails?.order_number || `#${orderId}`
            }}</span>
          </div>
        </div>

        <div class="space-y-3">
          <button
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
            Download Receipt
          </button>

          <button
            @click="router.push(`/menu/${sessionStore.shopSlug}`)"
            class="w-full py-4 bg-gray-900 rounded-2xl font-bold text-white shadow-lg shadow-gray-900/40 hover:bg-gray-800 transition-all active:scale-[0.98]"
          >
            Order More
          </button>
        </div>

        <p
          class="mt-8 text-center text-gray-300 text-xs font-medium uppercase tracking-wider"
        >
          {{ sessionStore.shopName }}
        </p>
      </div>
    </div>

    <!-- Receipt Modal (Hidden / For Print) -->
    <ReceiptModal
      v-if="showReceipt && receiptData"
      :show="showReceipt"
      :receipt-data="receiptData"
      @close="showReceipt = false"
    />
  </div>
</template>

<style scoped>
.dashed-border {
  background-image: url("data:image/svg+xml,%3csvg width='100%25' height='100%25' xmlns='http://www.w3.org/2000/svg'%3e%3crect width='100%25' height='100%25' fill='none' rx='24' ry='24' stroke='%23E5E7EBFF' stroke-width='2' stroke-dasharray='10%2c 10' stroke-dashoffset='0' stroke-linecap='square'/%3e%3c/svg%3e");
  border: none;
}

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
</style>
