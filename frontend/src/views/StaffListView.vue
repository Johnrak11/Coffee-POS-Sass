<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import { toast } from "vue-sonner";

const authStore = useAuthStore();
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
    toast.success(
      editingStaff.value ? "Staff updated" : "Staff added successfully"
    );
    await fetchStaff();
    showModal.value = false;
  } catch (e) {
    toast.error("Failed to save staff member");
  } finally {
    saving.value = false;
  }
}

async function deleteStaff(id: number) {
  if (!confirm("Are you sure you want to remove this staff member?")) return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  const loadingToast = toast.loading("Deleting staff...");

  try {
    const response = await apiClient.delete(
      `/staff/admin/${shopSlug}/menu/staff/${id}`
    );
    toast.dismiss(loadingToast);
    if (response.data.success) {
      toast.success("Staff member removed");
      await fetchStaff();
    } else {
      toast.error(response.data.error || "Failed to delete");
    }
  } catch (e) {
    toast.dismiss(loadingToast);
    toast.error("Failed to delete staff member");
  }
}

function getRoleBadge(role: string) {
  switch (role) {
    case "owner":
      return "bg-purple-100 text-purple-700";
    case "barista":
      return "bg-brown-100 text-brown-700";
    case "cashier":
      return "bg-green-100 text-green-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}
</script>

<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
          👥 Staff Management
        </h1>
        <p class="text-gray-500 mt-1">
          Manage your team members and their shop access.
        </p>
      </div>
      <div>
        <button
          @click="openAddModal"
          class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium shadow-lg shadow-orange-200 transition-colors flex items-center gap-2"
        >
          <span>+ New Member</span>
        </button>
      </div>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"
      ></div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="staff.length === 0"
      class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200"
    >
      <div class="text-gray-400 mb-4 text-4xl">👥</div>
      <h3 class="text-lg font-medium text-gray-600">No staff members found</h3>
      <p class="text-gray-500 mb-6">Add your first employee to get started.</p>
      <button
        @click="openAddModal"
        class="text-orange-600 font-bold hover:underline"
      >
        Add Staff Member
      </button>
    </div>

    <!-- Staff Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="member in staff"
        :key="member.id"
        class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow p-5"
      >
        <div class="flex items-start gap-4 mb-4">
          <!-- Avatar -->
          <div
            class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center text-xl font-bold shrink-0 border border-orange-100"
          >
            {{ member.name.charAt(0).toUpperCase() }}
          </div>

          <!-- Info -->
          <div class="flex-1 min-w-0">
            <h3 class="font-bold text-lg text-gray-800 truncate">
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
          class="bg-gray-50 rounded-lg p-3 mb-4 flex justify-between items-center"
        >
          <span class="text-xs text-gray-500 font-medium uppercase"
            >Access PIN</span
          >
          <span
            class="font-mono font-bold text-gray-700 tracking-widest text-lg"
            >******</span
          >
        </div>

        <!-- Actions -->
        <div class="flex gap-2">
          <button
            @click="openEditModal(member)"
            class="flex-1 py-1.5 bg-white border border-gray-200 text-gray-600 rounded-lg text-sm font-medium hover:bg-gray-50 hover:text-blue-600 transition-colors"
          >
            Edit
          </button>
          <button
            @click="deleteStaff(member.id)"
            class="px-3 py-1.5 bg-white border border-gray-200 text-gray-400 rounded-lg hover:border-red-200 hover:bg-red-50 hover:text-red-600 transition-colors"
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
          </button>
        </div>
      </div>
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
      <div
        class="bg-white rounded-2xl shadow-xl w-full max-w-lg relative z-10 overflow-hidden"
      >
        <div class="p-6">
          <h2 class="text-xl font-bold mb-6 text-gray-800">
            {{ editingStaff ? "Edit Staff Member" : "New Staff Member" }}
          </h2>

          <form @submit.prevent="handleSubmit">
            <!-- Name -->
            <div class="mb-5">
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Full Name</label
              >
              <input
                v-model="form.name"
                type="text"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="e.g. John Doe"
                required
              />
            </div>

            <!-- Role -->
            <div class="mb-5">
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Role</label
              >
              <select
                v-model="form.role"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all bg-white disabled:bg-gray-100 disabled:text-gray-500"
                :disabled="
                  editingStaff && editingStaff.id === authStore.user?.id
                "
              >
                <!-- Only show Owner option if editing an existing owner -->
                <option value="owner" v-if="editingStaff?.role === 'owner'">
                  Owner (Full Admin Access)
                </option>
                <option value="cashier">Cashier (POS & Orders)</option>
                <option value="barista">Barista (KDS Display Only)</option>
              </select>
              <p class="text-xs text-gray-500 mt-1">
                <span
                  v-if="editingStaff && editingStaff.id === authStore.user?.id"
                  class="text-orange-600 font-bold block mb-1"
                >
                  You cannot change your own role.
                </span>
                Owners have full access. Cashiers can process orders. Baristas
                only see the KDS.
              </p>
            </div>

            <!-- PIN -->
            <div class="mb-8">
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Login PIN (6 Digits)</label
              >
              <input
                v-model="form.pin"
                type="text"
                maxlength="6"
                class="w-full px-4 py-3 rounded-xl border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all text-center text-xl tracking-[0.5em] font-mono"
                placeholder="123456"
                required
              />
            </div>

            <div class="flex justify-end gap-3 mt-8">
              <button
                type="button"
                @click="showModal = false"
                class="px-4 py-2 text-gray-600 font-medium hover:bg-gray-100 rounded-lg transition-colors"
                :disabled="saving"
              >
                Cancel
              </button>
              <button
                type="submit"
                class="px-6 py-2 bg-orange-600 text-white font-bold rounded-lg shadow-lg shadow-orange-200 hover:bg-orange-500 transition-colors disabled:opacity-50"
                :disabled="saving"
              >
                {{ saving ? "Saving..." : "Save Member" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>
