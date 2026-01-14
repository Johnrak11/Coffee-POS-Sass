<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
          📁 Categories
        </h1>
        <p class="text-gray-500 mt-1">
          Manage your menu groups and display order.
        </p>
      </div>
      <div class="flex flex-wrap gap-3">
        <router-link
          :to="{ name: 'admin-option-sets' }"
          class="px-4 py-2 bg-white text-orange-600 border border-orange-200 rounded-lg text-sm font-bold hover:bg-orange-50 transition-all flex items-center gap-2"
        >
          ⚙️ Option Sets
        </router-link>
        <button
          @click="openAddModal"
          class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium shadow-lg shadow-orange-200 transition-colors flex items-center gap-2"
        >
          <span>+ New Category</span>
        </button>
      </div>
    </div>

    <!-- Search (Optional) -->
    <div class="mb-6 relative max-w-md">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
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
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </span>
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search categories..."
        class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none transition-all"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"
      ></div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="categories.data.length === 0"
      class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200"
    >
      <div class="text-gray-400 mb-4 text-4xl">☕</div>
      <h3 class="text-lg font-medium text-gray-600">No categories found</h3>
      <p class="text-gray-500 mb-6">
        Create your first category to start organizing your menu.
      </p>
      <button
        @click="openAddModal"
        class="text-orange-600 font-bold hover:underline"
      >
        Create Category
      </button>
    </div>

    <!-- Category Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="cat in categories.data"
        :key="cat.id"
        class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow p-5"
      >
        <div class="flex items-start gap-4">
          <!-- Icon -->
          <div
            class="w-12 h-12 bg-orange-50 text-orange-600 rounded-xl flex items-center justify-center text-xl overflow-hidden shrink-0 border border-orange-100"
          >
            <img
              v-if="cat.icon && cat.icon !== 'Coffee'"
              :src="cat.icon"
              class="w-full h-full object-cover"
              @error="(e) => (e.target as HTMLImageElement).style.display = 'none'"
            />
            <span v-if="!cat.icon || cat.icon === 'Coffee'">☕</span>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start">
              <h3 class="font-bold text-lg text-gray-800 truncate pr-2">
                {{ cat.name }}
              </h3>
              <!-- Action Buttons -->
              <div class="flex gap-1 shrink-0">
                <button
                  @click="openEditModal(cat)"
                  class="p-1 text-gray-400 hover:text-blue-600 transition-colors"
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
                      d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                    ></path>
                  </svg>
                </button>
                <button
                  @click="deleteCategory(cat.id)"
                  class="p-1 text-gray-400 hover:text-red-600 transition-colors"
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

            <p
              class="text-xs font-medium text-gray-400 mt-1 uppercase tracking-wide"
            >
              Sort Order: {{ cat.sort_order }}
            </p>
          </div>
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
            {{ editingCategory ? "Edit Category" : "New Category" }}
          </h2>

          <form @submit.prevent="handleSubmit">
            <!-- Name -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Category Name</label
              >
              <input
                v-model="form.name"
                type="text"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="e.g. Hot Coffee"
                required
              />
            </div>

            <!-- Image -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Category Image</label
              >
              <ImageUpload
                v-model="form.icon"
                folder="categories"
                @fileSelected="pendingImageFile = $event"
              />
            </div>

            <!-- Sort Order -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Sort Order</label
              >
              <input
                v-model.number="form.sort_order"
                type="number"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
              />
              <p class="text-xs text-gray-400 mt-1">
                Lower numbers appear first in the menu.
              </p>
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
                {{ saving ? "Saving..." : "Save Category" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import ImageUpload from "@/components/ImageUpload.vue";

const authStore = useAuthStore();
const categories = ref<any>({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0,
});
const loading = ref(true);
const saving = ref(false); // Added for loading state
const showModal = ref(false);
const editingCategory = ref<any>(null);
const pendingImageFile = ref<File | null>(null);

// Filters
const page = ref(1);
const searchQuery = ref("");
let searchTimeout: any = null;

const form = ref({
  name: "",
  icon: "", // Used for image_url
  sort_order: 0,
});

onMounted(async () => {
  await fetchCategories();
});

async function fetchCategories(p: number = 1) {
  loading.value = true;
  page.value = p;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";

  try {
    const params: any = { page: p, limit: 12 };
    if (searchQuery.value) params.search = searchQuery.value;

    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/categories`,
      { params }
    );
    categories.value = response.data;
  } catch (e) {
    console.error("Failed to fetch categories", e);
  } finally {
    loading.value = false;
  }
}

// Watch filters
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchCategories(1), 500);
});

function openAddModal() {
  editingCategory.value = null;
  form.value = {
    name: "",
    icon: "",
    sort_order: categories.value.total || 0,
  };
  pendingImageFile.value = null;
  showModal.value = true;
}

function openEditModal(category: any) {
  editingCategory.value = category;
  form.value = { ...category };

  // Clear default placeholder so ImageUpload shows empty state
  if (form.value.icon === "Coffee") {
    form.value.icon = "";
  }

  pendingImageFile.value = null;
  showModal.value = true;
}

async function uploadToCloudinary(file: File) {
  const cloudName = import.meta.env.VITE_CLOUDINARY_CLOUD_NAME;
  if (!cloudName) throw new Error("Cloudinary not configured");

  const formData = new FormData();
  formData.append("file", file);
  formData.append(
    "upload_preset",
    import.meta.env.VITE_CLOUDINARY_UPLOAD_PRESET || "coffee-pos-unsigned"
  );
  formData.append("folder", "coffee-pos/categories");

  const response = await fetch(
    `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
    { method: "POST", body: formData }
  );

  if (!response.ok) throw new Error("Image upload failed");
  const data = await response.json();
  return data.secure_url;
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  saving.value = true; // Start loading
  try {
    // Upload image if selected
    if (pendingImageFile.value) {
      const imageUrl = await uploadToCloudinary(pendingImageFile.value);
      form.value.icon = imageUrl;
    }

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
  } finally {
    saving.value = false; // End loading
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
    await apiClient.delete(`/staff/admin/${shopSlug}/menu/categories/${id}`);
    await fetchCategories();
  } catch (e) {
    alert("Failed to delete category");
  }
}
</script>
