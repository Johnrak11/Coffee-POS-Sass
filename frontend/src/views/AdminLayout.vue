<script setup lang="ts">
import { useAuthStore } from "@/stores/auth";
import { useThemeStore } from "@/stores/theme";
import { useRouter, useRoute } from "vue-router";

const authStore = useAuthStore();
const themeStore = useThemeStore();
const router = useRouter();
const route = useRoute();

function logout() {
  authStore.logoutUser();
  router.push("/login");
}

const navItems = [
  {
    name: "Dashboard",
    path: "/admin",
    icon: "M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6",
  },
  {
    name: "Products",
    path: "/admin/products",
    icon: "M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4",
  },
  {
    name: "Categories",
    path: "/admin/categories",
    icon: "M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10",
  },
  {
    name: "Staff",
    path: "/admin/staff",
    icon: "M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z",
  },
  {
    name: "Transactions",
    path: "/admin/transactions",
    icon: "M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01",
  },
  {
    name: "Settings",
    path: "/admin/settings",
    icon: "M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37a1.724 1.724 0 002.572-1.065z",
  },
  {
    name: "Tables",
    path: "/admin/tables",
    icon: "M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z",
  },
];
</script>

<template>
  <div
    class="flex h-screen bg-app-bg overflow-hidden transition-colors duration-300"
  >
    <!-- Sidebar -->
    <aside
      class="flex flex-col bg-app-sidebar border-r border-app-border transition-all duration-300 z-20 shadow-sm"
      :class="themeStore.isSidebarCollapsed ? 'w-20' : 'w-64'"
    >
      <!-- Header -->
      <div
        class="p-4 border-b border-app-border flex items-center justify-between"
      >
        <div class="flex items-center gap-3 overflow-hidden">
          <div
            class="min-w-[40px] w-10 h-10 bg-orange-600 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-orange-200/20"
          >
            A
          </div>
          <div
            v-show="!themeStore.isSidebarCollapsed"
            class="whitespace-nowrap transition-opacity duration-300"
          >
            <h1 class="font-bold text-app-text leading-tight">Admin</h1>
            <p
              class="text-[10px] text-app-muted font-medium uppercase tracking-wider truncate max-w-[120px]"
            >
              {{ authStore.shop?.name }}
            </p>
          </div>
        </div>
        <!-- Collapse Button -->
        <button
          v-show="!themeStore.isSidebarCollapsed"
          @click="themeStore.toggleSidebar"
          class="p-1 text-app-muted hover:text-app-text rounded-lg hover:bg-app-bg transition-colors"
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
              d="M11 19l-7-7 7-7m8 14l-7-7 7-7"
            />
          </svg>
        </button>
      </div>

      <!-- Expand Button (When collapsed) -->
      <div
        v-show="themeStore.isSidebarCollapsed"
        class="w-full flex justify-center py-2"
      >
        <button
          @click="themeStore.toggleSidebar"
          class="p-2 text-app-muted hover:text-app-text rounded-lg hover:bg-app-bg transition-colors"
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
              d="M13 5l7 7-7 7M5 5l7 7-7 7"
            />
          </svg>
        </button>
      </div>

      <!-- Navigation -->
      <nav class="flex-1 w-full p-3 space-y-1">
        <router-link
          v-for="item in navItems"
          :key="item.path"
          :to="item.path"
          :class="[
            'flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 group relative',
            route.path === item.path
              ? 'bg-orange-50 dark:bg-orange-500/10 text-orange-600 font-bold'
              : 'text-app-muted hover:bg-app-bg hover:text-app-text',
            themeStore.isSidebarCollapsed ? 'justify-center' : '',
          ]"
        >
          <svg
            class="w-6 h-6 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              :d="item.icon"
            />
          </svg>
          <span
            v-if="!themeStore.isSidebarCollapsed"
            class="whitespace-nowrap"
            >{{ item.name }}</span
          >

          <!-- Tooltip -->
          <div
            v-if="themeStore.isSidebarCollapsed"
            class="absolute left-full ml-3 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50"
          >
            {{ item.name }}
          </div>
        </router-link>
      </nav>

      <!-- Footer -->
      <div class="p-4 w-full border-t border-app-border">
        <!-- Theme Toggle -->
        <button
          @click="themeStore.toggleTheme"
          class="w-full flex items-center gap-3 px-3 py-3 mb-2 rounded-xl text-app-muted hover:bg-app-bg hover:text-app-text transition-colors"
          :class="themeStore.isSidebarCollapsed ? 'justify-center' : ''"
        >
          <svg
            v-if="themeStore.isDark"
            class="w-6 h-6 shrink-0"
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
            class="w-6 h-6 shrink-0"
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
          <span v-if="!themeStore.isSidebarCollapsed">
            {{ themeStore.isDark ? "Light" : "Dark" }} Mode
          </span>
        </button>

        <div
          v-if="!themeStore.isSidebarCollapsed"
          class="p-3 bg-app-bg rounded-2xl flex items-center gap-3 mb-3"
        >
          <div
            class="w-8 h-8 rounded-full bg-orange-100 flex items-center justify-center text-orange-600 font-bold text-xs"
          >
            {{ authStore.user?.name?.[0] || "U" }}
          </div>
          <div class="flex-1 overflow-hidden">
            <p class="text-xs font-bold text-app-text truncate">
              {{ authStore.user?.name }}
            </p>
            <p class="text-[10px] text-app-muted truncate capitalise">
              {{ authStore.user?.role }}
            </p>
          </div>
        </div>
        <button
          @click="logout"
          class="w-full flex items-center justify-center gap-2 px-3 py-3 rounded-xl border border-app-border text-app-muted font-bold hover:bg-red-50 hover:text-red-600 hover:border-red-100 transition-all duration-200"
          :class="themeStore.isSidebarCollapsed ? 'border-0' : ''"
        >
          <svg
            class="w-5 h-5 shrink-0"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            />
          </svg>
          <span v-if="!themeStore.isSidebarCollapsed">Logout</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto relative bg-app-bg">
      <router-view />
    </main>
  </div>
</template>
