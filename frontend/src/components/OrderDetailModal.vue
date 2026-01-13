<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  show: boolean;
  order: any;
}>();

const emit = defineEmits(["close", "reject", "print"]);

const subtotal = computed(() => {
  return (
    props.order?.items.reduce(
      (sum: number, item: any) => sum + item.subtotal,
      0
    ) || 0
  );
});

function formatDate(date: string) {
  return new Date(date).toLocaleString();
}
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
  >
    <!-- Backdrop -->
    <div
      @click="$emit('close')"
      class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
    ></div>

    <!-- Modal -->
    <div
      class="relative bg-app-surface rounded-3xl shadow-2xl w-full max-w-lg overflow-hidden animate-scale-in border border-app-border"
    >
      <!-- Header -->
      <div
        class="p-6 border-b border-app-border flex justify-between items-center bg-app-bg/50"
      >
        <div>
          <h2 class="text-xl font-bold text-app-text">Order Details</h2>
          <p class="text-xs text-app-muted font-mono mt-1">
            #{{ order?.order_number }}
          </p>
        </div>
        <button
          @click="$emit('close')"
          class="text-app-muted hover:text-app-text transition-colors"
        >
          <svg
            class="w-6 h-6"
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
      </div>

      <!-- Content -->
      <div class="p-6 max-h-[60vh] overflow-y-auto">
        <!-- Status & Meta -->
        <div
          class="flex justify-between items-start mb-6 bg-app-bg p-4 rounded-xl border border-app-border"
        >
          <div>
            <p class="text-xs text-app-muted uppercase font-bold mb-1">
              Status
            </p>
            <span
              :class="[
                'px-3 py-1 rounded-lg text-sm font-bold uppercase inline-block',
                order?.payment_status === 'paid'
                  ? 'bg-green-500/20 text-green-500'
                  : order?.payment_status === 'pending'
                  ? 'bg-orange-500/20 text-orange-500'
                  : order?.payment_status === 'rejected'
                  ? 'bg-red-500/20 text-red-500'
                  : 'bg-gray-100 dark:bg-gray-700 text-gray-500',
              ]"
            >
              {{ order?.payment_status }}
            </span>
          </div>
          <div class="text-right">
            <p class="text-xs text-app-muted uppercase font-bold mb-1">Date</p>
            <p class="text-sm text-app-text">
              {{ formatDate(order?.created_at) }}
            </p>
          </div>
        </div>

        <!-- Items -->
        <div class="space-y-4 mb-6">
          <h3 class="text-sm font-bold text-app-muted uppercase">Items</h3>
          <div
            v-for="item in order?.items"
            :key="item.id"
            class="flex justify-between items-start border-b border-app-border pb-2"
          >
            <div class="flex gap-3">
              <div
                class="w-8 h-8 rounded bg-app-bg flex items-center justify-center font-bold text-sm text-app-muted border border-app-border"
              >
                {{ item.quantity }}x
              </div>
              <div>
                <p class="font-medium text-app-text">
                  {{ item.product?.name }}
                </p>
                <p v-if="item.variant" class="text-sm text-app-muted">
                  {{ item.variant.name }}
                </p>
                <div
                  v-if="item.options && item.options.length > 0"
                  class="text-xs text-app-muted mt-1"
                >
                  <div v-for="(opt, idx) in item.options" :key="idx">
                    + {{ opt.group_name }}: {{ opt.option_name }}
                  </div>
                </div>
              </div>
            </div>
            <span class="font-mono text-app-text"
              >${{ item.subtotal.toFixed(2) }}</span
            >
          </div>
        </div>

        <!-- Totals -->
        <div class="space-y-2 text-right pt-2">
          <div class="flex justify-between text-app-muted text-sm">
            <span>Subtotal</span>
            <span>${{ subtotal.toFixed(2) }}</span>
          </div>
          <div
            class="flex justify-between font-bold text-xl text-app-text pt-2 border-t border-app-border"
          >
            <span>Total</span>
            <span>${{ order?.total_amount }}</span>
          </div>
          <div class="flex justify-between text-sm text-app-muted pt-1">
            <span>Payment Method</span>
            <span class="capitalize">{{ order?.payment_method }}</span>
          </div>
        </div>
      </div>

      <!-- Footer Actions -->
      <div
        class="p-6 bg-app-bg/50 border-t border-app-border flex flex-col sm:flex-row gap-3"
      >
        <button
          v-if="order?.payment_status === 'pending'"
          @click="$emit('reject', order)"
          class="flex-1 px-4 py-3 bg-red-500/10 hover:bg-red-500/20 text-red-500 border border-red-500/20 focus:ring-2 focus:ring-red-500/50 rounded-xl font-bold transition-all flex items-center justify-center gap-2"
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
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
          Reject Order
        </button>

        <button
          @click="$emit('print', order)"
          class="flex-1 px-4 py-3 bg-app-text text-app-bg hover:opacity-90 rounded-xl font-bold transition-colors flex items-center justify-center gap-2 shadow-lg"
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
