<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import apiClient from "@/api";
import { useAuthStore } from "@/stores/auth";
import { useUIStore } from "@/stores/ui";
import { BaseButton, BaseCard, BaseInput } from "@/components/common";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();
const staff = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingStaff = ref<any>(null);
const saving = ref(false);

const form = ref({
  name: "",
  role: "cashier",
  pin: "",
});

onMounted(async () => {
  await fetchStaff();
});

async function fetchStaff() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(`/staff/admin/${shopSlug}/menu/staff`);
    staff.value = response.data;
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", t("common.error"));
  } finally {
    loading.value = false;
  }
}

function openAddModal() {
  editingStaff.value = null;
  form.value = { name: "", role: "cashier", pin: "" };
  showModal.value = true;
}

function openEditModal(member: any) {
  editingStaff.value = member;
  form.value = { ...member };
  showModal.value = true;
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  saving.value = true;

  try {
    if (editingStaff.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/staff/${editingStaff.value.id}`,
        form.value
      );
    } else {
      await apiClient.post(`/staff/admin/${shopSlug}/menu/staff`, form.value);
    }
    uiStore.showToast(
      "success",
      editingStaff.value ? t("common.success") : "Staff added successfully"
    );
    await fetchStaff();
    showModal.value = false;
  } catch (e) {
    uiStore.showToast("error", "Failed to save staff member");
  } finally {
    saving.value = false;
  }
}

async function deleteStaff(id: number) {
  if (!confirm("Are you sure you want to remove this staff member?")) return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";

  try {
    const response = await apiClient.delete(
      `/staff/admin/${shopSlug}/menu/staff/${id}`
    );
    if (response.data.success) {
      uiStore.showToast("success", "Staff member removed");
      await fetchStaff();
    } else {
      uiStore.showToast("error", response.data.error || "Failed to delete");
    }
  } catch (e) {
    uiStore.showToast("error", "Failed to delete staff member");
  }
}

function getRoleBadge(role: string) {
  switch (role) {
    case "owner":
      return "bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400";
    case "barista":
      return "bg-brown-100 text-brown-700 dark:bg-brown-900/30 dark:text-brown-400";
    case "cashier":
      return "bg-success-100 text-success-700 dark:bg-success-900/30 dark:text-success-400";
    default:
      return "bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300";
  }
}
</script>

<template>
  <div
    class="p-6 max-w-7xl mx-auto bg-bg-secondary dark:bg-gray-900 min-h-screen"
  >
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4"
    >
      <div>
        <h1
          class="text-3xl font-bold text-text-primary dark:text-white flex items-center gap-2"
        >
          👥 {{ t("nav.staff") || "Staff Management" }}
        </h1>
        <p class="text-text-secondary dark:text-gray-400 mt-1">
          Manage your team members and their shop access.
        </p>
      </div>
      <BaseButton variant="primary" size="lg" @click="openAddModal">
        + New Member
      </BaseButton>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-4 border-primary-600 border-t-transparent"
      ></div>
    </div>

    <!-- Empty State -->
    <BaseCard v-else-if="staff.length === 0" padding="lg" class="text-center">
      <div class="text-gray-400 dark:text-gray-600 mb-4 text-4xl">👥</div>
      <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300">
        No staff members found
      </h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        Add your first employee to get started.
      </p>
      <BaseButton variant="primary" @click="openAddModal">
        Add Staff Member
      </BaseButton>
    </BaseCard>

    <!-- Staff Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <BaseCard
        v-for="member in staff"
        :key="member.id"
        padding="md"
        hover
        shadow="sm"
      >
        <div class="flex items-start gap-4 mb-4">
          <!-- Avatar -->
          <div
            class="w-12 h-12 bg-primary-50 text-primary-600 dark:bg-primary-900/30 dark:text-primary-400 rounded-xl flex items-center justify-center text-xl font-bold shrink-0 border border-primary-100 dark:border-primary-800"
          >
            {{ member.name.charAt(0).toUpperCase() }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h3
              class="font-bold text-lg text-gray-800 dark:text-white truncate"
            >
              {{ member.name }}
            </h3>
            <span
              class="inline-block px-2 py-0.5 rounded text-[10px] font-bold uppercase tracking-wider mt-1"
              :class="getRoleBadge(member.role)"
            >
              {{ member.role }}
            </span>
          </div>
        </div>

        <div
          class="bg-gray-50 dark:bg-gray-800 rounded-lg p-3 mb-4 flex justify-between items-center"
        >
          <span
            class="text-xs text-gray-500 dark:text-gray-400 font-medium uppercase"
            >Access PIN</span
          >
          <span
            class="font-mono font-bold text-gray-700 dark:text-gray-300 tracking-widest text-lg"
            >******</span
          >
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <BaseButton
            variant="secondary"
            size="sm"
            fullWidth
            @click="openEditModal(member)"
          >
            {{ t("common.edit") }}
          </BaseButton>
          <BaseButton
            variant="danger"
            size="sm"
            @click="deleteStaff(member.id)"
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
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
              ></path>
            </svg>
          </BaseButton>
        </div>
      </BaseCard>
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4"
    >
      <div
        class="absolute inset-0 bg-black/30 backdrop-blur-sm"
        @click="showModal = false"
      ></div>
      <BaseCard
        padding="none"
        shadow="lg"
        rounded="2xl"
        class="w-full max-w-lg relative z-10"
      >
        <div class="p-6">
          <h2 class="text-xl font-bold mb-6 text-gray-800 dark:text-white">
            {{ editingStaff ? "Edit Staff Member" : "New Staff Member" }}
          </h2>

          <form @submit.prevent="handleSubmit">
            <!-- Name -->
            <div class="mb-5">
              <BaseInput
                v-model="form.name"
                label="Full Name"
                placeholder="e.g. John Doe"
                required
              />
            </div>

            <!-- Role -->
            <div class="mb-5">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >
                Role
                <span class="text-error-500">*</span>
              </label>
              <select
                v-model="form.role"
                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 focus:border-primary-500 outline-none transition-colors disabled:opacity-50"
                :disabled="
                  editingStaff && editingStaff.id === authStore.user?.id
                "
              >
                <option value="owner" v-if="editingStaff?.role === 'owner'">
                  Owner (Full Admin Access)
                </option>
                <option value="cashier">Cashier (POS & Orders)</option>
                <option value="barista">Barista (KDS Display Only)</option>
              </select>
              <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                <span
                  v-if="editingStaff && editingStaff.id === authStore.user?.id"
                  class="text-primary-600 dark:text-primary-400 font-bold block mb-1"
                >
                  You cannot change your own role.
                </span>
                Owners have full access. Cashiers can process orders. Baristas
                only see the KDS.
              </p>
            </div>

            <!-- PIN -->
            <div class="mb-8">
              <BaseInput
                v-model="form.pin"
                label="Login PIN (6 Digits)"
                type="text"
                maxlength="6"
                placeholder="123456"
                class="text-center text-xl tracking-[0.5em] font-mono"
                required
              />
            </div>

            <div class="flex justify-end gap-3 mt-8">
              <BaseButton
                type="button"
                variant="ghost"
                @click="showModal = false"
                :disabled="saving"
              >
                {{ t("common.cancel") }}
              </BaseButton>
              <BaseButton type="submit" variant="primary" :loading="saving">
                {{ saving ? "Saving..." : "Save Member" }}
              </BaseButton>
            </div>
          </form>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
