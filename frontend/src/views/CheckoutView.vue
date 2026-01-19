<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import { useCartStore } from "@/stores/cart";
import { useSessionStore } from "@/stores/session";
import { guestApi } from "@/api";
import { toast } from "vue-sonner";

const router = useRouter();
const cartStore = useCartStore();
const sessionStore = useSessionStore();
const isSubmitting = ref(false);

async function checkout(paymentMethod: "cash" | "khqr") {
  if (cartStore.items.length === 0) return;
  if (!sessionStore.sessionToken) return;

  isSubmitting.value = true;
  const loadingToast = toast.loading("Processing your order...");

  try {
    const response = await guestApi.checkout({
      session_token: sessionStore.sessionToken,
      payment_method: paymentMethod,
    });

    if (response.data.success) {
      toast.dismiss(loadingToast);
      toast.success("Order placed successfully!");

      const orderId = response.data.order.id;
      cartStore.clearCart();

      if (paymentMethod === "khqr") {
        router.push(`/payment/${orderId}`);
      } else {
        router.push(`/success/${orderId}`);
      }
    } else {
      throw new Error(response.data.error || "Checkout failed");
    }
  } catch (error: any) {
    toast.dismiss(loadingToast);
    toast.error("Checkout Failed", {
      description: error.response?.data?.error || error.message,
    });
    console.error("Checkout error:", error);
  } finally {
    isSubmitting.value = false;
  }
}
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
        class="bg-gray-900 text-white p-8 rounded-[40px] shadow-2xl space-y-6"
      >
        <div class="space-y-3">
          <div class="flex justify-between text-gray-400">
            <span>Subtotal</span>
            <span>${{ Number(cartStore.total).toFixed(2) }}</span>
          </div>
          <div class="flex justify-between text-gray-400">
            <span>Tax (Included)</span>
            <span>$0.00</span>
          </div>
          <div
            class="pt-3 border-t border-gray-800 flex justify-between text-2xl font-bold"
          >
            <span>Total</span>
            <span>${{ Number(cartStore.total).toFixed(2) }}</span>
          </div>
        </div>

        <div class="space-y-3 pt-4">
          <button
            @click="checkout('khqr')"
            class="w-full py-5 bg-orange-600 rounded-2xl font-bold text-white shadow-lg shadow-orange-900/40 hover:bg-orange-500 transition-all active:scale-[0.98]"
          >
            Pay with KHQR
          </button>
          <button
            @click="checkout('cash')"
            class="w-full py-5 bg-white/10 rounded-2xl font-bold text-white hover:bg-white/20 transition-all active:scale-[0.98]"
          >
            Pay with Cash
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
