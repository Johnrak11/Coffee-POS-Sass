<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import apiClient from "@/api";
import { useAuthStore } from "@/stores/auth";
import { useUIStore } from "@/stores/ui";
import { BaseButton, BaseCard, BaseInput } from "@/components/common";
import QrcodeVue from "qrcode.vue";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();
const tables = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingTable = ref<any>(null);
const printingTable = ref<any>(null);

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
    uiStore.showToast("error", "Failed to fetch tables");
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

  try {
    if (editingTable.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/tables/${editingTable.value.id}`,
        form.value
      );
    } else {
      await apiClient.post(`/staff/admin/${shopSlug}/menu/tables`, form.value);
    }
    uiStore.showToast(
      "success",
      editingTable.value ? "Table updated" : "Table added"
    );
    await fetchTables();
    showModal.value = false;
  } catch (e) {
    uiStore.showToast("error", "Failed to save table");
  }
}

async function deleteTable(id: number) {
  if (!confirm("Delete this table? All associated sessions will be lost."))
    return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";

  try {
    await apiClient.delete(`/staff/admin/${shopSlug}/menu/tables/${id}`);
    uiStore.showToast("success", "Table deleted");
    await fetchTables();
  } catch (e) {
    uiStore.showToast("error", "Failed to delete table");
  }
}

function getTableUrl(token: string) {
  return `${window.location.origin}/table/${token}`;
}

async function printOneTable(table: any) {
  printingTable.value = table;
  // Wait for DOM update so the print section renders with the correct data
  setTimeout(() => {
    window.print();
    // Optional: reset after print dialog closes (though JS execution pauses in some browsers)
  }, 100);
}
</script>

<template>
  <div class="p-8 bg-bg-secondary dark:bg-gray-900 min-h-screen">
    <div class="mb-8 flex justify-between items-end">
      <div>
        <h1
          class="text-3xl font-bold text-text-primary dark:text-white font-display"
        >
          {{ t("nav.tables") || "Table Management" }}
        </h1>
        <p class="text-text-secondary dark:text-gray-400 font-medium">
          Generate and manage secure QR codes for customer ordering.
        </p>
      </div>
      <BaseButton variant="primary" size="md" @click="openAddModal">
        <svg
          class="w-5 h-5 mr-2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2.5"
            d="M12 4v16m8-8H4"
          />
        </svg>
        Add New Table
      </BaseButton>
    </div>

    <!-- Table Grid -->
    <div
      class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-8"
    >
      <template v-if="loading">
        <div
          v-for="i in 4"
          :key="i"
          class="h-80 bg-white dark:bg-gray-800 rounded-[40px] border border-gray-100 dark:border-gray-700 animate-pulse"
        ></div>
      </template>

      <BaseCard
        v-for="table in tables"
        :key="table.id"
        padding="md"
        shadow="md"
        hover
        rounded="2xl"
        class="flex flex-col h-full bg-gradient-to-b from-white to-gray-50/30 dark:from-gray-800 dark:to-gray-800/50"
      >
        <div class="flex justify-between items-start mb-6">
          <div class="flex gap-4 items-center">
            <div>
              <span
                class="text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-widest block"
              >
                STATION
              </span>
              <h3 class="text-2xl font-black text-gray-900 dark:text-white">
                {{ table.table_number }}
              </h3>
            </div>
          </div>
          <div
            :class="[
              'px-3 py-1.5 rounded-xl text-[10px] font-bold uppercase flex items-center gap-1.5 transition-all duration-500',
              table.status === 'available'
                ? 'bg-success-100 dark:bg-success-900/30 text-success-600 dark:text-success-400 border border-success-200 dark:border-success-800'
                : 'bg-primary-100 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 border border-primary-200 dark:border-primary-800',
            ]"
          >
            <div
              :class="[
                'w-1.5 h-1.5 rounded-full',
                table.status === 'available'
                  ? 'bg-success-500'
                  : 'bg-primary-500',
              ]"
            ></div>
            {{ table.status }}
          </div>
        </div>

        <!-- Scanning Station Section -->
        <div
          class="flex-1 bg-white dark:bg-gray-900 rounded-3xl p-6 border-2 border-dashed border-gray-100 dark:border-gray-700 hover:border-primary-200 dark:hover:border-primary-800 transition-all duration-300 mb-6 flex flex-col items-center justify-center relative overflow-hidden"
        >
          <div class="relative z-10">
            <QrcodeVue
              :value="getTableUrl(table.qr_token)"
              :size="140"
              level="H"
              class="rounded-xl shadow-inner-white dark:shadow-none"
            />
          </div>
          <div
            class="mt-4 text-[10px] font-bold text-gray-400 dark:text-gray-500 uppercase tracking-wider text-center"
          >
            Guest Scan to Order
          </div>
        </div>

        <div class="flex gap-3">
          <BaseButton
            variant="secondary"
            size="sm"
            class="!px-3"
            @click="printOneTable(table)"
            title="Print QR"
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
                d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
              />
            </svg>
          </BaseButton>
          <BaseButton
            variant="secondary"
            size="sm"
            fullWidth
            @click="openEditModal(table)"
          >
            <svg
              class="w-4 h-4 mr-2"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
              />
            </svg>
            Rename
          </BaseButton>
          <BaseButton variant="danger" size="sm" @click="deleteTable(table.id)">
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
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              />
            </svg>
          </BaseButton>
        </div>
      </BaseCard>
    </div>

    <!-- Empty State -->
    <BaseCard
      v-if="!loading && tables.length === 0"
      padding="lg"
      shadow="md"
      rounded="2xl"
      class="text-center"
    >
      <div
        class="w-24 h-24 bg-gray-50 dark:bg-gray-800 rounded-[32px] flex items-center justify-center mx-auto mb-6"
      >
        <svg
          class="w-12 h-12 text-gray-300 dark:text-gray-600"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="1.5"
            d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"
          />
        </svg>
      </div>
      <h3 class="text-2xl font-black text-gray-900 dark:text-white mb-2">
        Setup Your Shop
      </h3>
      <p
        class="text-gray-500 dark:text-gray-400 mb-8 max-w-xs mx-auto font-medium"
      >
        Start by adding your physical tables. We'll generate the QR codes for
        you instantly.
      </p>
      <BaseButton variant="primary" size="lg" @click="openAddModal">
        Add Your First Table
      </BaseButton>
    </BaseCard>

    <!-- Add/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-gray-900/60 backdrop-blur-md z-50 flex items-center justify-center p-6"
    >
      <BaseCard padding="lg" shadow="lg" rounded="2xl" class="max-w-md w-full">
        <div
          class="w-16 h-16 bg-primary-50 dark:bg-primary-900/30 rounded-3xl flex items-center justify-center mb-6"
        >
          <svg
            class="w-8 h-8 text-primary-600 dark:text-primary-400"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"
            />
          </svg>
        </div>
        <h2 class="text-2xl font-black text-gray-900 dark:text-white mb-2">
          {{ editingTable ? "Rename Table" : "Initialize Table" }}
        </h2>
        <p class="text-gray-500 dark:text-gray-400 text-sm mb-8 font-medium">
          Enter a unique identifier (e.g., T-05 or VIP-2).
        </p>

        <form @submit.prevent="handleSubmit" class="space-y-8">
          <BaseInput
            v-model="form.table_number"
            label="Table Identity"
            placeholder="e.g. T-01, VIP-1"
            required
            autofocus
            class="text-lg font-bold"
          />

          <div class="flex gap-4">
            <BaseButton
              type="button"
              variant="ghost"
              fullWidth
              @click="showModal = false"
            >
              {{ t("common.cancel") }}
            </BaseButton>
            <BaseButton type="submit" variant="primary" fullWidth>
              {{ editingTable ? "Update Info" : "Generate QR" }}
            </BaseButton>
          </div>
        </form>
      </BaseCard>
    </div>

    <!-- Printable Area (Hidden normally, shown on print) -->
    <div v-if="printingTable" class="print-only hidden">
      <div
        class="flex flex-col items-center justify-center h-full w-full p-8 text-center border-4 border-black box-border"
      >
        <!-- Header / Logo -->
        <div class="mb-8">
          <img
            v-if="authStore.shop?.logo_url"
            :src="authStore.shop.logo_url"
            class="h-24 mx-auto mb-4 object-contain"
          />
          <h1 class="text-4xl font-black uppercase tracking-wider mb-2">
            {{ authStore.shop?.name || "Lucky Cafe" }}
          </h1>
          <p class="text-xl text-gray-600 font-medium">Scan to order</p>
        </div>

        <!-- Big QR -->
        <div class="mb-8">
          <QrcodeVue
            :value="getTableUrl(printingTable.qr_token)"
            :size="400"
            level="H"
            class="mx-auto"
          />
        </div>

        <!-- Table Number -->
        <div class="mt-4">
          <p class="text-2xl font-bold uppercase tracking-[0.5em] text-gray-500">
            TABLE
          </p>
          <h2 class="text-8xl font-black mt-2">
            {{ printingTable.table_number }}
          </h2>
        </div>

        <div class="mt-12 text-sm text-gray-400 font-mono">
          Powered by CoffeePOS
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
.shadow-inner-white {
  box-shadow: 0 0 0 8px white, 0 10px 20px -10px rgba(0, 0, 0, 0.1);
}

@media print {
  /* Hide everything by default */
  body > * {
    display: none !important;
  }

  /* Show only the print container */
  .print-only {
    display: block !important;
    position: fixed;
    top: 0;
    left: 0;
    width: 100vw;
    height: 100vh;
    background: white;
    z-index: 9999;
  }

  /* Ensure the content inside print-only is visible */
  .print-only * {
    display: block;
  }
  
  /* Flex is needed for the centering container */
  .print-only .flex {
      display: flex !important;
  }
}
</style>
