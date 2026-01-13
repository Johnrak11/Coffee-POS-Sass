<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import { toast } from "vue-sonner";
import QrcodeVue from "qrcode.vue";

const authStore = useAuthStore();
const tables = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingTable = ref<any>(null);

const form = ref({
  table_number: "",
});

onMounted(async () => {
  await fetchTables();
});

async function fetchTables() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/tables`
    );
    tables.value = response.data;
  } catch (e) {
    console.error(e);
    toast.error("Failed to fetch tables");
  } finally {
    loading.value = false;
  }
}

function openAddModal() {
  editingTable.value = null;
  form.value = { table_number: "" };
  showModal.value = true;
}

function openEditModal(table: any) {
  editingTable.value = table;
  form.value = { table_number: table.table_number };
  showModal.value = true;
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  const loadingToast = toast.loading(
    editingTable.value ? "Updating table..." : "Adding table..."
  );

  try {
    if (editingTable.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/tables/${editingTable.value.id}`,
        form.value
      );
    } else {
      await apiClient.post(
        `/staff/admin/${shopSlug}/menu/tables`,
        form.value
      );
    }
    toast.dismiss(loadingToast);
    toast.success(editingTable.value ? "Table updated" : "Table added");
    await fetchTables();
    showModal.value = false;
  } catch (e) {
    toast.dismiss(loadingToast);
    toast.error("Failed to save table");
  }
}

async function deleteTable(id: number) {
  if (!confirm("Delete this table? All associated sessions will be lost."))
    return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  const loadingToast = toast.loading("Deleting...");

  try {
    await apiClient.delete(
      `/staff/admin/${shopSlug}/menu/tables/${id}`
    );
    toast.dismiss(loadingToast);
    toast.success("Table deleted");
    await fetchTables();
  } catch (e) {
    toast.dismiss(loadingToast);
    toast.error("Failed to delete table");
  }
}

function getTableUrl(token: string) {
  return `${window.location.origin}/table/${token}`;
}
</script>

<template>
  <div class="p-8">
    <div class="mb-8 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-gray-900 font-display">
          Table Management
        </h1>
        <p class="text-gray-500 font-medium">
          Generate and manage secure QR codes for guest ordering.
        </p>
      </div>
      <button @click="openAddModal" class="btn-primary flex items-center gap-2">
        <svg class="w-5 h-5 transition-transform group-hover:rotate-90" fill="none" stroke="currentColor"
          viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
        </svg>
        <span>Add New Table</span>
      </button>
    </div>

    <!-- Table Grid -->
    <div v-auto-animate class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8">
      <template v-if="loading">
        <div v-for="i in 4" :key="i" class="skeleton h-80 rounded-[40px]"></div>
      </template>

      <div v-for="table in tables" :key="table.id"
        class="bg-white p-6 rounded-[40px] shadow-sm border border-gray-100 hover:shadow-2xl hover:-translate-y-1 transition-all duration-300 group flex flex-col h-full bg-gradient-to-b from-white to-gray-50/30">
        <div class="flex justify-between items-start mb-6">
          <div class="flex gap-4 items-center">
            <div
              class="w-14 h-14 bg-gray-900 rounded-3xl flex items-center justify-center text-white shadow-lg shadow-gray-200 group-hover:scale-110 transition-transform duration-300">
              <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
              </svg>
            </div>
            <div>
              <span class="text-[10px] font-bold text-gray-400 uppercase tracking-widest block">
                STATION
              </span>
              <h3 class="text-2xl font-black text-gray-900">
                {{ table.table_number }}
              </h3>
            </div>
          </div>
          <div :class="[
            'px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase flex items-center gap-1.5 transition-all duration-500',
            table.status === 'available'
              ? 'bg-green-100/50 text-green-600 border border-green-200'
              : 'bg-orange-100/50 text-orange-600 border border-orange-200 animate-pulse-soft',
          ]">
            <div :class="[
              'w-1.5 h-1.5 rounded-full',
              table.status === 'available' ? 'bg-green-500' : 'bg-orange-500',
            ]"></div>
            {{ table.status }}
          </div>
        </div>

        <!-- Scanning Station Section -->
        <div
          class="flex-1 bg-white rounded-3xl p-6 border-2 border-dashed border-gray-100 group-hover:border-primary-200 group-hover:bg-primary-50/10 transition-all duration-300 mb-6 flex flex-col items-center justify-center relative overflow-hidden">
          <div class="relative z-10">
            <QrcodeVue :value="getTableUrl(table.qr_token)" :size="140" level="H"
              class="rounded-xl shadow-inner-white" />
          </div>
          <div class="mt-4 text-[10px] font-bold text-gray-400 uppercase tracking-wider text-center">
            Guest Scan to Order
          </div>

          <!-- Abstract background elements -->
          <div
            class="absolute -right-4 -bottom-4 w-24 h-24 bg-primary-100/20 rounded-full blur-2xl group-hover:bg-primary-400/10 transition-colors">
          </div>
          <div class="absolute -left-4 -top-4 w-20 h-20 bg-gray-100/50 rounded-full blur-xl"></div>
        </div>

        <div class="flex gap-3">
          <button @click="openEditModal(table)"
            class="flex-1 py-3 bg-gray-50 text-gray-700 rounded-2xl text-xs font-bold hover:bg-gray-100 hover:text-gray-900 transition-all active:scale-95 flex items-center justify-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
            </svg>
            Rename
          </button>
          <button @click="deleteTable(table.id)"
            class="p-3 bg-red-50 text-red-500 rounded-2xl hover:bg-red-100 hover:text-red-600 transition-all active:scale-95 flex items-center justify-center">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Empty State -->
    <div v-if="!loading && tables.length === 0"
      class="text-center py-32 bg-white rounded-[40px] border-2 border-dashed border-gray-100 animate-fade-in">
      <div class="w-24 h-24 bg-gray-50 rounded-[32px] flex items-center justify-center mx-auto mb-6 shadow-inner">
        <svg class="w-12 h-12 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
            d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
        </svg>
      </div>
      <h3 class="text-2xl font-black text-gray-900 mb-2">Setup Your Shop</h3>
      <p class="text-gray-500 mb-8 max-w-xs mx-auto font-medium">
        Start by adding your physical tables. We'll generate the QR codes for
        you instantly.
      </p>
      <button @click="openAddModal" class="btn-primary px-10 py-4 text-lg">
        Add Your First Table
      </button>
    </div>

    <!-- Add/Edit Modal (Refined) -->
    <div v-if="showModal"
      class="fixed inset-0 bg-gray-900/60 backdrop-blur-md z-50 flex items-center justify-center p-6">
      <div
        class="bg-white rounded-[40px] p-10 max-w-md w-full shadow-[0_20px_70px_-10px_rgba(0,0,0,0.3)] animate-scale-in">
        <div class="w-16 h-16 bg-primary-50 rounded-3xl flex items-center justify-center mb-6">
          <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z" />
          </svg>
        </div>
        <h2 class="text-2xl font-black text-gray-900 mb-2">
          {{ editingTable ? "Rename Table" : "Initialise Table" }}
        </h2>
        <p class="text-gray-500 text-sm mb-8 font-medium">
          Enter a unique identifier (e.g., T-05 or VIP-2).
        </p>

        <form @submit.prevent="handleSubmit" class="space-y-8">
          <div>
            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest mb-3">
              Table Identity
            </label>
            <input v-model="form.table_number" type="text" required autofocus placeholder="e.g. T-01, VIP-1"
              class="w-full px-6 py-5 rounded-3xl border-2 border-gray-100 focus:border-primary-500 focus:ring-4 focus:ring-primary-500/10 outline-none transition-all text-lg font-bold" />
          </div>

          <div class="flex gap-4">
            <button type="button" @click="showModal = false"
              class="flex-1 py-4 text-gray-500 font-bold hover:bg-gray-50 rounded-2xl transition-all">
              Back
            </button>
            <button type="submit"
              class="flex-2 py-4 bg-primary-600 text-white rounded-2xl font-bold shadow-lg shadow-primary-200 hover:bg-primary-500 transition-all active:scale-95">
              {{ editingTable ? "Update Info" : "Generate QR" }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<style scoped>
.animate-pulse-soft {
  animation: pulse-soft 2s infinite ease-in-out;
}

@keyframes pulse-soft {

  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }

  50% {
    opacity: 0.8;
    transform: scale(1.02);
  }
}

.shadow-inner-white {
  box-shadow: 0 0 0 8px white, 0 10px 20px -10px rgba(0, 0, 0, 0.1);
}

@media print {

  .btn-primary,
  .fixed,
  .p-8>div:first-child,
  .group button {
    display: none !important;
  }

  .grid {
    display: flex !important;
    gap: 40px !important;
    padding: 0 !important;
  }

  .group {
    border: 1px solid #eee !important;
    border-radius: 20px !important;
    box-shadow: none !important;
    page-break-inside: avoid;
    margin-bottom: 2rem;
    width: 300px !important;
  }
}
</style>
