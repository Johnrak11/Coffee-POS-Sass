<script setup lang="ts">
import { useUIStore } from "@/stores/ui";

const uiStore = useUIStore();

const toastIcons = {
  success: "✓",
  error: "✕",
  warning: "⚠",
  info: "ℹ",
};

const toastColors = {
  success:
    "bg-success-50 border-success-200 text-success-800 dark:bg-success-900/20 dark:border-success-800 dark:text-success-200",
  error:
    "bg-error-50 border-error-200 text-error-800 dark:bg-error-900/20 dark:border-error-800 dark:text-error-200",
  warning:
    "bg-warning-50 border-warning-200 text-warning-800 dark:bg-warning-900/20 dark:border-warning-800 dark:text-warning-200",
  info: "bg-info-50 border-info-200 text-info-800 dark:bg-info-900/20 dark:border-info-800 dark:text-info-200",
};
</script>

<template>
  <div class="fixed top-4 right-4 z-[9999] flex flex-col gap-2 max-w-md">
    <transition-group
      enter-active-class="transition ease-out duration-300"
      enter-from-class="transform translate-x-full opacity-0"
      enter-to-class="transform translate-x-0 opacity-100"
      leave-active-class="transition ease-in duration-200"
      leave-from-class="transform translate-x-0 opacity-100"
      leave-to-class="transform translate-x-full opacity-0"
    >
      <div
        v-for="toast in uiStore.toasts"
        :key="toast.id"
        :class="[
          'flex items-center gap-3 px-4 py-3 rounded-lg border shadow-lg backdrop-blur-sm',
          toastColors[toast.type],
        ]"
      >
        <!-- Icon -->
        <div
          class="flex-shrink-0 w-5 h-5 flex items-center justify-center font-bold text-lg"
        >
          {{ toastIcons[toast.type] }}
        </div>

        <!-- Message -->
        <p class="flex-1 text-sm font-medium">
          {{ toast.message }}
        </p>

        <!-- Close Button -->
        <button
          @click="uiStore.removeToast(toast.id)"
          class="flex-shrink-0 text-current opacity-60 hover:opacity-100 transition-opacity"
        >
          <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
            <path
              fill-rule="evenodd"
              d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
              clip-rule="evenodd"
            />
          </svg>
        </button>
      </div>
    </transition-group>
  </div>
</template>
