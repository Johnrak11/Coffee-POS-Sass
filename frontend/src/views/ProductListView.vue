<script setup lang="ts">
import { onMounted, ref, watch } from "vue";
import { useI18n } from "vue-i18n";
import apiClient from "../api";
import { useAuthStore } from "../stores/auth";
import { useUIStore } from "../stores/ui";
import { BaseButton, BaseCard, BaseInput } from "../components/common";
import ImageUpload from "../components/ImageUpload.vue";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();

const products = ref<any>({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0,
});
const categories = ref<any[]>([]);
const optionSets = ref<any[]>([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingProduct = ref<any>(null);

// Filters & Pagination
const page = ref(1);
const searchQuery = ref("");
const selectedCategory = ref("all");
const selectedStatus = ref("all");
let searchTimeout: any = null;

const form = ref({
  category_id: "",
  name: "",
  price: "",
  image_url: "",
  is_available: true,
  variants: [] as any[],
});
const pendingImageFile = ref<File | null>(null);

async function fetchProducts(p: number = 1) {
  loading.value = true;
  page.value = p;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";

  try {
    const params: any = { page: p, limit: 12 };
    if (searchQuery.value) params.search = searchQuery.value;
    if (selectedCategory.value !== "all")
      params.category_id = selectedCategory.value;
    if (selectedStatus.value !== "all") params.status = selectedStatus.value;

    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/products`,
      { params },
    );
    products.value = response.data;
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to load products");
  } finally {
    loading.value = false;
  }
}

watch([selectedCategory, selectedStatus], () => fetchProducts(1));
watch(searchQuery, () => {
  clearTimeout(searchTimeout);
  searchTimeout = setTimeout(() => fetchProducts(1), 500);
});

async function fetchCategories() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/categories`,
      { params: { limit: 100 } },
    );
    categories.value = response.data.data || [];
  } catch (e) {
    console.error("Failed to fetch categories", e);
  }
}

async function fetchOptionSets() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/option-sets`,
    );
    optionSets.value = response.data;
  } catch (e) {
    console.error(e);
  }
}

onMounted(async () => {
  await Promise.all([fetchProducts(), fetchCategories(), fetchOptionSets()]);
});

async function uploadToCloudinary(file: File) {
  const cloudName = import.meta.env.VITE_CLOUDINARY_CLOUD_NAME;

  if (!cloudName) throw new Error("Cloudinary not configured");

  const formData = new FormData();
  formData.append("file", file);

  formData.append(
    "upload_preset",
    import.meta.env.VITE_CLOUDINARY_UPLOAD_PRESET || "coffee-pos-unsigned",
  );
  formData.append("folder", "coffee-pos/products");

  const response = await fetch(
    `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
    { method: "POST", body: formData },
  );

  if (!response.ok) throw new Error("Image upload failed");
  const data = await response.json();
  return data.secure_url;
}

function openAddModal() {
  editingProduct.value = null;
  form.value = {
    category_id: "",
    name: "",
    price: "",
    image_url: "",
    is_available: true,
    variants: [],
  };
  pendingImageFile.value = null;
  showModal.value = true;
}

function openEditModal(product: any) {
  editingProduct.value = product;
  form.value = {
    category_id: product.category_id || "",
    name: product.name,
    price: product.price,
    image_url: product.image_url || "",
    is_available: product.is_available ?? true,
    variants: product.variants
      ? JSON.parse(JSON.stringify(product.variants))
      : [],
  };
  pendingImageFile.value = null;
  showModal.value = true;
}

function handlePresetChange(event: Event) {
  const target = event.target as HTMLSelectElement;
  if (!target.value) return;

  const val = parseInt(target.value);
  if (!isNaN(val)) {
    applyPreset(val);
  }
  // Reset select
  target.value = "";
}

function applyPreset(setId: number) {
  const set = optionSets.value.find((s: any) => s.id === setId);
  if (set) {
    set.elements.forEach((element: any) => {
      form.value.variants.push({
        name: set.name,
        option_name: element.label,
        extra_price: parseFloat(element.price_modifier),
      });
    });
  }
}

function addVariant() {
  form.value.variants.push({ name: "", option_name: "", extra_price: 0 });
}

function removeVariant(index: number) {
  form.value.variants.splice(index, 1);
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  saving.value = true;

  try {
    if (pendingImageFile.value) {
      const imageUrl = await uploadToCloudinary(pendingImageFile.value);
      form.value.image_url = imageUrl;
    }

    if (editingProduct.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/products/${editingProduct.value.id}`,
        form.value,
      );
    } else {
      await apiClient.post(
        `/staff/admin/${shopSlug}/menu/products`,
        form.value,
      );
    }

    uiStore.showToast(
      "success",
      editingProduct.value ? "Product updated" : "Product added",
    );
    await fetchProducts();
    showModal.value = false;
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to save product");
  } finally {
    saving.value = false;
  }
}

async function deleteProduct(id: number) {
  if (!confirm("Delete this product?")) return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    await apiClient.delete(`/staff/admin/${shopSlug}/menu/products/${id}`);
    uiStore.showToast("success", "Product deleted");
    await fetchProducts();
  } catch (e) {
    uiStore.showToast("error", "Failed to delete");
  }
}

function formatCurrency(val: number) {
  // Always display product prices in USD
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(val);
}
</script>

<template>
  <div
    class="h-full flex flex-col p-6 bg-bg-secondary dark:bg-gray-900 overflow-hidden"
  >
    <div class="mb-6 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-text-primary dark:text-white">
          {{ t("nav.products") || "Products" }}
        </h1>
        <p class="text-text-secondary dark:text-gray-400">
          Manage your items, pricing, and availability.
        </p>
      </div>
      <BaseButton variant="primary" size="lg" @click="openAddModal">
        + Add Product
      </BaseButton>
    </div>

    <!-- Filters -->
    <div class="mb-6 flex flex-wrap gap-4">
      <div class="flex-1 min-w-[300px]">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search products..."
          class="w-full px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none"
        />
      </div>
      <select
        v-model="selectedCategory"
        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none"
      >
        <option value="all">All Categories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
          {{ cat.name }}
        </option>
      </select>
      <select
        v-model="selectedStatus"
        class="px-4 py-2 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none"
      >
        <option value="all">All Status</option>
        <option value="available">Available</option>
        <option value="unavailable">Unavailable</option>
      </select>
    </div>

    <!-- Products Table -->
    <div
      class="flex-1 bg-app-surface rounded-2xl border border-app-border overflow-hidden flex flex-col transition-colors duration-300"
    >
      <div class="overflow-x-auto flex-1">
        <table class="w-full">
          <thead
            class="bg-app-bg text-app-muted text-xs uppercase font-bold sticky top-0 border-b border-app-border"
          >
            <tr>
              <th class="text-left px-4 py-3">Product</th>
              <th class="text-left px-4 py-3">Category</th>
              <th class="text-left px-4 py-3">Price</th>
              <th class="text-left px-4 py-3">Status</th>
              <th class="text-right px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-app-border">
            <tr v-if="loading">
              <td colspan="5" class="p-8 text-center">
                <div class="flex justify-center">
                  <div
                    class="animate-spin rounded-full h-8 w-8 border-4 border-primary-600 border-t-transparent"
                  ></div>
                </div>
              </td>
            </tr>
            <tr v-else-if="products.data.length === 0">
              <td colspan="5" class="p-12 text-center">
                <div class="text-app-muted mb-2">No products found</div>
                <BaseButton variant="primary" size="sm" @click="openAddModal"
                  >Add your first product</BaseButton
                >
              </td>
            </tr>
            <tr
              v-for="product in products.data"
              :key="product.id"
              class="hover:bg-app-bg transition-colors"
            >
              <td class="p-4">
                <div class="flex items-center gap-3">
                  <img
                    v-if="product.image_url"
                    :src="product.image_url"
                    class="w-12 h-12 rounded-xl object-cover border border-gray-100 dark:border-gray-700"
                  />
                  <div
                    v-else
                    class="w-12 h-12 rounded-xl bg-gray-100 dark:bg-gray-700 flex items-center justify-center text-2xl"
                  >
                    ☕
                  </div>
                  <div>
                    <div class="font-bold text-gray-900 dark:text-white">
                      {{ product.name }}
                    </div>
                    <div
                      v-if="product.variants && product.variants.length > 0"
                      class="text-xs text-primary-600 dark:text-primary-400"
                    >
                      {{ product.variants.length }}
                      {{ product.variants.length === 1 ? "option" : "options" }}
                    </div>
                  </div>
                </div>
              </td>
              <td class="p-4">
                <span
                  class="px-3 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded-lg text-xs font-medium uppercase"
                >
                  {{ product.category?.name || "No Category" }}
                </span>
              </td>
              <td class="p-4">
                <span class="font-bold text-gray-900 dark:text-white">
                  {{ formatCurrency(parseFloat(product.price)) }}
                </span>
              </td>
              <td class="p-4">
                <span
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 w-fit',
                    product.is_available
                      ? 'bg-success-100 dark:bg-success-900/30 text-success-600 dark:text-success-400'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                  ]"
                >
                  <div
                    :class="[
                      'w-1.5 h-1.5 rounded-full',
                      product.is_available ? 'bg-success-500' : 'bg-gray-400',
                    ]"
                  ></div>
                  {{ product.is_available ? "Available" : "Unavailable" }}
                </span>
              </td>
              <td class="p-4">
                <div class="flex justify-end gap-2">
                  <BaseButton
                    variant="ghost"
                    size="sm"
                    @click="openEditModal(product)"
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
                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                      />
                    </svg>
                  </BaseButton>
                  <BaseButton
                    variant="danger"
                    size="sm"
                    @click="deleteProduct(product.id)"
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
                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                      />
                    </svg>
                  </BaseButton>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div
        v-if="products.last_page > 1"
        class="border-t border-gray-100 dark:border-gray-700 p-4 flex justify-between items-center"
      >
        <div class="text-sm text-gray-500 dark:text-gray-400">
          Page {{ products.current_page }} of {{ products.last_page }}
        </div>
        <div class="flex gap-2">
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="products.current_page === 1"
            @click="fetchProducts(products.current_page - 1)"
          >
            Previous
          </BaseButton>
          <BaseButton
            variant="secondary"
            size="sm"
            :disabled="products.current_page === products.last_page"
            @click="fetchProducts(products.current_page + 1)"
          >
            Next
          </BaseButton>
        </div>
      </div>
    </div>

    <!-- Add/Edit Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm"
    >
      <BaseCard
        padding="lg"
        shadow="lg"
        rounded="2xl"
        class="w-full max-w-2xl max-h-[90vh] overflow-y-auto"
      >
        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
          {{ editingProduct ? "Edit Product" : "New Product" }}
        </h2>

        <form @submit.prevent="handleSubmit" class="space-y-6">
          <!-- Product Image -->
          <div>
            <label
              class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >Product Image</label
            >
            <ImageUpload
              v-model="form.image_url"
              folder="products"
              @fileSelected="pendingImageFile = $event"
            />
          </div>

          <!-- Product Name -->
          <BaseInput
            v-model="form.name"
            label="Product Name"
            placeholder="e.g. Espresso"
            required
          />

          <!-- Category & Price -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >Category</label
              >
              <select
                v-model="form.category_id"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none"
              >
                <option value="">No Category</option>
                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                  {{ cat.name }}
                </option>
              </select>
            </div>
            <BaseInput
              v-model="form.price"
              label="Base Price"
              type="number"
              step="0.01"
              placeholder="0.00"
              required
            />
          </div>

          <!-- Availability -->
          <div class="flex items-center gap-3">
            <input
              v-model="form.is_available"
              type="checkbox"
              id="available"
              class="w-5 h-5 rounded border-gray-300 dark:border-gray-600 text-primary-600 focus:ring-primary-500"
            />
            <label
              for="available"
              class="text-sm font-medium text-gray-700 dark:text-gray-300"
              >Available for order</label
            >
          </div>

          <!-- Variants Section -->
          <div>
            <div class="flex justify-between items-center mb-4">
              <label
                class="text-sm font-medium text-gray-700 dark:text-gray-300"
                >Options & Variants</label
              >
              <div class="flex gap-2">
                <select
                  @change="handlePresetChange"
                  class="px-3 py-1.5 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white text-xs"
                >
                  <option value="">Apply Preset...</option>
                  <option
                    v-for="set in optionSets"
                    :key="set.id"
                    :value="set.id"
                  >
                    {{ set.name }}
                  </option>
                </select>
                <BaseButton
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="addVariant"
                >
                  + Add
                </BaseButton>
              </div>
            </div>

            <div
              v-if="form.variants.length === 0"
              class="text-center py-8 bg-gray-50 dark:bg-gray-800 rounded-xl border-2 border-dashed border-gray-200 dark:border-gray-700"
            >
              <p class="text-gray-500 dark:text-gray-400 text-sm mb-3">
                No options yet
              </p>
              <BaseButton
                type="button"
                variant="primary"
                size="sm"
                @click="addVariant"
                >Add First Option</BaseButton
              >
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(variant, index) in form.variants"
                :key="index"
                class="flex gap-3 items-start p-3 bg-gray-50 dark:bg-gray-800 rounded-xl"
              >
                <input
                  v-model="variant.name"
                  type="text"
                  placeholder="Group (e.g. Size)"
                  class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-1 focus:ring-primary-500 outline-none"
                />
                <input
                  v-model="variant.option_name"
                  type="text"
                  placeholder="Option (e.g. Large)"
                  class="flex-1 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-1 focus:ring-primary-500 outline-none"
                />
                <input
                  v-model.number="variant.extra_price"
                  type="number"
                  step="0.01"
                  placeholder="+0.50"
                  class="w-24 px-3 py-2 rounded-lg border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-900 text-gray-900 dark:text-white text-sm focus:ring-1 focus:ring-primary-500 outline-none"
                />
                <BaseButton
                  type="button"
                  variant="ghost"
                  size="sm"
                  @click="removeVariant(index)"
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
                      d="M6 18L18 6M6 6l12 12"
                    />
                  </svg>
                </BaseButton>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <BaseButton
              type="button"
              variant="ghost"
              @click="showModal = false"
              :disabled="saving"
            >
              {{ t("common.cancel") }}
            </BaseButton>
            <BaseButton type="submit" variant="primary" :loading="saving">
              {{
                saving
                  ? "Saving..."
                  : editingProduct
                    ? "Update Product"
                    : "Create Product"
              }}
            </BaseButton>
          </div>
        </form>
      </BaseCard>
    </div>
  </div>
</template>
