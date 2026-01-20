import { defineStore } from "pinia";
import { ref } from "vue";
import apiClient from "@/api";
import { useUIStore } from "./ui";
import notificationSound from "@/assets/notification.mp3";

export const useNotificationStore = defineStore("notification", () => {
  const unreadCount = ref(0);
  const notifications = ref<any[]>([]);
  const isLoading = ref(false);
  const uiStore = useUIStore();

  // Poll count cheaply
  async function checkUnreadCount() {
    try {
      const res = await apiClient.get("/staff/notifications/unread-count", {
        skipLoading: true,
      } as any);

      const newCount = res.data.count;

      // Detect increase
      if (newCount > unreadCount.value) {
        // Fetch latest to show info
        await fetchAndAlertLatest();
      }

      unreadCount.value = newCount;
    } catch (e) {
      console.error("Failed to check notifications", e);
    }
  }

  async function fetchAndAlertLatest() {
    try {
      const res = await apiClient.get("/staff/notifications");
      const latest = res.data.data[0];

      if (latest) {
        // Play Sound
        try {
          const audio = new Audio(notificationSound);
          audio
            .play()
            .catch((e) =>
              console.log(
                "Audio play failed, user interaction needed first?",
                e,
              ),
            );
        } catch (e) {}

        // Show Toast
        uiStore.showToast(
          "info",
          latest.data.message || "New Notification",
          5000,
        );
      }
      // Update list while we are at it
      notifications.value = res.data.data;
    } catch (e) {
      console.error("Failed to fetch latest for alert", e);
    }
  }

  // Fetch list when opening dropdown
  async function fetchNotifications() {
    isLoading.value = true;
    try {
      const res = await apiClient.get("/staff/notifications");
      notifications.value = res.data.data; // Paginated response
    } catch (e) {
      console.error("Failed to fetch notifications", e);
    } finally {
      isLoading.value = false;
    }
  }

  async function markAsRead(id: string) {
    try {
      await apiClient.post(`/staff/notifications/${id}/read`);
      // Update local state
      const notif = notifications.value.find((n) => n.id === id);
      if (notif && !notif.read_at) {
        notif.read_at = new Date().toISOString();
        unreadCount.value = Math.max(0, unreadCount.value - 1);
      }
    } catch (e) {
      console.error("Failed to mark read", e);
    }
  }

  async function markAllRead() {
    // TODO: Implement backend endpoint for this if needed, loop for now
    // or just assume we implement a catch-all endpoint later.
    // For now, let's keep it simple.
  }

  return {
    unreadCount,
    notifications,
    isLoading,
    checkUnreadCount,
    fetchNotifications,
    markAsRead,
  };
});
