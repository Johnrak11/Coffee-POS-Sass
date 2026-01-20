<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import apiClient from "@/api";
import { useAuthStore } from "@/stores/auth";
import { useUIStore } from "@/stores/ui";
import { BaseButton, BaseCard, BaseInput } from "@/components/common";
import ImageUpload from "@/components/ImageUpload.vue";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();

const categories = ref<any>({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0,
});
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingCategory = ref<any>(null);
const pendingImageFile = ref<File | null>(null);

// Filters
const page = ref(1);
const searchQuery = ref("");
let searchTimeout: any = null;

const form = ref({
  name: "",
  icon: "",
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
      { params },
    );
    categories.value = response.data;
  } catch (e) {
    console.error("Failed to fetch categories", e);
    uiStore.showToast("error", "Failed to load categories");
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
    import.meta.env.VITE_CLOUDINARY_UPLOAD_PRESET || "coffee-pos-unsigned",
  );
  formData.append("folder", "coffee-pos/categories");

  const response = await fetch(
    `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
    { method: "POST", body: formData },
  );

  if (!response.ok) throw new Error("Image upload failed");
  const data = await response.json();
  return data.secure_url;
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  saving.value = true;
  try {
    if (pendingImageFile.value) {
      const imageUrl = await uploadToCloudinary(pendingImageFile.value);
      form.value.icon = imageUrl;
    }

    if (editingCategory.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/categories/${editingCategory.value.id}`,
        form.value,
      );
    } else {
      await apiClient.post(
        `/staff/admin/${shopSlug}/menu/categories`,
        form.value,
      );
    }
    uiStore.showToast(
      "success",
      editingCategory.value ? t("common.success") : "Category added",
    );
    await fetchCategories();
    showModal.value = false;
  } catch (e) {
    uiStore.showToast("error", "Failed to save category");
  } finally {
    saving.value = false;
  }
}

async function deleteCategory(id: number) {
  if (
    !confirm(
      "Are you sure? All products in this category will be uncategorized.",
    )
  )
    return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    await apiClient.delete(`/staff/admin/${shopSlug}/menu/categories/${id}`);
    uiStore.showToast("success", "Category deleted");
    await fetchCategories();
  } catch (e) {
    uiStore.showToast("error", "Failed to delete category");
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
          {{ t("nav.categories") || "Categories" }}
        </h1>
        <p class="text-text-secondary dark:text-gray-400 mt-1">
          Manage your menu groups and display order.
        </p>
      </div>
      <div class="flex flex-wrap gap-3">
        <router-link
          :to="{ name: 'admin-option-sets' }"
          class="px-4 py-2 bg-white dark:bg-gray-800 text-primary-600 dark:text-primary-400 border border-primary-200 dark:border-primary-800 rounded-lg text-sm font-bold hover:bg-primary-50 dark:hover:bg-primary-900/30 transition-all flex items-center gap-2"
        >
          ⚙️ Option Sets
        </router-link>
        <BaseButton variant="primary" @click="openAddModal">
          + New Category
        </BaseButton>
      </div>
    </div>

    <!-- Search -->
    <div class="mb-6 relative max-w-md">
      <span
        class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 dark:text-gray-500"
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
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </span>
      <input
        v-model="searchQuery"
        type="text"
        placeholder="Search categories..."
        class="w-full pl-10 pr-4 py-2 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all"
      />
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-4 border-primary-600 border-t-transparent"
      ></div>
    </div>

    <!-- Empty State -->
    <BaseCard
      v-else-if="categories.data.length === 0"
      padding="lg"
      class="text-center"
    >
      <div class="text-gray-400 dark:text-gray-600 mb-4 text-4xl">☕</div>
      <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300">
        No categories found
      </h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        Create your first category to start organizing your menu.
      </p>
      <BaseButton variant="primary" @click="openAddModal">
        Create Category
      </BaseButton>
    </BaseCard>

    <!-- Category Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <BaseCard
        v-for="cat in categories.data"
        :key="cat.id"
        padding="md"
        hover
        shadow="sm"
      >
        <div class="flex items-start gap-4">
          <!-- Icon -->
          <div
            class="w-12 h-12 bg-primary-50 dark:bg-primary-900/30 text-primary-600 dark:text-primary-400 rounded-xl flex items-center justify-center text-xl overflow-hidden shrink-0 border border-primary-100 dark:border-primary-800"
          >
            <img
              v-if="cat.icon && cat.icon !== 'Coffee'"
              :src="cat.icon"
              class="w-full h-full object-cover"
              @error="
                (e) => ((e.target as HTMLImageElement).style.display = 'none')
              "
            />
            <span v-if="!cat.icon || cat.icon === 'Coffee'">☕</span>
          </div>

          <!-- Content -->
          <div class="flex-1 min-w-0">
            <div class="flex justify-between items-start">
              <h3
                class="font-bold text-lg text-gray-800 dark:text-white truncate pr-2"
              >
                {{ cat.name }}
              </h3>
              <!-- Action Buttons -->
              <div class="flex gap-1 shrink-0">
                <BaseButton
                  variant="ghost"
                  size="sm"
                  @click="openEditModal(cat)"
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
                </BaseButton>
                <BaseButton
                  variant="danger"
                  size="sm"
                  @click="deleteCategory(cat.id)"
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
            </div>

            <p
              class="text-xs font-medium text-gray-400 dark:text-gray-500 mt-1 uppercase tracking-wide"
            >
              Sort Order: {{ cat.sort_order }}
            </p>
          </div>
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
            {{ editingCategory ? "Edit Category" : "New Category" }}
          </h2>

          <form @submit.prevent="handleSubmit">
            <!-- Name -->
            <div class="mb-6">
              <BaseInput
                v-model="form.name"
                label="Category Name"
                placeholder="e.g. Hot Coffee"
                required
              />
            </div>

            <!-- Image -->
            <div class="mb-6">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Category Image
              </label>
              <ImageUpload
                v-model="form.icon"
                folder="categories"
                @fileSelected="pendingImageFile = $event"
              />
            </div>

            <!-- Sort Order -->
            <div class="mb-6">
              <BaseInput
                v-model.number="form.sort_order"
                label="Sort Order"
                type="number"
              />
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                Lower numbers appear first in the menu.
              </p>
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
                {{ saving ? "Saving..." : "Save Category" }}
              </BaseButton>
            </div>
          </form>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
