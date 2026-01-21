<script setup lang="ts">
import { ref, onMounted, watch } from "vue";
import { useAuthStore } from "@/stores/auth";
import apiClient from "@/api";
import InvoiceModal from "@/components/InvoiceModal.vue";
import OrderDetailModal from "@/components/OrderDetailModal.vue";
import PaymentModal from "@/components/PaymentModal.vue";

const authStore = useAuthStore();
const loading = ref(false);
const orders = ref<any[]>([]);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

// Filters
const filters = ref({
  status: "",
  search: "",
  start_date: "",
  end_date: "",
});

const showDetailsModal = ref(false);
const showInvoiceModal = ref(false);
const selectedOrder = ref<any>(null);

import { useUIStore } from "@/stores/ui";

const uiStore = useUIStore();

onMounted(() => {
  fetchOrders();
});

watch(
  () => filters.value,
  () => {
    pagination.value.current_page = 1; // Reset page on filter change
    fetchOrders();
  },
  { deep: true },
);

// Auto-refresh when new order arrives
watch(
  () => uiStore.orderRefreshSignal,
  () => {
    // Keep current page, just refresh data
    fetchOrders(pagination.value.current_page);
  },
);

async function fetchOrders(page = 1) {
  if (!authStore.shop) return;
  loading.value = true;
  try {
    const params: Record<string, any> = {
      shop_id: authStore.shop.id,
      page: page,
      payment_status: filters.value.status,
      search: filters.value.search,
      start_date: filters.value.start_date,
      end_date: filters.value.end_date,
    };

    // Clean empty params
    Object.keys(params).forEach((key) => {
      const k = key as keyof typeof params;
      if (params[k] === "" || params[k] === null) {
        delete params[k];
      }
    });

    const response = await apiClient.get("/staff/orders", { params });
    orders.value = response.data.data;
    pagination.value = {
      current_page: response.data.current_page,
      last_page: response.data.last_page,
      total: response.data.total,
    };
  } catch (e) {
    console.error("Failed to fetch orders", e);
  } finally {
    loading.value = false;
  }
}

function viewDetails(order: any) {
  console.log("Viewing details for order:", order);
  selectedOrder.value = order;
  showDetailsModal.value = true;
}

async function markAsPaid(order: any) {
  if (!confirm("Are you sure you want to mark this order as PAID?")) return;
  await updateStatus(order, "paid");
}

async function rejectOrder(order: any) {
  if (!confirm("Are you sure you want to REJECT this order?")) return;
  await updateStatus(order, "rejected");
}

async function updateStatus(order: any, status: string) {
  try {
    const response = await apiClient.put(
      `/staff/orders/${order.id}/payment-status`,
      {
        status: status,
      },
    );

    // Refresh list
    await fetchOrders(pagination.value.current_page);

    // Update selected order if modal is open
    if (selectedOrder.value && selectedOrder.value.id === order.id) {
      selectedOrder.value = response.data.order;
    }

    // Auto-show receipt for paid orders
    if (status === "paid" && response.data.success) {
      selectedOrder.value = response.data.order;
      showInvoiceModal.value = true;
      // Close details modal if open
      showDetailsModal.value = false;
    } else if (status === "rejected") {
      showDetailsModal.value = false; // Close details on reject
    }
  } catch (e) {
    console.error(e);
    alert("Failed to update status");
  }
}

async function confirmOrder(order: any) {
  try {
    const response = await apiClient.post(`/staff/orders/${order.id}/confirm`);
    if (response.data.success) {
      uiStore.showToast("success", "Order Confirmed");
      fetchOrders(pagination.value.current_page);
    }
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to confirm order");
  }
}

const showPaymentModal = ref(false);

// ... (existing code)

function openPayment(order: any) {
  selectedOrder.value = order;
  showPaymentModal.value = true;
  showDetailsModal.value = false;
}

function handlePaymentSuccess() {
  fetchOrders(pagination.value.current_page);
  showPaymentModal.value = false;
  showDetailsModal.value = false; // Close details if open
}

function printOrder(order: any) {
  selectedOrder.value = order;
  showInvoiceModal.value = true;
  showDetailsModal.value = false;
}

function formatDate(date: string) {
  return new Date(date).toLocaleString();
}

function formatAmount(order: any) {
  if (order.payment_currency === "KHR") {
    // Backend stores total_amount in KHR if payment_currency is KHR.
    return new Intl.NumberFormat("en-US").format(order.total_amount) + " ៛";
  }
  // Default USD
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(order.total_amount);
}
</script>

<template>
  <div
    class="h-full flex flex-col bg-app-bg text-app-text p-6 overflow-hidden transition-colors duration-300"
  >
    <!-- Header -->
    <div class="flex justify-between items-center mb-6">
      <h1 class="text-2xl font-bold">Order History</h1>
      <div class="flex gap-2">
        <!-- Filters -->
        <select
          v-model="filters.status"
          class="bg-app-surface border border-app-border rounded-xl px-4 py-2 text-sm text-app-text focus:ring-2 focus:ring-primary-500 outline-none transition-colors"
        >
          <option value="">All Statuses</option>
          <option value="pending">Pending</option>
          <option value="paid">Paid</option>
          <option value="failed">Failed</option>
        </select>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Search Order #"
          class="bg-app-surface border border-app-border rounded-xl px-4 py-2 text-sm text-app-text placeholder-app-muted focus:ring-2 focus:ring-primary-500 outline-none transition-colors"
        />
      </div>
    </div>

    <!-- Table Container -->
    <div
      class="flex-1 bg-app-surface rounded-2xl border border-app-border overflow-hidden flex flex-col transition-colors duration-300"
    >
      <div class="overflow-x-auto flex-1">
        <table class="w-full text-left">
          <thead
            class="bg-app-bg text-app-muted text-xs uppercase font-bold sticky top-0 border-b border-app-border"
          >
            <tr>
              <th class="px-6 py-4">Queue #</th>
              <th class="px-6 py-4">Order #</th>
              <th class="px-6 py-4">Date</th>
              <th class="px-6 py-4">Processed By</th>
              <th class="px-6 py-4">Status</th>
              <th class="px-6 py-4">Payment</th>
              <th class="px-6 py-4 text-right">Total</th>
              <th class="px-6 py-4 text-center">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-app-border">
            <tr v-if="loading" class="animate-pulse">
              <td colspan="6" class="p-4 text-center text-app-muted">
                Loading...
              </td>
            </tr>
            <tr v-else-if="orders.length === 0">
              <td colspan="6" class="p-8 text-center text-app-muted">
                No orders found.
              </td>
            </tr>
            <tr
              v-for="order in orders"
              :key="order.id"
              class="hover:bg-app-bg transition-colors"
            >
              <td class="px-6 py-4 font-black text-lg text-primary-500">
                #{{ order.queue_number || "-" }}
              </td>
              <td class="px-6 py-4 font-mono text-sm text-app-text">
                {{ order.order_number }}
              </td>
              <td class="px-6 py-4 text-sm text-app-muted">
                {{ formatDate(order.created_at) }}
              </td>
              <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                  <div
                    v-if="order.user"
                    class="flex items-center gap-2 px-2 py-1 rounded-lg bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 border border-primary-100 dark:border-primary-800"
                  >
                    <span class="text-xs font-bold uppercase">Staff</span>
                    <span class="text-sm font-medium">{{
                      order.user.name
                    }}</span>
                  </div>
                  <div
                    v-else-if="order.table_session?.shop_table"
                    class="flex items-center gap-2 px-2 py-1 rounded-lg bg-orange-50 dark:bg-orange-900/20 text-orange-700 dark:text-orange-300 border border-orange-100 dark:border-orange-800"
                  >
                    <span class="text-xs font-bold uppercase">Table</span>
                    <span class="text-sm font-medium">{{
                      order.table_session.shop_table.name
                    }}</span>
                  </div>
                  <div
                    v-else
                    class="flex items-center gap-2 px-2 py-1 rounded-lg bg-gray-100 dark:bg-gray-800 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-gray-700"
                  >
                    <span class="text-xs font-bold uppercase">Guest</span>
                  </div>
                </div>
              </td>
              <td class="px-6 py-4">
                <span
                  v-if="order.confirmation_status === 'pending_confirmation'"
                  class="px-2 py-1 rounded text-xs font-bold uppercase bg-yellow-500/20 text-yellow-500 animate-pulse"
                >
                  Confirm Needed
                </span>
                <span
                  v-else
                  :class="[
                    'px-2 py-1 rounded text-xs font-bold uppercase',
                    order.payment_status === 'paid'
                      ? 'bg-green-500/20 text-green-400'
                      : order.payment_status === 'pending'
                        ? 'bg-orange-500/20 text-orange-400'
                        : 'bg-red-500/20 text-red-400',
                  ]"
                >
                  {{ order.payment_status }}
                </span>
              </td>
              <td class="px-6 py-4 text-sm capitalize text-app-text">
                {{ order.payment_method }}
              </td>
              <td class="px-6 py-4 text-right font-bold text-app-text">
                {{ formatAmount(order) }}
              </td>
              <td class="px-6 py-4 flex gap-2 justify-center items-center">
                <button
                  @click="viewDetails(order)"
                  class="p-2 bg-app-bg hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg text-app-muted hover:text-app-text transition-colors"
                  title="View Details"
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
                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"
                    />
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"
                    />
                  </svg>
                </button>
                <button
                  v-if="order.confirmation_status === 'pending_confirmation'"
                  @click="confirmOrder(order)"
                  class="p-2 bg-yellow-500/10 hover:bg-yellow-500/20 rounded-lg text-yellow-500 hover:text-yellow-400 transition-colors"
                  title="Confirm Order"
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
                      d="M5 13l4 4L19 7"
                    />
                  </svg>
                </button>
                <button
                  v-if="
                    order.payment_status === 'pending' &&
                    order.confirmation_status !== 'pending_confirmation'
                  "
                  @click="markAsPaid(order)"
                  class="p-2 bg-green-500/10 hover:bg-green-500/20 rounded-lg text-green-400 hover:text-green-300 transition-colors"
                  title="Mark as Paid"
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
                      d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </button>
                <button
                  v-if="order.payment_status === 'pending'"
                  @click="rejectOrder(order)"
                  class="p-2 bg-red-500/10 hover:bg-red-500/20 rounded-lg text-red-400 hover:text-red-300 transition-colors"
                  title="Reject Order"
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
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        class="bg-app-surface p-4 border-t border-app-border flex justify-between items-center text-sm text-app-muted"
      >
        <div>
          Showing page {{ pagination.current_page }} of
          {{ pagination.last_page }}
        </div>
        <div class="flex gap-2">
          <button
            @click="fetchOrders(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="px-3 py-1 rounded bg-app-bg hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-app-text"
          >
            Previous
          </button>
          <button
            @click="fetchOrders(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            class="px-3 py-1 rounded bg-app-bg hover:bg-gray-200 dark:hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors text-app-text"
          >
            Next
          </button>
        </div>
      </div>
    </div>

    <InvoiceModal
      :show="showInvoiceModal"
      :order="selectedOrder"
      @close="showInvoiceModal = false"
    />

    <OrderDetailModal
      :show="showDetailsModal"
      :order="selectedOrder"
      @close="showDetailsModal = false"
      @reject="rejectOrder"
      @print="printOrder"
      @pay="openPayment"
    />

    <PaymentModal
      v-if="showPaymentModal"
      :show="showPaymentModal"
      :total="selectedOrder?.total_amount || 0"
      :existing-order="selectedOrder"
      @close="showPaymentModal = false"
      @success="handlePaymentSuccess"
      @print="printOrder"
    />
  </div>
</template>
