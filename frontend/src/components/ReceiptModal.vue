<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  isOpen: boolean;
  orderItems: any[];
  total: number;
  orderNumber?: string;
  shopName?: string;
  date?: string;
  cashReceived?: number;
  change?: number;
}>();

const emit = defineEmits(["close", "print"]);

const formattedDate = computed(() => {
  return props.date || new Date().toLocaleString();
});

function handlePrint() {
  window.print();
  emit("print");
}
</script>

<template>
  <div
    v-if="isOpen"
    class="fixed inset-0 z-50 flex items-center justify-center p-4 print:p-0"
  >
    <!-- Backdrop (Hide in print) -->
    <div
      @click="$emit('close')"
      class="absolute inset-0 bg-black/60 backdrop-blur-sm print:hidden"
    ></div>

    <!-- Receipt Modal -->
    <div
      class="relative bg-app-surface w-full max-w-sm rounded-lg shadow-2xl overflow-hidden print:shadow-none print:w-full print:max-w-none border border-app-border"
    >
      <!-- Print Actions (Hide in print) -->
      <div
        class="bg-app-bg p-4 flex justify-between items-center print:hidden border-b border-app-border"
      >
        <h3 class="font-bold text-app-text">Receipt Preview</h3>
        <div class="flex gap-2">
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-app-muted hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition-colors"
          >
            Close
          </button>
          <button
            @click="handlePrint"
            class="px-4 py-2 bg-primary-600 text-white hover:bg-primary-700 rounded-lg text-sm font-bold flex items-center gap-2"
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
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
              ></path>
            </svg>
            Print
          </button>
        </div>
      </div>

      <!-- Actual Receipt Content -->
      <div
        class="p-6 bg-white text-gray-900 font-mono text-sm leading-relaxed receipt-content"
      >
        <div class="text-center mb-6">
          <h1 class="text-xl font-bold uppercase mb-2">
            {{ shopName || "Lucky Cafe" }}
          </h1>
          <p class="text-xs text-gray-500">Phnom Penh, Cambodia</p>
          <p class="text-xs text-gray-500">Tel: 012 345 678</p>
        </div>

        <div class="border-b border-dashed border-gray-300 pb-4 mb-4">
          <div class="flex justify-between">
            <span>Order #:</span>
            <span class="font-bold">{{ orderNumber || "PENDING" }}</span>
          </div>
          <div class="flex justify-between">
            <span>Date:</span>
            <span>{{ formattedDate }}</span>
          </div>
          <div class="flex justify-between">
            <span>Cashier:</span>
            <span>Staff</span>
          </div>
        </div>

        <div class="mb-4">
          <table class="w-full text-left">
            <thead>
              <tr class="border-b border-gray-300">
                <th class="py-1 w-1/2">Item</th>
                <th class="py-1 text-center">Qty</th>
                <th class="py-1 text-right">Amt</th>
              </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
              <tr v-for="item in orderItems" :key="item.id">
                <td class="py-2 pr-2 align-top">
                  <div class="font-bold">{{ item.product.name }}</div>
                  <div v-if="item.variant" class="text-xs text-gray-500">
                    {{ item.variant.name }}: {{ item.variant.option_name }}
                  </div>
                  <div
                    v-if="item.options && item.options.length > 0"
                    class="text-xs text-gray-500 mt-0.5"
                  >
                    <div v-for="(opt, idx) in item.options" :key="idx">
                      + {{ opt.group_name }}: {{ opt.option_name }}
                    </div>
                  </div>
                </td>
                <td class="py-2 text-center align-top">{{ item.quantity }}</td>
                <td class="py-2 text-right align-top">
                  {{
                    (() => {
                      let price = Number(item.product.price);
                      if (item.variant)
                        price += Number(item.variant.extra_price || 0);
                      if (item.options) {
                        item.options.forEach((opt: any) => {
                          price += Number(opt.extra_price || 0);
                        });
                      }
                      return "$" + (price * item.quantity).toFixed(2);
                    })()
                  }}
                </td>
              </tr>
            </tbody>
          </table>
        </div>

        <div class="border-t border-dashed border-gray-300 pt-4 space-y-1">
          <div class="flex justify-between text-base font-bold">
            <span>TOTAL</span>
            <span>${{ Number(total).toFixed(2) }}</span>
          </div>
          <div
            v-if="cashReceived"
            class="flex justify-between text-xs text-gray-600"
          >
            <span>Cash Received</span>
            <span>${{ Number(cashReceived).toFixed(2) }}</span>
          </div>
          <div
            v-if="change !== undefined"
            class="flex justify-between text-xs text-gray-600"
          >
            <span>Change</span>
            <span>${{ Number(change).toFixed(2) }}</span>
          </div>
        </div>

        <div class="mt-8 text-center text-xs">
          <p>Thank you for visiting {{ shopName || "Lucky Cafe" }}!</p>
          <p class="mt-2">Free WiFi: Lucky-Guest / pwd: 12345678</p>
          <div class="mt-4 border-t pt-2 text-[10px] text-gray-400">
            Powered by Coffee-POS SaaS
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
@media print {
  @page {
    margin: 0;
    size: 80mm 297mm;
  }

  /* Thermal paper size approx */
  body * {
    visibility: hidden;
  }

  .receipt-content,
  .receipt-content * {
    visibility: visible;
  }

  .receipt-content {
    position: absolute;
    left: 0;
    top: 0;
    width: 100%;
    margin: 0;
    padding: 10px;
    box-shadow: none;
  }
}
</style>
