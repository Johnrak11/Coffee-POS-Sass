<template>
  <div class="p-6 max-w-7xl mx-auto">
    <!-- Header -->
    <div
      class="flex flex-col md:flex-row justify-between items-start md:items-center mb-8 gap-4"
    >
      <div>
        <h1 class="text-2xl font-bold text-gray-800 flex items-center gap-2">
          ⚙️ Option Sets
        </h1>
        <p class="text-gray-500 mt-1">
          Manage reusable preset options for your products
        </p>
      </div>
      <button
        @click="openModal()"
        class="bg-orange-600 hover:bg-orange-700 text-white px-4 py-2 rounded-lg font-medium shadow-lg shadow-orange-200 transition-colors flex items-center gap-2"
      >
        <span>+ New Option Set</span>
      </button>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-b-2 border-orange-600"
      ></div>
    </div>

    <!-- Empty State -->
    <div
      v-else-if="optionSets.length === 0"
      class="text-center py-12 bg-gray-50 rounded-xl border-2 border-dashed border-gray-200"
    >
      <div class="text-gray-400 mb-4 text-4xl">🗄️</div>
      <h3 class="text-lg font-medium text-gray-600">No option sets found</h3>
      <p class="text-gray-500 mb-6">
        Create your first set of options (e.g. Size, Toppings) to use as
        presets.
      </p>
      <button
        @click="openModal()"
        class="text-orange-600 font-bold hover:underline"
      >
        Create Option Set
      </button>
    </div>

    <!-- Option Sets Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <div
        v-for="set in optionSets"
        :key="set.id"
        class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow p-5"
      >
        <div class="flex justify-between items-start mb-4">
          <h3 class="font-bold text-lg text-gray-800">{{ set.name }}</h3>
          <div class="flex gap-2">
            <button
              @click="openModal(set)"
              class="text-gray-400 hover:text-blue-600 transition-colors"
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
              @click="confirmDelete(set)"
              class="text-gray-400 hover:text-red-600 transition-colors"
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

        <div class="space-y-2">
          <div
            v-for="element in set.elements"
            :key="element.id"
            class="flex justify-between items-center text-sm p-2 bg-gray-50 rounded-lg"
          >
            <span class="text-gray-700 font-medium">{{ element.label }}</span>
            <span
              class="text-gray-500 bg-white px-2 py-0.5 rounded border border-gray-100 text-xs"
            >
              {{ formatCurrency(element.price_modifier) }}
            </span>
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
            {{ editingSet ? "Edit Option Set" : "New Option Set" }}
          </h2>

          <form @submit.prevent="handleSubmit">
            <!-- Set Name -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-1"
                >Set Name</label
              >
              <input
                v-model="form.name"
                type="text"
                class="w-full px-4 py-2 rounded-xl border border-gray-300 focus:ring-2 focus:ring-orange-500 focus:border-orange-500 outline-none transition-all"
                placeholder="e.g. Smoothie Add-ons"
                required
              />
            </div>

            <!-- Elements -->
            <div class="mb-6">
              <label class="block text-sm font-medium text-gray-700 mb-2"
                >Options</label
              >
              <div class="space-y-3">
                <div
                  v-for="(element, index) in form.elements"
                  :key="index"
                  class="flex gap-2 items-start group"
                >
                  <div class="flex-1">
                    <input
                      v-model="element.label"
                      type="text"
                      placeholder="Option Name (e.g. Extra)"
                      class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-1 focus:ring-orange-500 outline-none text-sm"
                      required
                    />
                  </div>
                  <div class="w-24">
                    <input
                      v-model.number="element.price_modifier"
                      type="number"
                      step="0.01"
                      placeholder="Price"
                      class="w-full px-3 py-2 rounded-lg border border-gray-300 focus:ring-1 focus:ring-orange-500 outline-none text-sm"
                    />
                  </div>
                  <button
                    type="button"
                    @click="removeElement(index)"
                    class="p-2 text-gray-400 hover:text-red-500"
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
                  </button>
                </div>
              </div>
              <button
                type="button"
                @click="addElement"
                class="mt-3 text-sm text-orange-600 font-semibold hover:text-orange-700 flex items-center gap-1"
              >
                + Add Option
              </button>
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
                {{ saving ? "Saving..." : "Save Set" }}
              </button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import apiClient from "@/services/api";

const authStore = useAuthStore();
const optionSets = ref<any[]>([]);
const loading = ref(true);
const saving = ref(false);
const showModal = ref(false);
const editingSet = ref<any>(null);

const form = ref({
  name: "",
  elements: [] as any[],
});

function addElement() {
  form.value.elements.push({ label: "", price_modifier: 0 });
}

function removeElement(index: number) {
  form.value.elements.splice(index, 1);
}

function formatCurrency(val: number) {
  const symbol = authStore.shop?.currency_symbol || "$";
  if (val === 0) return `+${symbol}0`;
  if (symbol === "$") {
    return (
      "+" +
      new Intl.NumberFormat("en-US", {
        style: "currency",
        currency: "USD",
      }).format(val)
    );
  }
  return `+${new Intl.NumberFormat("en-US").format(val)} ${symbol}`;
}

async function fetchOptionSets() {
  loading.value = true;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/option-sets`
    );
    optionSets.value = response.data;
  } catch (e) {
    console.error(e);
  } finally {
    loading.value = false;
  }
}

function openModal(set: any = null) {
  editingSet.value = set;
  if (set) {
    form.value = {
      name: set.name,
      // clone elements to avoid reactive issues
      elements: set.elements.map((e: any) => ({ ...e })),
    };
  } else {
    form.value = {
      name: "",
      elements: [{ label: "", price_modifier: 0 }],
    };
  }
  showModal.value = true;
}

async function handleSubmit() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  saving.value = true;
  try {
    if (editingSet.value) {
      await apiClient.put(
        `/staff/admin/${shopSlug}/menu/option-sets/${editingSet.value.id}`,
        form.value
      );
    } else {
      await apiClient.post(
        `/staff/admin/${shopSlug}/menu/option-sets`,
        form.value
      );
    }
    await fetchOptionSets();
    showModal.value = false;
  } catch (e) {
    console.error(e);
    alert("Failed to save option set");
  } finally {
    saving.value = false;
  }
}

async function confirmDelete(set: any) {
  if (
    !confirm(
      `Delete "${set.name}"? This will not affect existing products/orders.`
    )
  )
    return;

  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    await apiClient.delete(
      `/staff/admin/${shopSlug}/menu/option-sets/${set.id}`
    );
    await fetchOptionSets();
  } catch (e) {
    console.error(e);
  }
}

onMounted(() => {
  fetchOptionSets();
});
</script>
