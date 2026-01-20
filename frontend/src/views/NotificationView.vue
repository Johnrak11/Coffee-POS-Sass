<script setup lang="ts">
import { onMounted } from "vue";
import { useNotificationStore } from "@/stores/notification";
import { useRouter } from "vue-router";
import { useThemeStore } from "@/stores/theme";

const notificationStore = useNotificationStore();
const router = useRouter();
const themeStore = useThemeStore();

onMounted(() => {
  notificationStore.fetchNotifications();
});

function handleClick(notification: any) {
  notificationStore.markAsRead(notification.id);
  if (notification.data?.id) {
    router.push(`/pos/orders`);
  }
}
</script>

<template>
  <div class="p-6">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold text-app-text">Notifications</h1>
      <button
        @click="notificationStore.fetchNotifications"
        class="px-4 py-2 bg-primary-600 text-white rounded-lg hover:bg-primary-700 transition"
      >
        Refresh
      </button>
    </div>

    <!-- List -->
    <div class="space-y-4">
      <div
        v-if="notificationStore.notifications.length === 0"
        class="text-center py-10 text-app-muted"
      >
        No notifications found.
      </div>

      <div
        v-for="notif in notificationStore.notifications"
        :key="notif.id"
        class="flex items-start justify-between p-4 bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-app-border transition-colors hover:border-primary-500 cursor-pointer"
        :class="
          !notif.read_at ? 'bg-blue-50/10 border-l-4 border-l-primary-500' : ''
        "
        @click="handleClick(notif)"
      >
        <div class="flex items-start gap-4">
          <div
            class="p-3 bg-primary-100 dark:bg-primary-900/30 text-primary-600 rounded-full"
          >
            <svg
              class="w-6 h-6"
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
          </div>
          <div>
            <h3 class="font-bold text-app-text text-lg">
              {{ notif.data.message }}
            </h3>
            <p class="text-app-muted text-sm mt-1">
              Order #{{ notif.data.order_number }} • ${{
                Number(notif.data.total).toFixed(2)
              }}
            </p>
            <div class="flex items-center gap-2 mt-2">
              <span
                class="text-xs text-app-muted bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded"
              >
                {{ new Date(notif.created_at).toLocaleString() }}
              </span>
              <span
                v-if="!notif.read_at"
                class="text-xs font-bold text-primary-600"
              >
                New
              </span>
            </div>
          </div>
        </div>

        <div class="flex items-center">
          <svg
            class="w-5 h-5 text-app-muted"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M9 5l7 7-7 7"
            />
          </svg>
        </div>
      </div>
    </div>
  </div>
</template>
