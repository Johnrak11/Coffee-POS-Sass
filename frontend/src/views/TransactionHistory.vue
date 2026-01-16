<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import PaymentModal from "@/components/PaymentModal.vue";
import ReceiptModal from "@/components/ReceiptModal.vue";

const authStore = useAuthStore();
const transactions = ref<any[]>([]);
const loading = ref(true);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

// Modal State
const showPaymentModal = ref(false);
const selectedOrder = ref<any>(null);

// Receipt State
const showReceiptModal = ref(false);
const receiptData = ref<any>(null);

onMounted(async () => {
  await fetchTransactions();
});

async function fetchTransactions(page = 1) {
  try {
    loading.value = true;
    const shopSlug = authStore.shop?.slug || "lucky-cafe";
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/transactions?page=${page}`
    );

    const data = response.data;
    if (data.data) {
      transactions.value = data.data;
      pagination.value = {
        current_page: data.current_page,
        last_page: data.last_page,
        total: data.total,
      };
    } else {
      transactions.value = data;
    }
  } catch (e) {
    console.error("Failed to fetch transactions", e);
  } finally {
    loading.value = false;
  }
}

function formatAmount(order: any) {
  if (order.payment_currency === "KHR") {
    // Check if we have snapshot rate, else fallback
    // Backend stores total_amount in KHR if payment_currency is KHR.
    // So we just format it directly.
    return (
      new Intl.NumberFormat("en-US").format(Number(order.total_amount)) + " ៛"
    );
  }
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(order.total_amount);
}

function openPayment(order: any) {
  selectedOrder.value = order;
  showPaymentModal.value = true;
}

function handlePaymentSuccess() {
  fetchTransactions(pagination.value.current_page);
  // Ideally keep modal open or show success? PaymentModal handles success UI.
  // PaymentModal emits 'success' then 'close'.
}

function handlePrint(order: any) {
  // Map order to Receipt Data
  receiptData.value = {
    items: order.items,
    total: Number(order.total_amount),
    cashReceived: Number(order.received_amount || order.total_amount), // Fallback if missing
    change:
      Number(order.received_amount) > Number(order.total_amount)
        ? Number(order.received_amount) - Number(order.total_amount)
        : 0,
    orderNumber: order.order_number,
    shopName: authStore.shop?.name,
    date: new Date(order.created_at).toLocaleString(),
    currency: order.payment_currency || "USD",
  };
  showReceiptModal.value = true;
}
</script>

<template>
  <div class="p-8">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Transaction History</h1>
      <p class="text-gray-500">View and track all orders across your shop.</p>
    </div>

    <div
      class="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden"
    >
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr class="bg-gray-50/50 border-b border-gray-100">
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
              >
                Order ID
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
              >
                Table
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
              >
                Items
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
              >
                Amount
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
              >
                Status
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
              >
                Date
              </th>
              <th class="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="7" class="px-6 py-4">
                  <div class="h-4 bg-gray-100 rounded-full w-full"></div>
                </td>
              </tr>
            </template>
            <tr
              v-else
              v-for="order in transactions"
              :key="order.id"
              class="hover:bg-gray-50/50 transition-colors"
            >
              <td class="px-6 py-4">
                <div class="font-mono text-sm text-gray-600">
                  {{ order.order_number }}
                </div>
                <div
                  v-if="order.khqr_md5"
                  class="text-[10px] text-gray-400 mt-1 font-mono truncate max-w-[120px]"
                  title="KHQR MD5"
                >
                  MD5: {{ order.khqr_md5.substring(0, 8) }}...
                </div>
              </td>
              <td class="px-6 py-4">
                <span class="font-bold text-gray-800">{{
                  order.table_session?.shop_table?.name || "POS"
                }}</span>
              </td>
              <td class="px-6 py-4">
                <div class="flex -space-x-2">
                  <div
                    v-for="(item, idx) in order.items.slice(0, 3)"
                    :key="idx"
                    class="w-8 h-8 rounded-full bg-orange-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-orange-600"
                  >
                    {{ item.quantity }}x
                  </div>
                  <div
                    v-if="order.items.length > 3"
                    class="w-8 h-8 rounded-full bg-gray-100 border-2 border-white flex items-center justify-center text-[10px] font-bold text-gray-400"
                  >
                    +{{ order.items.length - 3 }}
                  </div>
                </div>
              </td>
              <td class="px-6 py-4 font-bold text-gray-900">
                {{ formatAmount(order) }}
              </td>
              <td class="px-6 py-4">
                <span
                  :class="[
                    'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                    order.payment_status === 'paid'
                      ? 'bg-green-100 text-green-700'
                      : 'bg-red-100 text-red-700',
                  ]"
                >
                  {{ order.payment_status === "paid" ? "Success" : "Failed" }}
                </span>
              </td>
              <td class="px-6 py-4">
                <p class="text-sm text-gray-900">
                  {{ new Date(order.created_at).toLocaleDateString() }}
                </p>
                <p class="text-[10px] text-gray-400 font-medium">
                  {{ new Date(order.created_at).toLocaleTimeString() }}
                </p>
              </td>
              <td class="px-6 py-4 text-right">
                <button
                  v-if="order.payment_status === 'pending'"
                  @click="openPayment(order)"
                  class="px-4 py-2 bg-primary-600 text-white text-xs font-bold rounded-lg hover:bg-primary-700 transition shadow-sm"
                >
                  Pay Now
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pagination -->
    <div
      class="mt-6 flex justify-between items-center text-sm text-gray-500"
      v-if="pagination.last_page > 1"
    >
      <div>
        Showing page {{ pagination.current_page }} of
        {{ pagination.last_page }}
      </div>
      <div class="flex gap-2">
        <button
          @click="fetchTransactions(pagination.current_page - 1)"
          :disabled="pagination.current_page <= 1"
          class="px-4 py-2 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all font-medium text-gray-700"
        >
          Previous
        </button>
        <button
          @click="fetchTransactions(pagination.current_page + 1)"
          :disabled="pagination.current_page >= pagination.last_page"
          class="px-4 py-2 rounded-xl bg-white border border-gray-200 hover:bg-gray-50 hover:border-gray-300 disabled:opacity-50 disabled:cursor-not-allowed transition-all font-medium text-gray-700"
        >
          Next
        </button>
      </div>
    </div>

    <!-- Payment Modal -->
    <PaymentModal
      v-if="showPaymentModal"
      :show="showPaymentModal"
      :total="selectedOrder?.total_amount || 0"
      :existing-order="selectedOrder"
      @close="showPaymentModal = false"
      @success="handlePaymentSuccess"
      @print="handlePrint"
    />

    <!-- Receipt Modal -->
    <ReceiptModal
      v-if="showReceiptModal && receiptData"
      :show="showReceiptModal"
      :receipt-data="receiptData"
      @close="showReceiptModal = false"
    />
  </div>
</template>
```
