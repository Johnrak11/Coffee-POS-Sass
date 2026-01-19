<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import apiClient from "@/api";
import { toast } from "vue-sonner";
import { BaseButton } from "@/components/common";

// Components
import ShopsTable from "./super-admin/components/ShopsTable.vue";
import CreateShopModal from "./super-admin/components/CreateShopModal.vue";
import EditShopModal from "./super-admin/components/EditShopModal.vue";

const stats = ref({
  total_shops: 0,
  total_revenue: 0,
  active_shops: 0,
  revenue_growth: 0,
});

const shops = ref<any[]>([]);
const loading = ref(true);

// Modal States
const showCreateModal = ref(false);
const showEditModal = ref(false);
const selectedShop = ref<any>(null);

onMounted(async () => {
  await fetchDashboardData();
});

async function fetchDashboardData() {
  loading.value = true;
  try {
    const statsRes = await apiClient.get(
      "/super-admin/stats"
    );
    stats.value = statsRes.data;

    const shopsRes = await apiClient.get(
      "/super-admin/shops"
    );
    shops.value = shopsRes.data;
  } catch (error) {
    console.error("Failed to fetch super admin data", error);
    toast.error("Failed to load dashboard data");
  } finally {
    loading.value = false;
  }
}

async function toggleShopStatus(shop: any) {
  try {
    const response = await apiClient.post(
      `/super-admin/shops/${shop.id}/toggle-status`
    );
    shop.status = response.data.status;
    toast.success(response.data.message);
  } catch (error) {
    toast.error("Failed to update shop status");
  }
}

function openEditModal(shop: any) {
  selectedShop.value = shop;
  showEditModal.value = true;
}

const formattedRevenue = computed(() => {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(stats.value.total_revenue);
});
</script>

<template>
  <div class="min-h-screen bg-gray-50 p-8">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-12">
        <div>
          <h1 class="text-4xl font-black text-gray-900 tracking-tight mb-2">
            Super Admin
          </h1>
          <p class="text-gray-500 font-medium">
            Platform Overview & Management
          </p>
        </div>
        <div class="flex items-center gap-4">
          <div class="bg-white px-4 py-2 rounded-xl border border-gray-200 shadow-sm flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-green-500 animate-pulse"></span>
            <span class="text-sm font-bold text-gray-700">System Healthy</span>
          </div>
        </div>
      </div>

      <!-- Stats Grid -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group">
          <p class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-1">
            Total Revenue
          </p>
          <div class="text-3xl font-black text-gray-900">
            {{ formattedRevenue }}
          </div>
          <div class="mt-4 flex items-center text-green-600 text-sm font-bold">
            <span>↑ {{ stats.revenue_growth }}%</span>
            <span class="text-gray-400 font-medium ml-2">vs last month</span>
          </div>
        </div>
        <div class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm relative overflow-hidden group">
          <p class="text-sm font-bold text-gray-400 uppercase tracking-wider mb-1">
            Active Shops
          </p>
          <div class="text-3xl font-black text-gray-900">
            {{ stats.active_shops }}
          </div>
          <div class="mt-4 flex items-center text-orange-600 text-sm font-bold">
            <span>{{ stats.total_shops }} Total</span>
            <span class="text-gray-400 font-medium ml-2">shops</span>
          </div>
        </div>
      </div>

      <!-- Shops Table -->
      <ShopsTable :shops="shops" @toggle-status="toggleShopStatus" @edit="openEditModal">
        <template #actions>
          <BaseButton variant="primary" @click="showCreateModal = true">
            <template #prefix><span>+</span></template>
            Add New Shop
          </BaseButton>
        </template>
      </ShopsTable>

      <!-- Modals -->
      <CreateShopModal v-model="showCreateModal" @created="fetchDashboardData" />

      <EditShopModal v-if="selectedShop" v-model="showEditModal" :shop="selectedShop" @updated="fetchDashboardData" />
    </div>
  </div>
</template>
