<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const transactions = ref<any[]>([]);
const loading = ref(true);

onMounted(async () => {
  await fetchTransactions();
});

async function fetchTransactions() {
  try {
    const shopSlug = authStore.shop?.slug || "lucky-cafe";
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/transactions`
    );
    transactions.value = response.data.data;
  } catch (e) {
    console.error("Failed to fetch transactions", e);
  } finally {
    loading.value = false;
  }
}

function formatAmount(order: any) {
  if (order.payment_currency === "KHR") {
    // Calculate total in KHR based on snapshot or default
    const rate = Number(order.exchange_rate_snapshot) || 4100;
    const khrTotal = Math.ceil((order.total_amount * rate) / 100) * 100;
    return new Intl.NumberFormat("en-US").format(khrTotal) + " ៛";
  }
  // Default USD
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(order.total_amount);
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
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-50">
            <template v-if="loading">
              <tr v-for="i in 5" :key="i" class="animate-pulse">
                <td colspan="6" class="px-6 py-4">
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
              <td class="px-6 py-4 font-mono text-sm text-gray-600">
                {{ order.order_number }}
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
                    getStatusBadge(order.payment_status),
                  ]"
                >
                  {{ order.payment_status }}
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
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>
