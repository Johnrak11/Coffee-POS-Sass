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
      router.push(`/menu/${sessionStore.shopSlug}`);
    }
  }
});
</script>

<template>
  <div
    class="min-h-screen flex items-center justify-center bg-gradient-to-br from-primary-50 to-accent-50 p-6"
  >
    <div
      class="glass-card p-8 rounded-3xl max-w-md w-full text-center animate-scale-in"
    >
      <div class="mb-6">
        <div
          class="w-20 h-20 bg-gradient-to-br from-primary-500 to-accent-500 rounded-full mx-auto mb-4 flex items-center justify-center"
        >
          <svg
            class="w-10 h-10 text-white"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 6v6m0 0v6m0-6h6m-6 0H6"
            />
          </svg>
        </div>
        <h1 class="text-2xl font-display font-bold text-gray-900">
          Welcome to {{ sessionStore.shopName || "Coffee Shop" }}
        </h1>
      </div>

      <div class="mb-6">
        <p class="text-gray-600 mb-2">Table</p>
        <div class="text-4xl font-display font-bold text-primary-700">
          {{ sessionStore.tableNumber || "..." }}
        </div>
      </div>

      <div class="space-y-3">
        <div class="skeleton h-4 w-3/4 mx-auto rounded"></div>
        <div class="skeleton h-4 w-1/2 mx-auto rounded"></div>
      </div>

      <p class="text-sm text-gray-500 mt-6">Loading your menu...</p>
    </div>
  </div>
</template>
