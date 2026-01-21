<script setup lang="ts">
import { useNotificationStore } from "@/stores/notification";
import { useThemeStore } from "@/stores/theme";
import { useRouter } from "vue-router";
import { computed } from "vue";

const props = defineProps<{
  modelValue: boolean; // Control visibility
}>();

const emit = defineEmits(["update:modelValue"]);

const notificationStore = useNotificationStore();
const themeStore = useThemeStore();
const router = useRouter();

function closeDrawer() {
  emit("update:modelValue", false);
}

function handleNotificationClick(notification: any) {
  notificationStore.markAsRead(notification.id);
  if (notification.data?.id) {
    router.push(`/pos/orders`); // Logic from NotificationView
    closeDrawer();
  }
}

async function markAllRead() {
  // Ideally store should support markAllAsRead, looping for now or implementing if store has it
  // Assuming backend or store loop
  for (const n of notificationStore.notifications) {
    if (!n.read_at) {
      await notificationStore.markAsRead(n.id);
    }
  }
}
</script>

<template>
  <div
    class="relative z-50"
    aria-labelledby="slide-over-title"
    role="dialog"
    aria-modal="true"
    v-if="modelValue"
  >
    <!-- Background backdrop -->
    <div
      class="fixed inset-0 bg-gray-500/20 backdrop-blur-sm transition-opacity"
      @click="closeDrawer"
      :class="
        modelValue
          ? 'opacity-100 ease-in-out duration-300'
          : 'opacity-0 ease-in-out duration-300'
      "
    ></div>

    <div class="fixed inset-0 overflow-hidden pointer-events-none">
      <div class="absolute inset-0 overflow-hidden">
        <div
          class="pointer-events-none fixed inset-y-0 right-0 flex max-w-full pl-10"
        >
          <!-- Drawer Panel -->
          <div
            class="pointer-events-auto w-screen max-w-md transform transition ease-in-out duration-300 sm:duration-500 bg-white dark:bg-gray-800 shadow-xl flex flex-col h-full"
            :class="modelValue ? 'translate-x-0' : 'translate-x-full'"
          >
            <!-- Header -->
            <div
              class="flex items-center justify-between px-4 py-6 sm:px-6 border-b border-gray-200 dark:border-gray-700"
            >
              <div>
                <h2
                  class="text-lg font-bold text-gray-900 dark:text-gray-100"
                  id="slide-over-title"
                >
                  Notifications
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400">
                  {{
                    notificationStore.notifications.filter((n) => !n.read_at)
                      .length
                  }}
                  unread
                </p>
              </div>
              <div class="flex items-center gap-2">
                <button
                  v-if="notificationStore.notifications.some((n) => !n.read_at)"
                  @click="markAllRead"
                  class="text-xs font-medium text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 mr-2"
                >
                  Mark all read
                </button>
                <button
                  @click="closeDrawer"
                  type="button"
                  class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none"
                >
                  <span class="sr-only">Close panel</span>
                  <svg
                    class="h-6 w-6"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke-width="1.5"
                    stroke="currentColor"
                    aria-hidden="true"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </div>
            </div>

            <!-- Content -->
            <div class="flex-1 overflow-y-auto px-4 py-6 sm:px-6">
              <!-- Empty State -->
              <div
                v-if="notificationStore.notifications.length === 0"
                class="flex flex-col items-center justify-center h-64 text-center"
              >
                <div
                  class="w-16 h-16 bg-gray-100 dark:bg-gray-700/50 rounded-full flex items-center justify-center mb-4 text-gray-400"
                >
                  <svg
                    class="w-8 h-8"
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
                <p class="text-gray-500 dark:text-gray-400">
                  No notifications yet
                </p>
                <button
                  @click="notificationStore.fetchNotifications"
                  class="mt-4 text-sm text-primary-600 hover:text-primary-700"
                >
                  Refresh
                </button>
              </div>

              <!-- List -->
              <div v-else class="space-y-3">
                <div
                  v-for="notif in notificationStore.notifications"
                  :key="notif.id"
                  @click="handleNotificationClick(notif)"
                  class="p-4 rounded-xl border border-gray-100 dark:border-gray-700 bg-white dark:bg-gray-800/50 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors cursor-pointer relative overflow-hidden group"
                  :class="{
                    'bg-primary-50/30 dark:bg-primary-900/10': !notif.read_at,
                  }"
                >
                  <!-- Unread Indicator -->
                  <div
                    v-if="!notif.read_at"
                    class="absolute left-0 top-0 bottom-0 w-1 bg-primary-500"
                  ></div>

                  <div class="flex gap-3">
                    <div class="mt-1">
                      <div
                        class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 flex items-center justify-center text-primary-600 dark:text-primary-400"
                      >
                        <svg
                          class="w-4 h-4"
                          fill="none"
                          stroke="currentColor"
                          viewBox="0 0 24 24"
                        >
                          <path
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"
                          />
                        </svg>
                      </div>
                    </div>
                    <div class="flex-1 min-w-0">
                      <p
                        class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                      >
                        {{ notif.data.message }}
                      </p>
                      <div class="flex items-center gap-2 mt-1">
                        <p class="text-xs text-gray-500 dark:text-gray-400">
                          #{{ notif.data.order_number }}
                        </p>
                        <span class="text-xs text-gray-300 dark:text-gray-600"
                          >•</span
                        >
                        <p
                          class="text-xs text-primary-600 dark:text-primary-400 font-medium"
                        >
                          ${{ Number(notif.data.total).toFixed(2) }}
                        </p>
                      </div>
                      <p class="text-[10px] text-gray-400 mt-2">
                        {{ new Date(notif.created_at).toLocaleString() }}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Footer (Optional actions) -->
            <div
              class="border-t border-gray-200 dark:border-gray-700 px-4 py-4 sm:px-6"
            >
              <button
                @click="notificationStore.fetchNotifications"
                class="flex items-center justify-center w-full px-4 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 text-sm font-medium rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition"
              >
                Refresh Feed
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
