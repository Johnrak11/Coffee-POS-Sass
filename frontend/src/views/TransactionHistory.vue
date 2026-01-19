<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import apiClient from "@/api";
import { useAuthStore } from "@/stores/auth";
import { BaseButton, BaseCard } from "@/components/common";
import PaymentModal from "@/components/PaymentModal.vue";
import ReceiptModal from "@/components/ReceiptModal.vue";

const { t } = useI18n();
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
</script>

<template>
  <div class="p-8 bg-bg-secondary dark:bg-gray-900 min-h-screen">
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

    <!-- Transactions Table Card -->
    <BaseCard padding="none" shadow="md" rounded="2xl">
      <div class="overflow-x-auto">
        <table class="w-full text-left">
          <thead>
            <tr
              class="bg-gray-50/50 dark:bg-gray-800 border-b border-border-primary dark:border-gray-700"
            >
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
              >
                {{ t("order.orderNumber") }} / {{ t("transaction.method") }}
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
              >
                {{ t("order.items") }}
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
              >
                {{ t("transaction.amount") }}
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
              >
                {{ t("transaction.status") }}
              </th>
              <th
                class="px-6 py-4 text-xs font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider"
              >
                {{ t("transaction.date") }}
              </th>
              <th class="px-6 py-4"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50 dark:divide-gray-800">
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
            <template v-else v-for="tx in transactions" :key="tx.id">
              <tr
                class="hover:bg-gray-50/50 dark:hover:bg-gray-800/50 transition-colors"
              >
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
                  <div
                    v-if="
                      tx.md5_hash &&
                      tx.md5_hash !== 'N/A' &&
                      !tx.md5_hash.startsWith('CASH')
                    "
                    class="text-[10px] text-gray-400 dark:text-gray-500 mt-1 font-mono truncate max-w-[120px]"
                    title="MD5 Hash"
                  >
                    MD5: {{ tx.md5_hash.substring(0, 8) }}...
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
                          Number(tx.amount)
                        ) + " ៛"
                      : new Intl.NumberFormat("en-US", {
                          style: "currency",
                          currency: "USD",
                        }).format(tx.amount)
                  }}
                </td>
                <td class="px-6 py-4">
                  <span
                    :class="[
                      'px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wider',
                      tx.verified_at
                        ? 'bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400'
                        : 'bg-warning-100 text-warning-700 dark:bg-warning-900/30 dark:text-warning-400',
                    ]"
                  >
                    {{
                      tx.verified_at
                        ? t("transaction.verified")
                        : t("order.pending")
                    }}
                  </span>
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
                <td class="px-6 py-4 text-right space-x-2">
                  <button
                    @click="tx.expanded = !tx.expanded"
                    class="text-xs font-bold text-primary-600 dark:text-primary-400 hover:text-primary-700 dark:hover:text-primary-300 transition-colors"
                  >
                    {{
                      tx.expanded
                        ? t("transaction.hideInfo")
                        : t("transaction.showInfo")
                    }}
                  </button>
                  <BaseButton
                    v-if="!tx.verified_at && tx.order"
                    @click="openPayment(tx.order)"
                    variant="primary"
                    size="sm"
                  >
                    {{ t("order.payment") }}
                  </BaseButton>
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
    </BaseCard>

    <!-- Pagination -->
    <div
      class="mt-6 flex justify-between items-center text-sm text-gray-500 dark:text-gray-400"
      v-if="pagination.last_page > 1"
    >
      <div>
        {{ t("common.showing") || "Showing" }} {{ t("common.page") || "page" }}
        {{ pagination.current_page }} {{ t("common.of") || "of" }}
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
