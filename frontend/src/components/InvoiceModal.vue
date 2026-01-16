<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  show: boolean;
  order: any;
}>();

const emit = defineEmits(["close"]);

const subtotal = computed(() => {
  return (
    props.order?.items.reduce(
      (sum: number, item: any) => sum + Number(item.subtotal),
      0
    ) || 0
  );
});

function formatTotal(order: any) {
  if (order.payment_currency === "KHR") {
    return new Intl.NumberFormat("en-US").format(order.total_amount) + " ៛";
  }
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(order.total_amount);
}

function print() {
  window.print();
}
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"
    @click.self="$emit('close')"
  >
    <div
      class="bg-white rounded-2xl w-full max-w-sm overflow-hidden shadow-2xl animate-scale-in text-black"
    >
      <!-- Printable Area -->
      <div id="receipt-area" class="p-6">
        <div class="text-center mb-6">
          <h2 class="text-2xl font-bold uppercase tracking-wide">Coffee POS</h2>
          <p class="text-xs text-gray-500 mt-1">
            {{ order.shop?.name || "Shop Name" }}
          </p>
          <p class="text-xs text-gray-400 mt-4">
            {{ new Date(order.created_at).toLocaleString() }}
          </p>
          <p class="text-sm font-mono mt-1">#{{ order.order_number }}</p>
        </div>

        <div
          class="border-t border-b border-dashed border-gray-300 py-4 mb-4 space-y-2"
        >
          <div
            v-for="item in order.items"
            :key="item.id"
            class="flex justify-between text-sm"
          >
            <div>
              <span class="font-bold">{{ item.quantity }}x</span>
              {{ item.product?.name }}
              <div v-if="item.variant" class="text-xs text-gray-500 pl-4">
                {{ item.variant.name }}
              </div>
              <div
                v-if="item.options && item.options.length > 0"
                class="text-xs text-gray-500 pl-4"
              >
                <div v-for="(opt, idx) in item.options" :key="idx">
                  + {{ opt.group_name }}: {{ opt.option_name }}
                </div>
              </div>
            </div>
            <div class="font-mono">{{ Number(item.subtotal).toFixed(2) }}</div>
          </div>
        </div>

        <div class="space-y-2 font-mono text-sm">
          <div class="flex justify-between">
            <span>Subtotal</span>
            <span>${{ subtotal.toFixed(2) }}</span>
          </div>
          <div
            class="flex justify-between font-bold text-lg pt-2 border-t border-gray-900"
          >
            <span>Total</span>
            <span>{{ formatTotal(order) }}</span>
          </div>
          <div class="flex justify-between text-xs text-gray-500 pt-1">
            <span>Payment</span>
            <span class="uppercase">{{ order.payment_method }}</span>
          </div>
        </div>

        <div class="text-center mt-8 text-xs text-gray-400">
          <p>Thank you for your visit!</p>
          <p>Please come again.</p>
        </div>
      </div>

      <!-- Actions -->
      <div
        class="bg-gray-50 p-4 border-t border-gray-100 flex gap-4 print:hidden"
      >
        <button
          @click="$emit('close')"
          class="flex-1 py-2 text-gray-500 hover:text-gray-900 font-bold transition-colors"
        >
          Close
        </button>
        <button
          @click="print"
          class="flex-1 py-2 bg-black text-white rounded-xl font-bold hover:bg-gray-800 transition-colors flex items-center justify-center gap-2"
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
              d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
            />
          </svg>
          Print Receipt
        </button>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  body * {
    visibility: hidden;
  }

  #receipt-area,
  #receipt-area * {
    visibility: visible;
  }

  #receipt-area {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 0;
  }
}
</style>
