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
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/staff`
    );
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
  const loadingToast = toast.loading(
    editingStaff.value ? "Updating staff..." : "Saving staff..."
  );

  try {
    if (editingStaff.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/staff/${editingStaff.value.id}`,
        form.value
      );
    } else {
      await apiClient.post(
        `/staff/admin/${shopSlug}/menu/staff`,
        form.value
      );
    }
    toast.dismiss(loadingToast);
    toast.success(
      editingStaff.value ? "Staff updated" : "Staff added successfully"
    );
    await fetchStaff();
    showModal.value = false;
  } catch (e) {
    toast.dismiss(loadingToast);
    toast.error("Failed to save staff member");
  }
}

async function deleteStaff(id: number) {
  if (!confirm("Delete this staff member?")) return;
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
</script>

<template>
  <div class="p-8">
    <div class="mb-8 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Staff Management</h1>
        <p class="text-gray-500">
          Manage your team members and their shop access.
        </p>
      </div>
      <button @click="openAddModal"
        class="px-6 py-3 bg-orange-600 rounded-xl text-sm font-bold text-white shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all active:scale-95">
        + Add Staff
      </button>
    </div>

    <!-- Staff Cards -->
    <div v-auto-animate class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <template v-if="loading">
        <div v-for="i in 3" :key="i" class="h-40 bg-white rounded-3xl border border-gray-100 animate-pulse"></div>
      </template>

      <div v-else v-for="member in staff" :key="member.id"
        class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group relative overflow-hidden">
        <div class="flex items-center gap-4 mb-4">
          <div
            class="w-14 h-14 bg-orange-100 text-orange-600 rounded-2xl flex items-center justify-center text-xl font-bold">
            {{ member.name[0] }}
          </div>
          <div>
            <h3 class="font-bold text-gray-900">{{ member.name }}</h3>
            <span class="px-2 py-0.5 bg-gray-100 rounded text-[10px] font-bold text-gray-400 uppercase tracking-widest">
              {{ member.role }}
            </span>
          </div>
        </div>

        <div class="space-y-2 mb-4">
          <div class="flex justify-between text-xs">
            <span class="text-gray-400">PIN Code:</span>
            <span class="font-mono font-bold text-gray-600">******</span>
          </div>
        </div>

        <div class="flex gap-2">
          <button @click="openEditModal(member)"
            class="flex-1 py-2 bg-gray-50 text-gray-600 rounded-xl text-xs font-bold hover:bg-blue-50 hover:text-blue-600 transition-colors">
            Edit
          </button>
          <button @click="deleteStaff(member.id)"
            class="px-3 py-2 bg-gray-50 text-red-400 rounded-xl hover:bg-red-50 hover:text-red-600 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path
                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
              </path>
            </svg>
          </button>
        </div>
      </div>
    </div>

    <!-- Modal for Add/Edit -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-[32px] w-full max-w-md p-8 shadow-2xl">
        <h2 class="text-2xl font-bold mb-6">
          {{ editingStaff ? "Edit Staff" : "New Staff Member" }}
        </h2>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Member Name</label>
            <input v-model="form.name" type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Role</label>
            <select v-model="form.role"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white font-bold">
              <option value="owner">Owner (Full Admin)</option>
              <option value="cashier">Cashier (POS + Orders)</option>
              <option value="barista">Barista (KDS Only)</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Login PIN (6 Digits)</label>
            <input v-model="form.pin" type="text" maxlength="6"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none font-mono tracking-[0.5em] text-center text-lg"
              placeholder="123456" />
          </div>
        </div>

        <div class="flex gap-4 mt-8">
          <button @click="showModal = false"
            class="flex-1 py-3 font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors">
            Cancel
          </button>
          <button @click="handleSubmit"
            class="flex-1 py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-colors">
            Save Member
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
