<script setup lang="ts">
import { onMounted, onUnmounted } from "vue";

defineProps<{
  isOpen: boolean;
  title?: string;
  maxWidth?: "sm" | "md" | "lg" | "xl" | "2xl";
}>();

const emit = defineEmits(["close"]);

// Lock body scroll when open
onMounted(() => {
  if (document) document.body.style.overflow = "hidden";
});

onUnmounted(() => {
  if (document) document.body.style.overflow = "";
});

const maxWidthClasses = {
  sm: "max-w-sm",
  md: "max-w-md",
  lg: "max-w-lg",
  xl: "max-w-xl",
  "2xl": "max-w-2xl",
};
</script>

<template>
  <Teleport to="body">
    <Transition name="fade">
      <div
        v-if="isOpen"
        class="fixed inset-0 z-50 flex items-center justify-center p-6 bg-black/60 backdrop-blur-sm"
        @click.self="$emit('close')"
      >
        <div
          class="bg-white w-full rounded-3xl shadow-2xl flex flex-col max-h-[90vh] overflow-hidden animate-scale-in"
          :class="maxWidthClasses[maxWidth || 'md']"
        >
          <!-- Header -->
          <div
            v-if="title || $slots.header"
            class="p-6 border-b border-gray-100 flex justify-between items-center bg-gray-50/50"
          >
            <div>
              <h2 v-if="title" class="text-xl font-black text-gray-900">
                {{ title }}
              </h2>
              <slot name="subtitle"></slot>
            </div>
            <button
              @click="$emit('close')"
              class="text-gray-400 hover:text-gray-900 bg-gray-100 hover:bg-gray-200 rounded-full p-2 transition-colors"
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
            </button>
          </div>

          <!-- Body -->
          <div class="p-6 overflow-y-auto custom-scrollbar">
            <slot></slot>
          </div>

          <!-- Footer -->
          <div
            v-if="$slots.footer"
            class="p-6 border-t border-gray-100 bg-gray-50 flex justify-end gap-3"
          >
            <slot name="footer"></slot>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

.animate-scale-in {
  animation: scaleIn 0.2s ease-out;
}

@keyframes scaleIn {
  from {
    opacity: 0;
    transform: scale(0.95);
  }
  to {
    opacity: 1;
    transform: scale(1);
  }
}
</style>
