<script setup lang="ts">
import { Toaster } from "vue-sonner";
import { onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useUIStore } from "@/stores/ui";
import ToastContainer from "@/components/common/ToastContainer.vue";

const authStore = useAuthStore();
const uiStore = useUIStore();

onMounted(() => {
  authStore.restoreSession();
  // Theme is applied automatically by UI store
});
</script>

<template>
  <div
    id="app"
    class="min-h-screen font-sans antialiased text-app-text bg-app-bg transition-colors duration-300"
  >
    <router-view v-slot="{ Component }">
      <transition name="page" mode="out-in">
        <component :is="Component" />
      </transition>
    </router-view>
    <Toaster position="top-center" richColors closeButton />
    <ToastContainer />
  </div>
</template>

<style>
.page-enter-active,
.page-leave-active {
  transition: opacity 0.25s ease, transform 0.25s ease;
}

.page-enter-from {
  opacity: 0;
  transform: translateY(10px);
}

.page-leave-to {
  opacity: 0;
  transform: translateY(-10px);
}

/* Custom scrollbar for premium feel */
::-webkit-scrollbar {
  width: 6px;
  height: 6px;
}

::-webkit-scrollbar-track {
  background: transparent;
}

::-webkit-scrollbar-thumb {
  background: #e5e7eb;
  border-radius: 10px;
}

::-webkit-scrollbar-thumb:hover {
  background: #d1d5db;
}
</style>
