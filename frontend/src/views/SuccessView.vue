<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useRoute, useRouter } from "vue-router";
import { useSessionStore } from "@/stores/session";
import { guestApi } from "@/services/api";

const route = useRoute();
const router = useRouter();
const sessionStore = useSessionStore();
const orderId = route.params.orderId as string;
const orderDetails = ref<any>(null);
const loading = ref(true);

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
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-[#F8FAFC] p-6">
    <div class="max-w-md w-full">
      <div
        class="bg-white p-10 rounded-[48px] shadow-2xl shadow-gray-200 text-center relative overflow-hidden"
      >
        <!-- Confetti-like background decor -->
        <div
          class="absolute -top-10 -left-10 w-32 h-32 bg-orange-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob"
        ></div>
        <div
          class="absolute -bottom-10 -right-10 w-32 h-32 bg-blue-100 rounded-full mix-blend-multiply filter blur-xl opacity-70 animate-blob animation-delay-2000"
        ></div>

        <!-- Success Celebration Icon -->
        <div class="mb-8 relative">
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
          <!-- Sparkles -->
          <div class="absolute top-0 right-1/4 animate-pulse">
            <svg
              class="w-6 h-6 text-yellow-400"
              fill="currentColor"
              viewBox="0 0 20 20"
            >
              <path
                d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"
              />
            </svg>
          </div>
        </div>

        <h1 class="text-4xl font-black text-gray-900 mb-2">Wonderful!</h1>
        <p class="text-gray-500 font-medium mb-8">
          Your coffee is being prepared.
        </p>

        <div class="bg-gray-50 p-6 rounded-3xl mb-8 border border-gray-100">
          <div
            class="text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-2"
          >
            Order Tracking ID
          </div>
          <div
            v-if="loading"
            class="h-8 bg-gray-200 animate-pulse rounded-lg w-3/4 mx-auto mb-1"
          ></div>
          <div v-else class="text-3xl font-black text-gray-900 tabular-nums">
            {{ orderDetails?.order_number || `#${orderId}` }}
          </div>
        </div>

        <div class="space-y-4 mb-10">
          <div
            class="flex items-center gap-4 text-left p-3 rounded-2xl hover:bg-gray-50 transition-colors"
          >
            <div
              class="w-10 h-10 bg-blue-50 text-blue-600 rounded-xl flex items-center justify-center font-bold"
            >
              1
            </div>
            <div>
              <p class="text-sm font-bold text-gray-800">Order Received</p>
              <p class="text-xs text-gray-400">
                Successfully sent to the counter
              </p>
            </div>
          </div>
          <div
            class="flex items-center gap-4 text-left p-3 rounded-2xl hover:bg-gray-50 transition-colors"
          >
            <div
              class="w-10 h-10 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center font-bold"
            >
              2
            </div>
            <div>
              <p class="text-sm font-bold text-gray-800">Kitchen Queue</p>
              <p class="text-xs text-gray-400">Our baristas are on it!</p>
            </div>
          </div>
        </div>

        <button
          @click="router.push(`/menu/${sessionStore.shopSlug}`)"
          class="w-full py-5 bg-gray-900 rounded-2xl font-bold text-white shadow-xl shadow-gray-200 hover:bg-gray-800 transition-all active:scale-95"
        >
          Back to Menu
        </button>
      </div>

      <p class="mt-8 text-center text-gray-400 text-sm font-medium">
        Thank you for choosing {{ sessionStore.shopName }}
      </p>
    </div>
  </div>
</template>

<style scoped>
@keyframes blob {
  0% {
    transform: scale(1);
  }
  33% {
    transform: scale(1.1) translate(10px, -10px);
  }
  66% {
    transform: scale(0.9) translate(-10px, 10px);
  }
  100% {
    transform: scale(1);
  }
}
.animate-blob {
  animation: blob 7s infinite alternate;
}
.animation-delay-2000 {
  animation-delay: 2s;
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
