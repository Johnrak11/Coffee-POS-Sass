<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const categories = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingCategory = ref<any>(null);

const form = ref({
  name: "",
  icon: "Coffee",
  sort_order: 0,
});

onMounted(async () => {
  await fetchCategories();
});

async function fetchCategories() {
  try {
    const shopSlug = authStore.shop?.slug || "lucky-cafe";
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/categories`
    );
    categories.value = response.data;
  } catch (e) {
    console.error("Failed to fetch categories", e);
  } finally {
    loading.value = false;
  }
}

function openAddModal() {
  editingCategory.value = null;
  form.value = {
    name: "",
    icon: "Coffee",
    sort_order: categories.value.length,
  };
  showModal.value = true;
}

function openEditModal(category: any) {
  editingCategory.value = category;
  form.value = { ...category };
  showModal.value = true;
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    if (editingCategory.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/categories/${editingCategory.value.id}`,
        form.value
      );
    } else {
      await apiClient.post(
        `/staff/admin/${shopSlug}/menu/categories`,
        form.value
      );
    }
    await fetchCategories();
    showModal.value = false;
  } catch (e) {
    alert("Failed to save category");
  }
}

async function deleteCategory(id: number) {
  if (
    !confirm(
      "Are you sure? All products in this category will be uncategorized."
    )
  )
    return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    await apiClient.delete(
      `/staff/admin/${shopSlug}/menu/categories/${id}`
    );
    await fetchCategories();
  } catch (e) {
    alert("Failed to delete category");
  }
}
</script>

<template>
  <div class="p-8">
    <div class="mb-8 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Categories</h1>
        <p class="text-gray-500">Manage your menu groups and display order.</p>
      </div>
      <button @click="openAddModal"
        class="px-6 py-3 bg-orange-600 rounded-xl text-sm font-bold text-white shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all active:scale-95">
        + Add Category
      </button>
    </div>

    <!-- Category Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <template v-if="loading">
        <div v-for="i in 3" :key="i" class="h-32 bg-white rounded-3xl border border-gray-100 animate-pulse"></div>
      </template>

      <div v-else v-for="cat in categories" :key="cat.id"
        class="bg-white p-6 rounded-3xl border border-gray-100 shadow-sm hover:shadow-md transition-all group">
        <div class="flex items-center gap-4">
          <div class="w-12 h-12 bg-orange-50 text-orange-600 rounded-2xl flex items-center justify-center text-xl">
            <!-- Simplified icon placeholder -->
            ☕
          </div>
          <div class="flex-1">
            <h3 class="font-bold text-gray-900">{{ cat.name }}</h3>
            <p class="text-xs text-gray-400">Order: {{ cat.sort_order }}</p>
          </div>
          <div class="flex gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
            <button @click="openEditModal(cat)" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                </path>
              </svg>
            </button>
            <button @click="deleteCategory(cat.id)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg">
              <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path
                  d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                </path>
              </svg>
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Simple Modal Overlay -->
    <div v-if="showModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4">
      <div class="bg-white rounded-[32px] w-full max-w-md p-8 shadow-2xl">
        <h2 class="text-2xl font-bold mb-6">
          {{ editingCategory ? "Edit Category" : "New Category" }}
        </h2>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Category Name</label>
            <input v-model="form.name" type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="e.g. Hot Coffee" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Sort Order</label>
            <input v-model="form.sort_order" type="number"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none" />
          </div>
        </div>

        <div class="flex gap-4 mt-8">
          <button @click="showModal = false"
            class="flex-1 py-3 font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors">
            Cancel
          </button>
          <button @click="handleSubmit"
            class="flex-1 py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-colors">
            Save Category
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
