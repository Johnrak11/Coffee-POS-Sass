<script setup lang="ts">
import { onMounted, onUnmounted, ref } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useThemeStore } from "@/stores/theme";
import { useRouter, useRoute } from "vue-router";
import { useUIStore } from "@/stores/ui";
import { useNotificationStore } from "@/stores/notification";
import { toast } from "vue-sonner";
import NotificationDrawer from "@/components/common/NotificationDrawer.vue";

const authStore = useAuthStore();
const themeStore = useThemeStore();
const uiStore = useUIStore();
const notificationStore = useNotificationStore();
const router = useRouter();
const route = useRoute();

const showNotifications = ref(false); // Drawer state

let pollingInterval: any = null;

onMounted(() => {
  if (authStore.user) {
    // Poll unread count every 15 seconds
    notificationStore.checkUnreadCount();
    pollingInterval = setInterval(() => {
      notificationStore.checkUnreadCount();
    }, 15000);
  }
});

onUnmounted(() => {
  if (pollingInterval) clearInterval(pollingInterval);
});

function handleNotificationClick(notification: any) {
  notificationStore.markAsRead(notification.id);
  // Navigate to order details
  if (notification.data?.id) {
    // Find if it's new order or other type. Assuming new order.
    router.push(`/pos/orders`); // Ideally to specific order /pos/orders/${id} but list is fine
  }
}

function toggleLanguage() {
  const newLocale = uiStore.currentLocale === "en" ? "kh" : "en";
  uiStore.setLocale(newLocale);
}

function logout() {
  authStore.logout();
  router.push("/login");
}
</script>

<template>
  <div
    class="flex h-screen bg-app-bg overflow-hidden transition-colors duration-300"
  >
    <!-- Sidebar -->
    <aside
      class="flex flex-col bg-app-sidebar border-r border-app-border transition-all duration-300 z-20 shadow-xl"
      :class="themeStore.isSidebarCollapsed ? 'w-20' : 'w-64'"
    >
      <!-- Header -->
      <div
        class="p-4 flex items-center justify-between border-b border-app-border"
      >
        <div class="flex items-center gap-3 overflow-hidden">
          <img
            v-if="authStore.shop?.logo_url"
            :src="authStore.shop.logo_url"
            class="min-w-[40px] w-10 h-10 rounded-xl object-cover shadow-lg shadow-primary-200/20 dark:shadow-primary-900/20"
          />
          <div
            v-else
            class="min-w-[40px] w-10 h-10 bg-primary-600 dark:bg-primary-700 rounded-xl flex items-center justify-center text-white font-bold shadow-lg shadow-primary-200/20 dark:shadow-primary-900/20"
          >
            {{ authStore.shop?.name?.[0] || "P" }}
          </div>
          <div
            v-show="!themeStore.isSidebarCollapsed"
            class="whitespace-nowrap transition-opacity duration-300"
          >
            <h1
              class="font-bold text-app-text leading-tight truncate max-w-[140px]"
            >
              {{ authStore.shop?.name || "POS System" }}
            </h1>
            <p
              class="text-[10px] text-app-muted font-medium uppercase tracking-wider truncate max-w-[120px]"
            >
              {{ authStore.user?.role || "Staff" }}
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

      <nav class="flex-1 p-3 space-y-2">
        <router-link
          to="/pos"
          class="flex items-center gap-3 p-3 rounded-xl transition-colors group relative"
          :class="[
            route.path === '/pos'
              ? 'bg-primary-600/20 text-primary-600 dark:text-primary-400'
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
              d="M4 6h16M4 10h16M4 14h16M4 18h16"
            ></path>
          </svg>
          <span
            v-if="!themeStore.isSidebarCollapsed"
            class="font-medium whitespace-nowrap"
            >{{ $t("pos.pointOfSale") }}</span
          >
          <div
            v-if="themeStore.isSidebarCollapsed"
            class="absolute left-full ml-3 px-2 py-1 bg-gray-800 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50"
          >
            {{ $t("pos.pointOfSale") }}
          </div>
        </router-link>

        <router-link
          to="/kitchen"
          class="flex items-center gap-3 p-3 rounded-xl transition-colors group relative"
          :class="[
            route.path === '/kitchen'
              ? 'bg-primary-600/20 text-primary-600 dark:text-primary-400'
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
              d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
            ></path>
          </svg>
          <span
            v-if="!themeStore.isSidebarCollapsed"
            class="font-medium whitespace-nowrap"
            >{{ $t("nav.kitchen") }}</span
          >
          <div
            v-if="themeStore.isSidebarCollapsed"
            class="absolute left-full ml-3 px-2 py-1 bg-gray-700 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50"
          >
            {{ $t("nav.kitchen") }}
          </div>
        </router-link>

        <router-link
          to="/pos/orders"
          class="flex items-center gap-3 p-3 rounded-xl transition-colors group relative"
          :class="[
            route.path === '/pos/orders'
              ? 'bg-primary-600/20 text-primary-600 dark:text-primary-400'
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
              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"
            ></path>
          </svg>
          <span
            v-if="!themeStore.isSidebarCollapsed"
            class="font-medium whitespace-nowrap"
            >{{ $t("nav.orders") }}</span
          >
          <div
            v-if="themeStore.isSidebarCollapsed"
            class="absolute left-full ml-3 px-2 py-1 bg-gray-700 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50"
          >
            {{ $t("nav.orders") }}
          </div>
        </router-link>

        <!-- Notification Bell (Drawer Toggle) -->
        <button
          @click="showNotifications = true"
          class="flex items-center gap-3 p-3 rounded-xl transition-colors group relative w-full"
          :class="[
            showNotifications
              ? 'bg-primary-600/20 text-primary-600 dark:text-primary-400'
              : 'text-app-muted hover:bg-app-bg hover:text-app-text',
            themeStore.isSidebarCollapsed ? 'justify-center' : 'justify-start',
          ]"
        >
          <div class="relative">
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
                d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"
              />
            </svg>
            <span
              v-if="notificationStore.unreadCount > 0"
              class="absolute -top-1 -right-1 flex h-4 w-4 items-center justify-center rounded-full bg-red-500 text-[10px] text-white font-bold ring-2 ring-white dark:ring-gray-900"
            >
              {{
                notificationStore.unreadCount > 9
                  ? "9+"
                  : notificationStore.unreadCount
              }}
            </span>
          </div>

          <span
            v-if="!themeStore.isSidebarCollapsed"
            class="font-medium whitespace-nowrap"
          >
            Notifications
          </span>

          <div
            v-if="themeStore.isSidebarCollapsed"
            class="absolute left-full ml-3 px-2 py-1 bg-gray-700 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50"
          >
            Notifications
          </div>
        </button>

        <router-link
          v-if="authStore.isOwner"
          to="/admin"
          class="flex items-center gap-3 p-3 rounded-xl transition-colors group relative"
          :class="[
            route.path.startsWith('/admin')
              ? 'bg-primary-600/20 text-primary-600 dark:text-primary-400'
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
              d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"
            ></path>
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
            ></path>
          </svg>
          <span
            v-if="!themeStore.isSidebarCollapsed"
            class="font-medium whitespace-nowrap"
            >{{ $t("nav.dashboard") }}</span
          >
          <div
            v-if="themeStore.isSidebarCollapsed"
            class="absolute left-full ml-3 px-2 py-1 bg-gray-700 text-white text-xs rounded opacity-0 group-hover:opacity-100 pointer-events-none whitespace-nowrap z-50"
          >
            {{ $t("nav.dashboard") }}
          </div>
        </router-link>
      </nav>

      <div class="p-4 border-t border-app-border">
        <!-- Theme Toggle -->
        <button
          @click="themeStore.toggleTheme"
          class="w-full flex items-center gap-3 p-2 mb-2 text-app-muted hover:text-app-text transition-colors"
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
          <span v-if="!themeStore.isSidebarCollapsed" class="font-medium">
            {{
              themeStore.isDark
                ? $t("settings.lightMode")
                : $t("settings.darkMode")
            }}
          </span>
        </button>

        <!-- Language Toggle -->
        <button
          @click="toggleLanguage"
          class="w-full flex items-center gap-3 p-2 mb-2 text-app-muted hover:text-app-text transition-colors"
          :class="themeStore.isSidebarCollapsed ? 'justify-center' : ''"
        >
          <svg
            xmlns="http://www.w3.org/2000/svg"
            class="w-6 h-6 shrink-0"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"
            />
          </svg>
          <span v-if="!themeStore.isSidebarCollapsed" class="font-medium">
            {{ uiStore.currentLocale === "en" ? "English" : "ខ្មែរ" }}
          </span>
        </button>

        <button
          @click="logout"
          class="flex items-center gap-3 text-red-400 hover:text-red-300 w-full p-2"
          :class="themeStore.isSidebarCollapsed ? 'justify-center' : ''"
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
              d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"
            ></path>
          </svg>
          <span v-if="!themeStore.isSidebarCollapsed" class="hidden lg:block">{{
            $t("nav.logout")
          }}</span>
        </button>
      </div>
    </aside>

    <!-- Main Content -->
    <main
      class="flex-1 overflow-auto p-4 bg-app-bg transition-colors duration-300"
    >
      <router-view />
    </main>
  </div>
  <!-- Notification Drawer -->
  <NotificationDrawer v-model="showNotifications" />
</template>
