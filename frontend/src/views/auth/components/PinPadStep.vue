<script setup lang="ts">
import { ref, watch, onMounted, onUnmounted } from "vue";
import { LanguageSelector } from "@/components/common";

const props = defineProps<{
  user: any;
  loading: boolean;
  error?: string;
}>();

const emit = defineEmits(["login"]);
const pin = ref("");

function appendPin(digit: string) {
  if (pin.value.length < 6) {
    pin.value += digit;
  }
}

function clearPin() {
  pin.value = "";
}

function onSubmit() {
  if (pin.value.length === 6) {
    emit("login", pin.value);
  }
}

watch(
  () => props.error,
  (val) => {
    if (val) pin.value = ""; // clear pin on error
  }
);

function handleKeydown(e: KeyboardEvent) {
  if (props.loading) return;

  // Numbers 0-9
  if (/^[0-9]$/.test(e.key)) {
    appendPin(e.key);
    return;
  }

  // Backspace
  if (e.key === "Backspace") {
    pin.value = pin.value.slice(0, -1);
    return;
  }

  // Enter
  if (e.key === "Enter") {
    onSubmit();
    return;
  }

  // Escape or Delete
  if (e.key === "Escape" || e.key === "Delete") {
    clearPin();
    return;
  }
}

onMounted(() => {
  window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeydown);
});
</script>

<template>
  <div
    class="glass-card bg-gray-800/80 backdrop-blur-xl p-8 rounded-[32px] border border-gray-700 shadow-2xl animate-fade-in"
  >
    <!-- Header with Language Selector -->
    <div class="flex justify-between items-start mb-6">
      <div class="text-center flex-1">
        <div
          class="w-20 h-20 bg-primary-600 rounded-full mx-auto flex items-center justify-center mb-4 shadow-lg shadow-primary-900/50"
        >
          <span class="text-4xl font-black text-white">{{
            user.name.charAt(0)
          }}</span>
        </div>
        <h2 class="text-gray-400 mb-1">Enter PIN for</h2>
        <p class="text-2xl font-bold text-white">{{ user.name }}</p>
      </div>
      <!-- Language Selector -->
      <div class="absolute top-8 right-8">
        <LanguageSelector />
      </div>
    </div>

    <!-- PIN Display -->
    <div class="flex justify-center gap-4 mb-6">
      <div
        v-for="i in 6"
        :key="i"
        :class="[
          'w-4 h-4 rounded-full transition-all duration-200',
          i <= pin.length
            ? 'bg-primary-500 scale-110 shadow-[0_0_10px_orange]'
            : 'bg-gray-700',
        ]"
      ></div>
    </div>

    <div
      v-if="error"
      class="text-red-400 text-center mb-4 font-medium bg-red-500/10 py-2 rounded-lg border border-red-500/20"
    >
      {{ error }}
    </div>

    <!-- Numpad -->
    <div class="grid grid-cols-3 gap-3 max-w-xs mx-auto">
      <button
        v-for="n in 9"
        :key="n"
        @click="appendPin(n.toString())"
        class="h-16 rounded-2xl bg-gray-700/50 text-white text-2xl font-bold hover:bg-gray-600 active:bg-gray-500 active:scale-95 transition-all outline-none"
      >
        {{ n }}
      </button>

      <button
        @click="clearPin"
        class="h-16 rounded-2xl bg-red-500/10 text-red-400 font-bold hover:bg-red-500/20 active:scale-95 transition-all outline-none"
      >
        CLEAR
      </button>
      <button
        @click="appendPin('0')"
        class="h-16 rounded-2xl bg-gray-700/50 text-white text-2xl font-bold hover:bg-gray-600 active:bg-gray-500 active:scale-95 transition-all outline-none"
      >
        0
      </button>

      <button
        @click="onSubmit"
        :disabled="pin.length !== 6 || loading"
        :class="[
          'h-16 rounded-2xl text-white text-xl font-bold transition-all flex items-center justify-center',
          pin.length === 6
            ? 'bg-primary-600 hover:bg-primary-500 shadow-lg'
            : 'bg-gray-700 opacity-50 cursor-not-allowed',
        ]"
      >
        <span v-if="!loading">ENTER</span>
        <span v-else class="animate-spin">↻</span>
      </button>
    </div>
  </div>
</template>
