import { defineStore } from "pinia";
import { ref } from "vue";
import apiClient from "@/api";

export interface KitchenOrder {
  id: number;
  order_number: string;
  queue_number?: string;
  created_at: string;
  fulfillment_status: "queue" | "preparing" | "served";
  table_session?: {
    shop_table?: {
      name: string;
    };
  };
  shop_table_name?: string; // derived
  elapsed_seconds?: number; // derived
  items?: any[]; // if you load them
}

export const useKitchenStore = defineStore("kitchen", () => {
  const orders = ref<KitchenOrder[]>([]);

  async function fetchOrders(shopSlug: string) {
    try {
      const response = await apiClient.get(`/staff/kitchen/${shopSlug}/orders`);
      orders.value = response.data.orders.map((o: any) => ({
        ...o,
        shop_table_name: o.table_session?.shop_table?.name || "Counter",
      }));
    } catch (e) {
      console.error("Failed to fetch kitchen orders", e);
    }
  }

  async function updateStatus(
    orderId: number,
    status: "queue" | "preparing" | "served"
  ) {
    try {
      await apiClient.post(`/staff/kitchen/orders/${orderId}/status`, {
        status,
      });
      // Updates local state optimistically or re-fetch
      const order = orders.value.find((o) => o.id === orderId);
      if (order) {
        order.fulfillment_status = status;
        // If served, remove from list or move to served tab
        if (status === "served") {
          orders.value = orders.value.filter((o) => o.id !== orderId);
        }
      }
    } catch (e) {
      console.error("Failed to update status", e);
    }
  }

  return {
    orders,
    fetchOrders,
    updateStatus,
  };
});
