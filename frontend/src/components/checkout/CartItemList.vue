<script setup lang="ts">
import { useI18n } from "vue-i18n";
import { computed } from "vue";

const props = defineProps<{
  items: any[];
  isPartialPayment: boolean;
  isSubmitting: boolean;
}>();

const emit = defineEmits<{
  (e: "delete", id: number): void;
  (e: "update-quantity", item: any, qty: number): void;
}>();

const { t } = useI18n();

function getItemTotal(item: any) {
  const basePrice = Number(item.product.price || 0);
  const optionsPrice = (item.options || []).reduce(
    (sum: number, opt: any) => sum + Number(opt.extra_price || 0),
    0,
  );
  return (basePrice + optionsPrice) * item.quantity;
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
}
</script>

<template>
  <div v-if="items.length > 0" v-auto-animate class="space-y-4 mb-8">
    <div
      v-if="isPartialPayment"
      class="bg-blue-50 text-blue-700 px-4 py-3 rounded-2xl text-sm font-bold flex items-center gap-2"
    >
      <svg
        class="w-5 h-5 shrink-0"
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
      {{ t("customer.partialPaymentNotice") }}
    </div>
    <div
      v-for="item in items"
      :key="item.id"
      class="bg-white p-5 rounded-3xl border border-gray-100 shadow-sm flex items-center gap-4 relative group"
      :class="{ 'opacity-60 grayscale-[0.5]': isPartialPayment }"
    >
      <button
        @click="emit('delete', item.id)"
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
        class="w-16 h-16 bg-gray-50 rounded-2xl flex items-center justify-center overflow-hidden shrink-0"
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

        <!-- Display Options -->
        <div
          v-if="item.options && item.options.length > 0"
          class="flex flex-wrap gap-1 mt-1"
        >
          <span
            v-for="opt in item.options"
            :key="opt.product_variant_id || opt.id"
            class="text-xs bg-gray-100 text-gray-500 px-2 py-0.5 rounded-md"
          >
            {{ opt.option_name }}
            <span v-if="Number(opt.extra_price) > 0"
              >(+{{ Number(opt.extra_price).toFixed(2) }})</span
            >
          </span>
        </div>

        <!-- Quantity Controls -->
        <div class="flex items-center gap-3 mt-2">
          <button
            @click="emit('update-quantity', item, item.quantity - 1)"
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
            @click="emit('update-quantity', item, item.quantity + 1)"
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
        {{ formatCurrency(getItemTotal(item)) }}
      </p>
    </div>
  </div>
</template>
