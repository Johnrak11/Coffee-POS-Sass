<script setup lang="ts">
import { useI18n } from "vue-i18n";

defineProps<{
  partialOrder: any;
  remainingAmount: number;
}>();

const emit = defineEmits<{
  (e: "pay-remaining"): void;
}>();

const { t } = useI18n();
</script>

<template>
  <div class="space-y-6 mb-8">
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
        <h2 class="text-xl font-black text-gray-900">
          {{ t("customer.partialPayment") }}
        </h2>
        <p class="text-sm text-gray-600">
          {{ t("customer.youHavePaid") }}
          <span class="font-bold text-green-600"
            >${{
              Number(partialOrder.total_amount - remainingAmount).toFixed(2)
            }}</span
          >
          {{ t("customer.ofTotalAmount") }}
        </p>
      </div>

      <div class="bg-white p-4 rounded-2xl border border-gray-100">
        <p
          class="text-gray-400 text-xs font-bold uppercase tracking-widest mb-1"
        >
          {{ t("customer.remainingBalance") }}
        </p>
        <p class="text-4xl font-black text-gray-900">
          ${{ Number(remainingAmount).toFixed(2) }}
        </p>
      </div>

      <button
        @click="emit('pay-remaining')"
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
        {{ t("customer.payRemainingBalance") }}
      </button>
    </div>
  </div>
</template>
