<script setup lang="ts">
import { useAuthStore } from "@/stores/auth";
import { useThemeStore } from "@/stores/theme";
import { useRouter } from "vue-router";

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();

function logout() {
  authStore.logoutUser();
  router.push("/login"); // Back to staff selection
}

function goToPos() {
  router.push("/pos");
}
</script>

<template>
  <div
    class="flex flex-col h-screen bg-app-bg text-app-text overflow-hidden transition-colors duration-300"
  >
    <!-- Header -->
    <header
      class="h-16 bg-app-surface border-b border-app-border flex items-center justify-between px-6 shadow-md z-10 transition-colors duration-300"
    >
      <div class="flex items-center gap-4">
        <div
          class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center font-bold text-xl text-white shadow-lg shadow-orange-900/50"
        >
          K
        </div>
        <div>
          <h1 class="font-bold text-lg leading-tight text-app-text">
            Kitchen Display
          </h1>
          <p class="text-xs text-app-muted capitalize">
            {{ authStore.shop?.name || "Shop" }}
          </p>
        </div>
      </div>

      <div class="flex items-center gap-4">
        <!-- Clock -->
        <div
          class="text-xl font-mono font-bold text-app-text hidden sm:block mr-4"
        >
          {{
            new Date().toLocaleTimeString([], {
              hour: "2-digit",
              minute: "2-digit",
            })
          }}
        </div>

        <!-- Theme Toggle -->
        <button
          @click="themeStore.toggleTheme"
          class="p-2 rounded-lg text-app-muted hover:bg-app-bg hover:text-app-text transition-colors"
          title="Toggle Theme"
        >
          <svg
            v-if="themeStore.isDark"
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"
            />
          </svg>
          <svg
            v-else
            class="w-6 h-6"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"
            />
          </svg>
        </button>

        <button
          v-if="authStore.user?.role !== 'barista'"
          @click="goToPos"
          class="bg-app-bg hover:bg-gray-200 dark:hover:bg-gray-700 px-4 py-2 rounded-lg text-sm transition-colors text-app-text border border-app-border font-medium"
        >
          pos
        </button>
        <button
          @click="logout"
          class="bg-red-600 hover:bg-red-500 px-4 py-2 rounded-lg text-sm text-white transition-colors font-bold shadow-lg shadow-red-900/30"
        >
          Exit
        </button>
      </div>
    </header>

    <!-- Main Content -->
    <main class="flex-1 overflow-hidden relative">
      <router-view />
    </main>
  </div>
</template>
