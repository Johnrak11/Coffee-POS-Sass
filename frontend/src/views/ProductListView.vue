<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import ImageUpload from "@/components/ImageUpload.vue";

const authStore = useAuthStore();
const products = ref<any>({
  data: [],
  current_page: 1,
  last_page: 1,
  total: 0,
});
const categories = ref<any[]>([]);
const loading = ref(true);
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

import { watch } from "vue";

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
      { params }
    );
    products.value = response.data;
  } catch (e) {
    console.error(e);
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
      { params: { limit: 100 } }
    );
    categories.value = response.data.data || [];
  } catch (e) {
    console.error("Failed to fetch categories", e);
  }
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
  formData.append("folder", "coffee-pos/products");

  const response = await fetch(
    `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
    { method: "POST", body: formData }
  );

  if (!response.ok) throw new Error("Image upload failed");
  const data = await response.json();
  return data.secure_url;
}

function openAddModal() {
  editingProduct.value = null;
  form.value = {
    category_id: categories.value[0]?.id || "",
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
    ...product,
    variants: product.variants || [],
  };
  pendingImageFile.value = null;
  showModal.value = true;
}

const saving = ref(false);
const showPresets = ref(false);
const optionSets = ref<any[]>([]);

async function fetchOptionSets() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/option-sets`
    );
    optionSets.value = response.data;
  } catch (e) {
    console.error("Failed to fetch option sets", e);
  }
}

onMounted(async () => {
  await Promise.all([fetchProducts(), fetchCategories(), fetchOptionSets()]);
});

function applyPreset(presetName: string) {
  const set = optionSets.value.find((s) => s.name === presetName);
  if (set && set.elements) {
    set.elements.forEach((element: any) => {
      form.value.variants.push({
        name: set.name,
        option_name: element.label,
        extra_price: parseFloat(element.price_modifier),
      });
    });
  }
}

function getIconForSet(name: string) {
  if (name.toLowerCase().includes("size")) return "🥤";
  if (name.toLowerCase().includes("sugar")) return "🍬";
  if (name.toLowerCase().includes("ice")) return "🧊";
  if (name.toLowerCase().includes("topping")) return "✨";
  return "⚡";
}

function addVariant() {
  form.value.variants.push({
    name: "",
    option_name: "",
    extra_price: 0,
  });
}

function removeVariant(index: number) {
  form.value.variants.splice(index, 1);
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  saving.value = true;

  try {
    // Upload image if selected
    if (pendingImageFile.value) {
      const imageUrl = await uploadToCloudinary(pendingImageFile.value);
      form.value.image_url = imageUrl;
    }

    if (editingProduct.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/products/${editingProduct.value.id}`,
        form.value
      );
    } else {
      await apiClient.post(
        `/staff/admin/${shopSlug}/menu/products`,
        form.value
      );
    }
    await fetchProducts();
    // await fetchProducts(); // Removed duplicate call
    showModal.value = false;
  } catch (e) {
    console.error(e);
    alert("Failed to save product");
  } finally {
    saving.value = false;
  }
}

async function deleteProduct(id: number) {
  if (!confirm("Delete this product?")) return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    await apiClient.delete(`/staff/admin/${shopSlug}/menu/products/${id}`);
    await fetchProducts();
  } catch (e) {
    alert("Failed to delete");
  }
}

function formatCurrency(val: number) {
  const symbol = authStore.shop?.currency_symbol || "$";
  if (symbol === "$") {
    return new Intl.NumberFormat("en-US", {
      style: "currency",
      currency: "USD",
    }).format(val);
  }
  // Simple custom formatting for Riel or others
  return `${new Intl.NumberFormat("en-US").format(val)} ${symbol}`;
}
</script>

<template>
  <div class="p-8">
    <div class="mb-8 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-gray-900">Products</h1>
        <p class="text-gray-500">
          Manage your items, pricing, and availability.
        </p>
      </div>
      <button
        @click="openAddModal"
        class="px-6 py-3 bg-orange-600 rounded-xl text-sm font-bold text-white shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all active:scale-95"
      >
        + Add Product
      </button>
    </div>

    <!-- Filters & Search -->
    <div class="mb-6 flex gap-4">
      <div class="flex-1 relative">
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
          placeholder="Search products..."
          class="w-full pl-10 pr-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
        />
      </div>
      <select
        v-model="selectedCategory"
        class="px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white min-w-[150px]"
      >
        <option value="all">All Categories</option>
        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
          {{ cat.name }}
        </option>
      </select>
      <select
        v-model="selectedStatus"
        class="px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white min-w-[150px]"
      >
        <option value="all">All Status</option>
        <option value="active">Active</option>
        <option value="inactive">Inactive</option>
      </select>
    </div>

    <!-- Product Table -->
    <div
      class="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden"
    >
      <table class="w-full text-left">
        <thead>
          <tr class="bg-gray-50/50 border-b border-gray-100">
            <th
              class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Product
            </th>
            <th
              class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Category
            </th>
            <th
              class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Price
            </th>
            <th
              class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Status
            </th>
            <th
              class="px-6 py-4 text-xs font-bold text-gray-400 uppercase tracking-wider text-right"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-50">
          <template v-if="loading">
            <tr v-for="i in 5" :key="i" class="animate-pulse">
              <td colspan="5" class="px-6 py-4">
                <div class="h-4 bg-gray-100 rounded-full w-full"></div>
              </td>
            </tr>
          </template>
          <tr
            v-else
            v-for="product in products.data || []"
            :key="product.id"
            class="hover:bg-gray-50/50 transition-colors"
          >
            <td class="px-6 py-4">
              <div class="flex items-center gap-3">
                <img
                  :src="product.image_url || 'https://via.placeholder.com/40'"
                  class="w-10 h-10 rounded-xl object-cover bg-gray-100"
                />
                <div>
                  <div class="font-bold text-gray-900">{{ product.name }}</div>
                  <div
                    v-if="product.variants && product.variants.length > 0"
                    class="text-xs text-orange-600 font-medium mt-0.5"
                  >
                    {{ product.variants.length }} option{{
                      product.variants.length > 1 ? "s" : ""
                    }}
                  </div>
                </div>
              </div>
            </td>
            <td class="px-6 py-4">
              <span
                class="px-3 py-1 bg-gray-100 rounded-full text-[10px] font-bold text-gray-500 uppercase"
              >
                {{ product.category?.name }}
              </span>
            </td>
            <td class="px-6 py-4 font-bold text-gray-900">
              {{ formatCurrency(product.price) }}
            </td>
            <td class="px-6 py-4">
              <div class="flex items-center gap-2">
                <div
                  :class="[
                    'w-2 h-2 rounded-full',
                    product.is_available ? 'bg-green-500' : 'bg-red-500',
                  ]"
                ></div>
                <span class="text-xs font-medium text-gray-600">{{
                  product.is_available ? "Available" : "Out of Stock"
                }}</span>
              </div>
            </td>
            <td class="px-6 py-4 text-right space-x-2">
              <button
                @click="openEditModal(product)"
                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"
                  ></path>
                </svg>
              </button>
              <button
                @click="deleteProduct(product.id)"
                class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
              >
                <svg
                  class="w-5 h-5"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
                  ></path>
                </svg>
              </button>
            </td>
          </tr>
        </tbody>
      </table>

      <!-- Pagination Controls -->
      <div
        v-if="products.total > 0"
        class="border-t border-gray-100 p-4 flex items-center justify-between bg-gray-50/50"
      >
        <div class="text-sm text-gray-500">
          Showing {{ (products.current_page - 1) * products.per_page + 1 }} to
          {{
            Math.min(products.current_page * products.per_page, products.total)
          }}
          of {{ products.total }} results
        </div>
        <div class="flex gap-2">
          <button
            @click="fetchProducts(products.current_page - 1)"
            :disabled="products.current_page === 1"
            class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-bold text-gray-600 hover:bg-white hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            Previous
          </button>
          <button
            v-for="p in products.last_page"
            :key="p"
            @click="fetchProducts(p)"
            :class="[
              'w-8 h-8 rounded-lg text-sm font-bold flex items-center justify-center transition-all',
              products.current_page === p
                ? 'bg-orange-600 text-white shadow-lg shadow-orange-200'
                : 'text-gray-600 hover:bg-white hover:shadow-sm border border-transparent hover:border-gray-200',
            ]"
          >
            {{ p }}
          </button>
          <button
            @click="fetchProducts(products.current_page + 1)"
            :disabled="products.current_page === products.last_page"
            class="px-3 py-1.5 rounded-lg border border-gray-200 text-sm font-bold text-gray-600 hover:bg-white hover:shadow-sm disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            Next
          </button>
        </div>
      </div>

      <!-- Empty State for Search -->
      <div v-else-if="!loading" class="p-12 text-center text-gray-500">
        <p class="text-lg font-medium">No products found</p>
        <p class="text-sm">Try adjusting your search or filters</p>
      </div>
    </div>

    <!-- REDESIGNED Modal for Add/Edit -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <div
        class="bg-white rounded-[32px] w-full max-w-4xl p-6 md:p-8 shadow-2xl overflow-y-auto max-h-[90vh]"
      >
        <div class="flex items-center justify-between mb-8">
          <h2 class="text-2xl font-bold">
            {{ editingProduct ? "Edit Product" : "New Product" }}
          </h2>
          <button
            @click="showModal = false"
            class="p-2 text-gray-400 hover:text-gray-600 hover:bg-gray-100 rounded-full transition-colors"
          >
            <svg
              class="w-6 h-6"
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
          </button>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <!-- LEFT COLUMN: Product Details -->
          <div class="space-y-6">
            <div>
              <label
                class="block text-xs font-bold text-gray-400 uppercase mb-2"
                >Image</label
              >
              <ImageUpload
                v-model="form.image_url"
                folder="products"
                @fileSelected="pendingImageFile = $event"
              />
            </div>

            <div>
              <label
                class="block text-xs font-bold text-gray-400 uppercase mb-2"
                >Product Name</label
              >
              <input
                v-model="form.name"
                type="text"
                placeholder="e.g. Iced Latte"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              />
            </div>

            <div class="grid grid-cols-2 gap-4">
              <div>
                <label
                  class="block text-xs font-bold text-gray-400 uppercase mb-2"
                  >Category</label
                >
                <select
                  v-model="form.category_id"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white"
                >
                  <option
                    v-for="cat in categories"
                    :key="cat.id"
                    :value="cat.id"
                  >
                    {{ cat.name }}
                  </option>
                </select>
              </div>
              <div>
                <label
                  class="block text-xs font-bold text-gray-400 uppercase mb-2"
                  >Price ({{ authStore.shop?.currency_symbol || "$" }})</label
                >
                <input
                  v-model="form.price"
                  type="number"
                  step="0.01"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
                />
              </div>
            </div>

            <div>
              <label
                class="block text-xs font-bold text-gray-400 uppercase mb-2"
                >Status</label
              >
              <div class="flex items-center gap-3 pt-1">
                <input
                  v-model="form.is_available"
                  type="checkbox"
                  class="w-5 h-5 accent-orange-600 rounded cursor-pointer"
                />
                <span class="text-sm font-medium text-gray-600"
                  >Available in Shop</span
                >
              </div>
            </div>
          </div>

          <!-- RIGHT COLUMN: Options & Variants -->
          <div
            class="bg-gray-50 rounded-2xl p-6 border border-gray-100 flex flex-col h-full"
          >
            <div
              class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4"
            >
              <div>
                <label class="block text-xs font-bold text-gray-400 uppercase"
                  >Options</label
                >
                <p class="text-[10px] text-gray-500 mt-1">
                  Sizes, toppings, etc.
                </p>
              </div>
              <div class="flex flex-wrap gap-2 w-full sm:w-auto">
                <!-- Quick Preset Dropdown -->
                <div class="relative flex-1 sm:flex-none">
                  <!-- Invisible Backdrop to close dropdown -->
                  <div
                    v-if="showPresets"
                    @click="showPresets = false"
                    class="fixed inset-0 z-10 cursor-default"
                  ></div>

                  <button
                    @click="showPresets = !showPresets"
                    class="relative z-20 w-full sm:w-auto px-3 py-2 bg-white border border-gray-200 text-gray-600 hover:bg-gray-50 rounded-lg text-xs font-bold transition-colors flex items-center justify-center gap-1 whitespace-nowrap"
                  >
                    ⚡ Presets
                    <svg
                      class="w-3 h-3"
                      fill="none"
                      stroke="currentColor"
                      viewBox="0 0 24 24"
                    >
                      <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M19 9l-7 7-7-7"
                      />
                    </svg>
                  </button>
                  <!-- Dropdown Menu -->
                  <div
                    v-if="showPresets"
                    class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-xl border border-gray-100 z-20 overflow-hidden text-left"
                  >
                    <button
                      v-for="set in optionSets"
                      :key="set.id"
                      @click="
                        applyPreset(set.name);
                        showPresets = false;
                      "
                      class="w-full text-left px-4 py-2 text-xs font-medium text-gray-700 hover:bg-orange-50 hover:text-orange-600 block"
                    >
                      {{ getIconForSet(set.name) }} {{ set.name }}
                    </button>

                    <!-- Manage Presets Link -->
                    <div class="border-t border-gray-100 mt-1 pt-1">
                      <router-link
                        :to="{ name: 'admin-option-sets' }"
                        class="block w-full text-left px-4 py-2 text-[10px] uppercase tracking-wider font-bold text-gray-400 hover:text-orange-600 hover:bg-orange-50 transition-colors"
                      >
                        ⚙️ Manage Presets
                      </router-link>
                    </div>
                  </div>
                </div>

                <button
                  @click="addVariant"
                  type="button"
                  class="flex-1 sm:flex-none px-3 py-2 bg-orange-600 text-white hover:bg-orange-500 rounded-lg text-xs font-bold transition-colors shadow-lg shadow-orange-200 whitespace-nowrap"
                >
                  + Add Custom
                </button>
              </div>
            </div>

            <div
              v-if="form.variants.length === 0"
              class="flex-1 flex flex-col items-center justify-center text-center text-gray-400 text-sm py-8 border-2 border-dashed border-gray-200 rounded-xl"
            >
              <p>No options added.</p>
              <span class="text-xs opacity-75 mt-1"
                >Use "Presets" or add manually.</span
              >
            </div>

            <div
              v-else
              class="space-y-3 flex-1 overflow-y-auto pr-2 custom-scrollbar"
            >
              <div
                v-if="form.variants.length > 0"
                class="flex justify-end mb-2 sticky top-0 bg-gray-50 py-1 z-10"
              >
                <button
                  @click="form.variants = []"
                  class="text-[10px] text-red-500 hover:text-red-700 font-bold uppercase tracking-wider bg-white px-2 py-1 rounded shadow-sm"
                >
                  Clear All
                </button>
              </div>
              <div
                v-for="(variant, index) in form.variants"
                :key="index"
                class="flex gap-2 items-start p-3 bg-white rounded-xl border border-gray-200 shadow-sm"
              >
                <div class="flex-1 grid grid-cols-7 gap-2">
                  <div class="col-span-3">
                    <label
                      class="block text-[8px] font-bold text-gray-300 uppercase mb-1"
                      v-if="index === 0"
                      >Group</label
                    >
                    <input
                      v-model="variant.name"
                      type="text"
                      placeholder="e.g. Size"
                      class="w-full px-2 py-1.5 text-xs rounded border border-gray-100 bg-gray-50 focus:bg-white focus:ring-1 focus:ring-orange-500 outline-none transition-all"
                    />
                  </div>
                  <div class="col-span-2">
                    <label
                      class="block text-[8px] font-bold text-gray-300 uppercase mb-1"
                      v-if="index === 0"
                      >Option</label
                    >
                    <input
                      v-model="variant.option_name"
                      type="text"
                      placeholder="e.g. Regular"
                      class="w-full px-2 py-1.5 text-xs rounded border border-gray-100 bg-gray-50 focus:bg-white focus:ring-1 focus:ring-orange-500 outline-none transition-all"
                    />
                  </div>
                  <div class="col-span-2">
                    <label
                      class="block text-[8px] font-bold text-gray-300 uppercase mb-1"
                      v-if="index === 0"
                      >Price</label
                    >
                    <input
                      v-model="variant.extra_price"
                      type="number"
                      step="0.01"
                      placeholder="+Price"
                      class="w-full px-2 py-1.5 text-xs rounded border border-gray-100 bg-gray-50 focus:bg-white focus:ring-1 focus:ring-orange-500 outline-none transition-all"
                    />
                  </div>
                </div>
                <div :class="{ 'mt-4': index === 0 }">
                  <button
                    @click="removeVariant(index)"
                    type="button"
                    class="p-1.5 text-gray-300 hover:text-red-500 hover:bg-red-50 rounded-lg transition-colors shrink-0"
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
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="flex justify-end gap-3 mt-8 border-t border-gray-50 pt-6">
          <button
            @click="showModal = false"
            class="px-6 py-3 font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleSubmit"
            :disabled="saving"
            class="px-8 py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center justify-center gap-2 min-w-[160px]"
          >
            <span
              v-if="saving"
              class="w-5 h-5 border-2 border-white/30 border-t-white rounded-full animate-spin"
            ></span>
            {{ saving ? "Saving..." : "Save Product" }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
