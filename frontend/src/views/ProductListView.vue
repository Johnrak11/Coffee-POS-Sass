<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";

const authStore = useAuthStore();
const products = ref<any[]>([]);
const categories = ref<any[]>([]);
const loading = ref(true);
const showModal = ref(false);
const editingProduct = ref<any>(null);

const form = ref<any>({
  category_id: "",
  name: "",
  price: 0,
  image_url: "",
  is_available: true,
  variants: [],
});

onMounted(async () => {
  await Promise.all([fetchProducts(), fetchCategories()]);
});

async function fetchProducts() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/products`
    );
    products.value = response.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

async function fetchCategories() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  const response = await apiClient.get(
    `/staff/admin/${shopSlug}/menu/categories`
  );
  categories.value = response.data;
}

function openAddModal() {
  editingProduct.value = null;
  form.value = {
    category_id: categories.value[0]?.id || "",
    name: "",
    price: 0,
    image_url: "",
    is_available: true,
    variants: [],
  };
  showModal.value = true;
}

function openEditModal(product: any) {
  editingProduct.value = product;
  form.value = {
    ...product,
    variants: product.variants || [],
  };
  showModal.value = true;
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
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
    showModal.value = false;
  } catch (e) {
    alert("Failed to save product");
  }
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
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(val);
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
            v-for="product in products"
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
    </div>

    <!-- Modal for Add/Edit -->
    <div
      v-if="showModal"
      class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 flex items-center justify-center p-4"
    >
      <div
        class="bg-white rounded-[32px] w-full max-w-lg p-8 shadow-2xl overflow-y-auto max-h-[90vh]"
      >
        <h2 class="text-2xl font-bold mb-6">
          {{ editingProduct ? "Edit Product" : "New Product" }}
        </h2>

        <div class="space-y-4">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Category</label
            >
            <select
              v-model="form.category_id"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none bg-white"
            >
              <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                {{ cat.name }}
              </option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Product Name</label
            >
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
            />
          </div>
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label
                class="block text-xs font-bold text-gray-400 uppercase mb-2"
                >Price ($)</label
              >
              <input
                v-model="form.price"
                type="number"
                step="0.01"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              />
            </div>
            <div>
              <label
                class="block text-xs font-bold text-gray-400 uppercase mb-2"
                >Availability</label
              >
              <div class="flex items-center gap-3 pt-3">
                <input
                  v-model="form.is_available"
                  type="checkbox"
                  class="w-5 h-5 accent-orange-600"
                />
                <span class="text-sm font-medium text-gray-600">In Stock</span>
              </div>
            </div>
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Image URL</label
            >
            <input
              v-model="form.image_url"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="https://..."
            />
          </div>

          <!-- Variants Section -->
          <div class="border-t border-gray-200 pt-6 mt-6">
            <div class="flex justify-between items-center mb-4">
              <div>
                <label class="block text-xs font-bold text-gray-400 uppercase"
                  >Product Options</label
                >
                <p class="text-xs text-gray-500 mt-1">
                  Add Size, Sugar, Ice, Toppings, etc.
                </p>
              </div>
              <button
                @click="addVariant"
                type="button"
                class="px-3 py-2 bg-orange-100 text-orange-600 hover:bg-orange-200 rounded-lg text-xs font-bold transition-colors"
              >
                + Add Option
              </button>
            </div>

            <div
              v-if="form.variants.length === 0"
              class="text-center text-gray-400 text-sm py-4"
            >
              No options added yet
            </div>

            <div v-else class="space-y-3">
              <div
                v-for="(variant, index) in form.variants"
                :key="index"
                class="flex gap-2 items-start p-3 bg-gray-50 rounded-xl border border-gray-100"
              >
                <div class="flex-1 grid grid-cols-3 gap-2">
                  <div>
                    <input
                      v-model="variant.name"
                      type="text"
                      placeholder="Group (e.g., Size)"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
                    />
                  </div>
                  <div>
                    <input
                      v-model="variant.option_name"
                      type="text"
                      placeholder="Option (e.g., Large)"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
                    />
                  </div>
                  <div>
                    <input
                      v-model="variant.extra_price"
                      type="number"
                      step="0.01"
                      placeholder="+Price"
                      class="w-full px-3 py-2 text-sm rounded-lg border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
                    />
                  </div>
                </div>
                <button
                  @click="removeVariant(index)"
                  type="button"
                  class="p-2 text-red-500 hover:bg-red-50 rounded-lg transition-colors shrink-0"
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
                    />
                  </svg>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="flex gap-4 mt-8">
          <button
            @click="showModal = false"
            class="flex-1 py-3 font-bold text-gray-500 hover:bg-gray-50 rounded-xl transition-colors"
          >
            Cancel
          </button>
          <button
            @click="handleSubmit"
            class="flex-1 py-3 bg-orange-600 text-white font-bold rounded-xl shadow-lg shadow-orange-200 hover:bg-orange-500 transition-colors"
          >
            Save Product
          </button>
        </div>
      </div>
    </div>
  </div>
</template>
