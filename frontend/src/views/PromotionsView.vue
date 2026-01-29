<script setup lang="ts">
import { onMounted, ref, computed } from "vue";
import { useI18n } from "vue-i18n";
import apiClient from "../api";
import { useAuthStore } from "../stores/auth";
import { useUIStore } from "../stores/ui";
import {
  BaseButton,
  BaseCard,
  BaseInput,
  BaseMultiSelect,
} from "../components/common";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();

const promotions = ref<any[]>([]);
const products = ref<any[]>([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingPromotion = ref<any>(null);

const form = ref({
  name: "",
  code: "",
  type: "percentage",
  value: 0,
  is_active: true,
  // Buy X Get Y specific
  buy_qty: 1,
  get_qty: 1,
  buy_product_id: null as number | null,
  get_product_id: null as number | null,
  discount_percent: 100,
  applicable_products: [] as number[],
});

async function fetchPromotions() {
  loading.value = true;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/promotions`,
    );
    promotions.value = response.data;
  } catch (e) {
    uiStore.showToast("error", "Failed to load promotions");
  } finally {
    loading.value = false;
  }
}

async function fetchProducts() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/products`,
      { params: { limit: 100 } },
    );
    products.value = response.data.data || [];
  } catch (e) {
    console.error("Failed to load products");
  }
}

// Filter products: Only show products that are NOT already in another ACTIVE promotion

const availableProducts = computed(() => {
  // 1. Collect all product IDs currently used in OTHER active promotions
  const usedProductIds = new Set<number>();

  promotions.value.forEach((promo) => {
    // Skip if it's the one we are currently editing
    if (editingPromotion.value && editingPromotion.value.id === promo.id)
      return;

    // Only care about active promotions
    if (!promo.is_active) return;

    // Helper to add IDs
    const addIds = (ids: any) => {
      if (Array.isArray(ids)) {
        ids.forEach((id) => usedProductIds.add(Number(id)));
      }
    };

    // Extract IDs from rules
    if (promo.rules) {
      addIds(promo.rules.applicable_product_ids);
      addIds(promo.rules.buy_product_ids);
      addIds(promo.rules.get_product_ids);
    }
  });

  // 2. Filter the main products list
  return products.value.filter((p) => !usedProductIds.has(p.id));
});

onMounted(async () => {
  await fetchProducts();
  await fetchPromotions();
});

function openAddModal() {
  editingPromotion.value = null;
  form.value = {
    name: "",
    code: "",
    type: "percentage",
    value: 0,
    is_active: true,
    buy_qty: 1,
    get_qty: 1,
    buy_product_id: null,
    get_product_id: null,
    discount_percent: 100,
    applicable_products: [],
  };
  showModal.value = true;
}

function openEditModal(promo: any) {
  editingPromotion.value = promo;
  form.value = {
    name: promo.name,
    code: promo.code,
    type: promo.type,
    value: promo.value ? parseFloat(promo.value) : 0,
    is_active: Boolean(promo.is_active),
    buy_qty: promo.rules?.buy_quantity || 1,
    get_qty: promo.rules?.get_quantity || 1,
    buy_product_id: promo.rules?.buy_product_ids?.[0] || null,
    get_product_id: promo.rules?.get_product_ids?.[0] || null,
    discount_percent: promo.rules?.discount_percent || 100,
    applicable_products: promo.rules?.applicable_product_ids || [],
  };
  showModal.value = true;
}

async function deletePromotion(id: number) {
  if (!confirm("Delete this promotion?")) return;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    await apiClient.delete(`/staff/admin/${shopSlug}/menu/promotions/${id}`);
    uiStore.showToast("success", "Promotion deleted");
    await fetchPromotions();
  } catch (e) {
    uiStore.showToast("error", "Failed to delete");
  }
}

async function handleSubmit() {
  saving.value = true;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";

  // Construct payload
  const payload: any = {
    name: form.value.name,
    code: form.value.code,
    type: form.value.type,
    is_active: form.value.is_active,
  };

  if (form.value.type === "percentage") {
    payload.value = form.value.value;
    payload.rules = {
      applicable_product_ids: form.value.applicable_products,
    };
  } else if (form.value.type === "buy_x_get_y") {
    payload.rules = {
      buy_quantity: form.value.buy_qty,
      get_quantity: form.value.get_qty,
      buy_product_ids: form.value.buy_product_id
        ? [form.value.buy_product_id]
        : [],
      get_product_ids: form.value.get_product_id
        ? [form.value.get_product_id]
        : [],
      discount_percent: form.value.discount_percent,
    };
  }

  try {
    if (editingPromotion.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/promotions/${editingPromotion.value.id}`,
        payload,
      );
      uiStore.showToast("success", "Promotion updated");
    } else {
      await apiClient.post(`/staff/admin/${shopSlug}/menu/promotions`, payload);
      uiStore.showToast("success", "Promotion added");
    }
    await fetchPromotions();
    showModal.value = false;
  } catch (e) {
    uiStore.showToast("error", "Failed to save promotion");
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div
    class="h-full flex flex-col p-6 bg-bg-secondary dark:bg-gray-900 overflow-hidden"
  >
    <div class="mb-6 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-text-primary dark:text-white">
          Promotions
        </h1>
        <p class="text-text-secondary dark:text-gray-400">
          Manage discounts and special offers.
        </p>
      </div>
      <BaseButton variant="primary" size="md" @click="openAddModal">
        + New Promotion
      </BaseButton>
    </div>

    <!-- Table -->
    <div
      class="flex-1 bg-app-surface rounded-2xl border border-app-border overflow-hidden flex flex-col"
    >
      <div class="overflow-x-auto flex-1">
        <table class="w-full">
          <thead
            class="bg-app-bg text-app-muted text-xs uppercase font-bold sticky top-0 border-b border-app-border"
          >
            <tr>
              <th class="text-left px-4 py-3">Name</th>
              <th class="text-left px-4 py-3">Type</th>
              <th class="text-left px-4 py-3">Details</th>
              <th class="text-left px-4 py-3">Status</th>
              <th class="text-right px-4 py-3">Actions</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-app-border">
            <tr v-if="loading">
              <td colspan="5" class="p-8 text-center">Loading...</td>
            </tr>
            <tr v-else-if="promotions.length === 0">
              <td colspan="5" class="p-12 text-center text-gray-500">
                No promotions found
              </td>
            </tr>
            <tr
              v-for="promo in promotions"
              :key="promo.id"
              class="hover:bg-app-bg"
            >
              <td class="p-4 font-bold">{{ promo.name }}</td>
              <td class="p-4">
                <span
                  class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs uppercase"
                  >{{ promo.type.replace(/_/g, " ") }}</span
                >
              </td>
              <td class="p-4">
                <span v-if="promo.type === 'percentage'"
                  >{{ promo.value }}% Off</span
                >

                <span v-else>
                  Buy {{ promo.rules?.buy_quantity }} Get
                  {{ promo.rules?.get_quantity }} ({{
                    promo.rules?.discount_percent
                  }}% Off)
                </span>
              </td>
              <td class="p-4">
                <span
                  :class="[
                    'px-3 py-1.5 rounded-lg text-xs font-bold flex items-center gap-1.5 w-fit',
                    promo.is_active
                      ? 'bg-success-100 dark:bg-success-900/30 text-success-600 dark:text-success-400'
                      : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-400',
                  ]"
                >
                  <div
                    :class="[
                      'w-1.5 h-1.5 rounded-full',
                      promo.is_active ? 'bg-success-500' : 'bg-gray-400',
                    ]"
                  ></div>
                  {{ promo.is_active ? "Active" : "Inactive" }}
                </span>
              </td>
              <td class="p-4">
                <div class="flex justify-end gap-2">
                  <BaseButton
                    variant="ghost"
                    size="sm"
                    @click="openEditModal(promo)"
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
                    variant="ghost"
                    size="sm"
                    class="text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20"
                    @click="deletePromotion(promo.id)"
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
    </div>

    <!-- Modal -->
    <div
      v-if="showModal"
      class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/30 backdrop-blur-sm"
    >
      <BaseCard
        padding="lg"
        shadow="lg"
        rounded="2xl"
        class="w-full max-w-lg max-h-[90vh] overflow-y-auto"
      >
        <h2 class="text-2xl font-bold mb-6 text-gray-900 dark:text-white">
          {{ editingPromotion ? "Edit Promotion" : "New Promotion" }}
        </h2>
        <form @submit.prevent="handleSubmit" class="space-y-4">
          <BaseInput
            v-model="form.name"
            label="Promotion Name"
            placeholder="e.g. Summer Sale"
            required
          />
          <BaseInput
            v-model="form.code"
            label="Code (Optional)"
            placeholder="e.g. SAVE10"
          />

          <div>
            <label class="block text-sm font-medium mb-1">Type</label>
            <select
              v-model="form.type"
              class="w-full px-4 py-2 border rounded-xl dark:bg-gray-800"
            >
              <option value="percentage">Percentage Discount</option>
              <option value="buy_x_get_y">Buy X Get Y (BOGO)</option>
            </select>
          </div>

          <div v-if="form.type === 'percentage'">
            <BaseInput
              v-model.number="form.value"
              label="Percentage (%)"
              type="number"
            />
            <div class="mt-4">
              <BaseMultiSelect
                v-model="form.applicable_products"
                :options="
                  availableProducts.map((p) => ({ value: p.id, label: p.name }))
                "
                label="Applicable Products (Optional)"
                placeholder="Select products (leave empty for All)"
              />
            </div>
          </div>

          <!-- BOGO Logic -->
          <div
            v-if="form.type === 'buy_x_get_y'"
            class="space-y-3 p-4 bg-gray-50 dark:bg-gray-800 rounded-xl"
          >
            <div class="grid grid-cols-2 gap-4">
              <BaseInput
                v-model.number="form.buy_qty"
                label="Buy Qty"
                type="number"
              />
              <BaseInput
                v-model.number="form.get_qty"
                label="Get Qty"
                type="number"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Buy Product</label>
              <select
                v-model="form.buy_product_id"
                class="w-full px-4 py-2 border rounded-xl dark:bg-gray-800"
              >
                <option :value="null">Any Product (Not supported yet)</option>
                <option v-for="p in products" :key="p.id" :value="p.id">
                  {{ p.name }}
                </option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Get Product</label>
              <select
                v-model="form.get_product_id"
                class="w-full px-4 py-2 border rounded-xl dark:bg-gray-800"
              >
                <option :value="null">Same as Buy Product</option>
                <option v-for="p in products" :key="p.id" :value="p.id">
                  {{ p.name }}
                </option>
              </select>
            </div>
            <BaseInput
              v-model.number="form.discount_percent"
              label="Discount % on 'Get' items"
              type="number"
              placeholder="100 for Free"
            />
          </div>

          <div class="flex items-center gap-2">
            <input
              type="checkbox"
              v-model="form.is_active"
              id="is_active"
              class="w-5 h-5"
            />
            <label for="is_active">Active</label>
          </div>

          <div class="flex justify-end gap-2 pt-4">
            <BaseButton type="button" variant="ghost" @click="showModal = false"
              >Cancel</BaseButton
            >
            <BaseButton type="submit" variant="primary" :loading="saving"
              >Save</BaseButton
            >
          </div>
        </form>
      </BaseCard>
    </div>
  </div>
</template>
