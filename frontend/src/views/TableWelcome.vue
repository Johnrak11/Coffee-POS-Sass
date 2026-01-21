<script setup lang="ts">
import { onMounted } from "vue";
import { useRouter } from "vue-router";
import { useSessionStore } from "@/stores/session";

const router = useRouter();
const sessionStore = useSessionStore();

onMounted(async () => {
  const qrToken = router.currentRoute.value.params.qrToken as string;

  if (qrToken) {
    const success = await sessionStore.scanTable(qrToken);

    if (success && sessionStore.shopSlug) {
      router.replace(`/menu/${sessionStore.shopSlug}`);
    } else {
      // Handle error or stay on loading/error state
      console.error("Failed to scan table");
    }
  }
});
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-accent-50 p-6"
  >
    <div class="text-center">
      <div
        class="w-20 h-20 bg-white rounded-full mx-auto mb-6 flex items-center justify-center shadow-lg animate-pulse"
      >
        <svg
          class="w-10 h-10 text-primary-600"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"
          />
        </svg>
      </div>
      <h2 class="text-xl font-bold text-gray-800 mb-2">Finding Table...</h2>
      <p class="text-gray-500 text-sm">Please wait a moment.</p>
    </div>
  </div>
</template>
