<script setup lang="ts">
import { computed, ref, watch } from "vue";
import { useThemeStore } from "@/stores/theme";

const props = defineProps<{
  show: boolean;
  product: any;
}>();

const emit = defineEmits(["close", "add-to-cart"]);
const themeStore = useThemeStore();

const selections = ref<Record<string, any>>({});
const quantity = ref(1);

// Group variants by name (e.g., "Size", "Sugar Level")
const variantGroups = computed(() => {
  if (!props.product?.variants) return {};

  const groups: Record<string, any[]> = {};
  props.product.variants.forEach((variant: any) => {
    if (!groups[variant.name]) {
      groups[variant.name] = [];
    }
    groups[variant.name]?.push(variant);
  });
  return groups;
});

// Calculate total price
const totalPrice = computed(() => {
  const basePrice = Number(props.product?.price || 0);
  const extras = Object.values(selections.value).reduce(
    (sum: number, variant: any) => {
      return sum + Number(variant.extra_price || 0);
    },
    0,
  );
  return (basePrice + extras) * quantity.value;
});

// Initialize default selections (first option of each group)
watch(
  () => props.product,
  (newProduct) => {
    selections.value = {};
    quantity.value = 1;
    if (newProduct?.variants) {
      const groups = variantGroups.value;
      Object.keys(groups).forEach((key) => {
        const group = groups[key];
        // Default to first option
        // In a real app, maybe check for 'is_default' flag
        if (group && group.length > 0) {
          selections.value[key] = group[0];
        }
      });
    }
  },
  { immediate: true },
);

function addToCart() {
  const selectedOptions = Object.values(selections.value).map((v: any) => ({
    product_variant_id: v.id,
    group_name: v.name,
    option_name: v.option_name,
    extra_price: v.extra_price,
  }));

  emit("add-to-cart", {
    product: props.product,
    options: selectedOptions,
    quantity: quantity.value,
  });
  emit("close");
}

function formatCurrency(amount: number) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(amount);
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
      class="relative bg-app-surface w-full max-w-md rounded-2xl shadow-2xl overflow-hidden animate-scale-in text-app-text border border-app-border flex flex-col max-h-[90vh]"
    >
      <!-- Header -->
      <div
        class="p-4 border-b border-app-border bg-app-bg/50 flex justify-between items-center"
      >
        <h2 class="text-xl font-bold">{{ product?.name }}</h2>
        <button
          @click="$emit('close')"
          class="p-2 hover:bg-app-bg rounded-lg text-app-muted hover:text-app-text transition-colors"
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
      <div class="p-6 overflow-y-auto flex-1">
        <div class="space-y-6">
          <!-- Variant Groups -->
          <div v-for="(variants, groupName) in variantGroups" :key="groupName">
            <h3 class="font-bold text-sm uppercase text-app-muted mb-3">
              {{ groupName }}
            </h3>
            <div class="grid grid-cols-2 gap-3">
              <label
                v-for="variant in variants"
                :key="variant.id"
                class="cursor-pointer relative group"
              >
                <input
                  type="radio"
                  :name="groupName"
                  :value="variant"
                  v-model="selections[groupName]"
                  class="peer sr-only"
                />
                <div
                  class="p-3 rounded-xl border border-app-border bg-app-bg peer-checked:border-primary-500 peer-checked:bg-primary-500/10 peer-checked:text-primary-500 transition-all text-center"
                >
                  <div class="font-medium">{{ variant.option_name }}</div>
                  <div
                    v-if="Number(variant.extra_price) > 0"
                    class="text-xs mt-1 text-app-muted group-hover:text-app-text"
                  >
                    +{{ formatCurrency(Number(variant.extra_price)) }}
                  </div>
                </div>
              </label>
            </div>
          </div>
        </div>
      </div>

      <!-- Footer -->
      <div class="p-4 border-t border-app-border bg-app-bg/50">
        <div class="flex items-center justify-between mb-4">
          <div
            class="flex items-center gap-3 bg-app-bg rounded-xl p-1 border border-app-border"
          >
            <button
              @click="quantity > 1 && quantity--"
              class="w-8 h-8 flex items-center justify-center hover:bg-app-surface rounded-lg transition-colors text-xl font-bold"
            >
              -
            </button>
            <span class="w-8 text-center font-bold">{{ quantity }}</span>
            <button
              @click="quantity++"
              class="w-8 h-8 flex items-center justify-center hover:bg-app-surface rounded-lg transition-colors text-xl font-bold"
            >
              +
            </button>
          </div>
          <div class="text-xl font-bold">
            {{ formatCurrency(totalPrice) }}
          </div>
        </div>
        <button
          @click="addToCart"
          class="w-full py-4 bg-primary-600 hover:bg-primary-500 text-white rounded-xl font-bold text-lg shadow-lg shadow-primary-900/20 transition-all transform active:scale-95"
        >
          Add to Order
        </button>
      </div>
    </div>
  </div>
</template>
