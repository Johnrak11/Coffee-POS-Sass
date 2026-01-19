<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import debounce from "lodash/debounce";
import apiClient from "@/api";
import { useAuthStore } from "@/stores/auth";
import { useUIStore } from "@/stores/ui";
import { BaseButton, BaseCard } from "@/components/common";
import PaymentModal from "@/components/PaymentModal.vue";
import ReceiptModal from "@/components/ReceiptModal.vue";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();
const transactions = ref<any[]>([]);
const loading = ref(true);
const pagination = ref({
  current_page: 1,
  last_page: 1,
  total: 0,
});

// Filters
const searchQuery = ref("");
const selectedStatus = ref("all");

// Watchers
watch(
  searchQuery,
  debounce(() => {
    fetchTransactions(1);
  }, 300),
);

watch(selectedStatus, () => {
  fetchTransactions(1);
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
      `/staff/admin/${shopSlug}/transactions?page=${page}&search=${searchQuery.value}&status=${selectedStatus.value}`,
    );

    const data = response.data;
    if (data.data) {
      transactions.value = data.data.map((tx: any) => ({
        ...tx,
        expanded: false,
      }));
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

function openPayment(order: any) {
  selectedOrder.value = order;
  showPaymentModal.value = true;
}

function handlePaymentSuccess() {
  fetchTransactions(pagination.value.current_page);
}

function handlePrint(order: any) {
  receiptData.value = {
    items: order.items,
    total: Number(order.total_amount),
    cashReceived: Number(order.received_amount || order.total_amount),
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

function copyToClipboard(text: string) {
  navigator.clipboard.writeText(text);
  uiStore.showToast("success", "MD5 copied to clipboard");
}
</script>

<template>
  <div
    class="h-full flex flex-col p-6 bg-bg-secondary dark:bg-gray-900 overflow-hidden"
  >
    <!-- Header -->
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-text-primary dark:text-white">
        {{ t("transaction.transactionHistory") }}
      </h1>
      <p class="text-text-secondary dark:text-gray-400 mt-1">
        {{
          t("transaction.viewAllTransactions") ||
          "View and track all transactions across your shop."
        }}
      </p>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap gap-4">
      <div class="flex-1 min-w-[300px]">
        <input
          v-model="searchQuery"
          type="text"
          :placeholder="t('common.search') || 'Search Order #...'"
          class="w-full px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none"
        />
      </div>
      <select
        v-model="selectedStatus"
        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none"
      >
        <option value="all">
          {{ t("common.allStatus") || "All Status" }}
        </option>
        <option value="verified">
          {{ t("transaction.verified") || "Verified" }}
        </option>
        <option value="failed">
          {{ t("transaction.failed") || "Failed" }}
        </option>
      </select>
    </div>

    <!-- Transactions Table Card -->
    <div
      class="flex-1 bg-app-surface rounded-2xl border border-app-border overflow-hidden flex flex-col transition-colors duration-300"
    >
      <div class="overflow-auto flex-1 custom-scrollbar">
        <table class="w-full text-left">
          <thead
            class="bg-app-bg text-app-muted text-xs uppercase font-bold sticky top-0 border-b border-app-border"
          >
            <tr>
              <th class="px-6 py-4">
                {{ t("order.orderNumber") }} / {{ t("transaction.method") }}
              </th>
              <th class="px-6 py-4">
                {{ t("order.items") }}
              </th>
              <th class="px-6 py-4">
                {{ t("transaction.amount") }}
              </th>
              <th class="px-6 py-4">Received</th>
              <th class="px-6 py-4">Transaction ID</th>
              <th class="px-6 py-4">
                {{ t("transaction.status") }}
              </th>
              <th class="px-6 py-4">MD5</th>
              <th class="px-6 py-4">
                {{ t("transaction.date") }}
              </th>
            </tr>
          </thead>
          <tbody class="divide-y divide-app-border">
            <!-- Loading State -->
            <template v-if="loading">
              <tr v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="6" class="px-6 py-4">
                  <div
                    class="h-4 bg-gray-100 dark:bg-gray-800 rounded-full w-full"
                  ></div>
                </td>
              </tr>
            </template>

            <!-- Transaction Rows -->
            <!-- eslint-disable-next-line vue/no-v-for-template-key -->
            <template v-else v-for="tx in transactions" :key="tx.id">
              <tr class="hover:bg-app-bg transition-colors">
                <td class="px-6 py-4">
                  <div
                    class="font-mono text-sm text-gray-600 dark:text-gray-300 font-bold"
                  >
                    {{ tx.order?.order_number }}
                  </div>
                  <div
                    class="text-[10px] uppercase font-bold tracking-wider mt-1"
                    :class="
                      tx.payment_method === 'khqr'
                        ? 'text-blue-600 dark:text-blue-400'
                        : 'text-green-600 dark:text-green-400'
                    "
                  >
                    {{ tx.payment_method }}
                  </div>
                </td>
                <td class="px-6 py-4">
                  <div class="flex -space-x-2" v-if="tx.order?.items">
                    <div
                      v-for="(item, idx) in tx.order.items.slice(0, 3)"
                      :key="idx"
                      class="w-8 h-8 rounded-full bg-primary-100 dark:bg-primary-900/30 border-2 border-white dark:border-gray-800 flex items-center justify-center text-[10px] font-bold text-primary-600 dark:text-primary-400"
                    >
                      {{ item.quantity }}x
                    </div>
                    <div
                      v-if="tx.order.items.length > 3"
                      class="w-8 h-8 rounded-full bg-gray-100 dark:bg-gray-700 border-2 border-white dark:border-gray-800 flex items-center justify-center text-[10px] font-bold text-gray-400 dark:text-gray-500"
                    >
                      +{{ tx.order.items.length - 3 }}
                    </div>
                  </div>
                </td>
                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                  {{
                    tx.currency === "KHR"
                      ? new Intl.NumberFormat("en-US").format(
                          Number(tx.amount),
                        ) + " ៛"
                      : new Intl.NumberFormat("en-US", {
                          style: "currency",
                          currency: "USD",
                        }).format(tx.amount)
                  }}
                </td>
                <td class="px-6 py-4 font-bold text-gray-900 dark:text-white">
                  {{
                    tx.order?.received_amount
                      ? tx.currency === "KHR"
                        ? new Intl.NumberFormat("en-US").format(
                            Number(tx.order.received_amount),
                          ) + " ៛"
                        : new Intl.NumberFormat("en-US", {
                            style: "currency",
                            currency: "USD",
                          }).format(tx.order.received_amount)
                      : "-"
                  }}
                </td>
                <td
                  class="px-6 py-4 text-xs font-mono text-gray-500 max-w-[150px] truncate"
                  title="Transaction ID"
                >
                  {{ tx.order?.payment_metadata?.hash || "-" }}
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="[
                      'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                      tx.verified_at || tx.order?.payment_status === 'paid'
                        ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400'
                        : 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                    ]"
                  >
                    {{
                      tx.verified_at || tx.order?.payment_status === "paid"
                        ? t("transaction.verified")
                        : "Failed"
                    }}
                  </span>
                </td>
                <td class="px-6 py-4">
                  <div
                    v-if="
                      tx.md5_hash &&
                      tx.md5_hash !== 'N/A' &&
                      !tx.md5_hash.startsWith('CASH')
                    "
                    class="text-[10px] text-gray-400 dark:text-gray-500 font-mono truncate max-w-[100px] cursor-pointer hover:text-primary-500"
                    title="Click to Copy MD5"
                    @click="copyToClipboard(tx.md5_hash)"
                  >
                    {{ tx.md5_hash.substring(0, 8) }}...
                  </div>
                  <span v-else></span>
                </td>
                <td class="px-6 py-4">
                  <p class="text-sm text-gray-900 dark:text-gray-200">
                    {{ new Date(tx.created_at).toLocaleDateString() }}
                  </p>
                  <p
                    class="text-[10px] text-gray-400 dark:text-gray-500 font-medium"
                  >
                    {{ new Date(tx.created_at).toLocaleTimeString() }}
                  </p>
                </td>
              </tr>

              <!-- Expanded Details Row -->
              <tr v-if="tx.expanded" class="bg-gray-50 dark:bg-gray-800/50">
                <td colspan="6" class="px-6 py-4">
                  <div
                    class="text-xs font-mono bg-gray-900 dark:bg-black text-green-400 p-4 rounded-lg overflow-x-auto"
                  >
                    <pre>{{ JSON.stringify(tx.payload, null, 2) }}</pre>
                  </div>
                  <div
                    class="mt-2 text-xs text-gray-500 dark:text-gray-400 font-mono"
                  >
                    <strong
                      >{{
                        t("transaction.fullString") || "Full String"
                      }}:</strong
                    >
                    {{ tx.khqr_string }}
                  </div>
                </td>
              </tr>
            </template>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        class="border-t border-app-border p-4 flex justify-between items-center"
      >
        <div class="text-sm text-gray-500 dark:text-gray-400">
          {{ t("common.showing") || "Showing" }}
          {{ t("common.page") || "page" }} {{ pagination.current_page }}
          {{ t("common.of") || "of" }}
          {{ pagination.last_page }}
        </div>
        <div class="flex gap-2">
          <BaseButton
            @click="fetchTransactions(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            variant="secondary"
            size="sm"
          >
            {{ t("common.previous") || "Previous" }}
          </BaseButton>
          <BaseButton
            @click="fetchTransactions(pagination.current_page + 1)"
            :disabled="pagination.current_page >= pagination.last_page"
            variant="secondary"
            size="sm"
          >
            {{ t("common.next") || "Next" }}
          </BaseButton>
        </div>
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
