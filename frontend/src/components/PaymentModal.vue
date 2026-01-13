<script setup lang="ts">
import { ref, computed } from "vue";

const props = defineProps<{
  show: boolean;
  total: number;
}>();

const emit = defineEmits(["close", "confirm"]);

const receivedAmount = ref("");

const change = computed(() => {
  const received = parseFloat(receivedAmount.value);
  if (isNaN(received)) return 0;
  return received - props.total;
});

const isValid = computed(() => {
  const received = parseFloat(receivedAmount.value);
  return !isNaN(received) && received >= props.total;
});

function appendNumber(num: string) {
  if (num === "." && receivedAmount.value.includes(".")) return;
  receivedAmount.value += num;
}

function clear() {
  receivedAmount.value = "";
}

function handleConfirm() {
  if (isValid.value) {
    emit("confirm", parseFloat(receivedAmount.value));
  }
}

function exactAmount() {
  receivedAmount.value = Number(props.total).toFixed(2);
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
      class="absolute inset-0 bg-black/60 backdrop-blur-sm"
    ></div>

    <!-- Modal -->
    <div
      class="relative bg-app-surface rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-scale-in border border-app-border"
    >
      <div class="bg-gray-900 text-white p-6 text-center">
        <h2 class="text-xl font-medium opacity-80 mb-1">Total to Pay</h2>
        <div class="text-4xl font-bold font-display">
          ${{ Number(totalAmount).toFixed(2) }}
        </div>
      </div>

      <div class="p-6">
        <div class="mb-6">
          <label class="block text-sm font-bold text-app-muted mb-2"
            >Received Cash ($)</label
          >
          <div class="flex gap-2">
            <div
              class="flex-1 bg-app-bg rounded-xl p-4 text-2xl font-bold text-right text-app-text border-2 border-transparent focus-within:border-primary-500 transition-colors"
            >
              {{ receivedAmount || "0.00" }}
            </div>
            <button
              @click="exactAmount"
              class="px-4 py-2 bg-app-border rounded-xl font-bold text-app-muted hover:text-app-text hover:bg-gray-300 dark:hover:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-700 transition-colors"
            >
              Exact
            </button>
          </div>
        </div>

        <div
          v-if="change > 0"
          class="mb-6 p-4 bg-green-50 text-green-700 rounded-xl flex justify-between items-center animate-pulse"
        >
          <span class="font-bold">CHANGE DUE:</span>
          <span class="text-2xl font-bold"
            >${{ Number(change).toFixed(2) }}</span
          >
        </div>

        <!-- Numpad -->
        <div class="grid grid-cols-3 gap-3 mb-6">
          <button
            v-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]"
            :key="n"
            @click="appendNumber(n.toString())"
            class="h-14 rounded-xl bg-app-bg text-xl text-app-text font-bold hover:bg-gray-200 dark:hover:bg-gray-700 active:bg-gray-300 dark:active:bg-gray-600 border border-app-border transition-colors"
          >
            {{ n }}
          </button>
          <button
            @click="appendNumber('.')"
            class="h-14 rounded-xl bg-app-bg text-xl text-app-text font-bold hover:bg-gray-200 dark:hover:bg-gray-700 border border-app-border transition-colors"
          >
            .
          </button>
          <button
            @click="appendNumber('0')"
            class="h-14 rounded-xl bg-app-bg text-xl text-app-text font-bold hover:bg-gray-200 dark:hover:bg-gray-700 border border-app-border transition-colors"
          >
            0
          </button>
          <button
            @click="clear"
            class="h-14 rounded-xl bg-red-50 text-red-500 font-bold hover:bg-red-100 border border-red-100 dark:bg-red-900/10 dark:hover:bg-red-900/30 dark:border-red-900/30 transition-colors"
          >
            C
          </button>
        </div>

        <button
          @click="handleConfirm"
          :disabled="!isValid"
          :class="[
            'w-full py-4 rounded-2xl font-bold text-xl transition-all',
            isValid
              ? 'bg-primary-600 text-white hover:bg-primary-700 shadow-lg'
              : 'bg-app-bg text-app-muted cursor-not-allowed border border-app-border',
          ]"
        >
          Complete Payment
        </button>
      </div>
    </div>
  </div>
</template>
