<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useI18n } from "vue-i18n";
import { useAuthStore } from "@/stores/auth";
import { useUIStore } from "@/stores/ui";
import { BaseButton, BaseCard, BaseInput } from "@/components/common";
import apiClient from "@/api";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();

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
    uiStore.showToast("error", "Failed to load option sets");
  } finally {
    loading.value = false;
  }
}

function openModal(set: any = null) {
  editingSet.value = set;
  if (set) {
    form.value = {
      name: set.name,
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
    uiStore.showToast(
      "success",
      editingSet.value ? t("common.success") : "Option set created"
    );
    await fetchOptionSets();
    showModal.value = false;
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to save option set");
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
    uiStore.showToast("success", "Option set deleted");
    await fetchOptionSets();
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to delete option set");
  }
}

onMounted(() => {
  fetchOptionSets();
});
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
          ⚙️ Option Sets
        </h1>
        <p class="text-text-secondary dark:text-gray-400 mt-1">
          Manage reusable preset options for your products
        </p>
      </div>
      <BaseButton variant="primary" @click="openModal()">
        + New Option Set
      </BaseButton>
    </div>

    <!-- Loading State -->
    <div v-if="loading" class="flex justify-center py-12">
      <div
        class="animate-spin rounded-full h-12 w-12 border-4 border-primary-600 border-t-transparent"
      ></div>
    </div>

    <!-- Empty State -->
    <BaseCard
      v-else-if="optionSets.length === 0"
      padding="lg"
      class="text-center"
    >
      <div class="text-gray-400 dark:text-gray-600 mb-4 text-4xl">🗄️</div>
      <h3 class="text-lg font-medium text-gray-600 dark:text-gray-300">
        No option sets found
      </h3>
      <p class="text-gray-500 dark:text-gray-400 mb-6">
        Create your first set of options (e.g. Size, Toppings) to use as
        presets.
      </p>
      <BaseButton variant="primary" @click="openModal()">
        Create Option Set
      </BaseButton>
    </BaseCard>

    <!-- Option Sets Grid -->
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <BaseCard
        v-for="set in optionSets"
        :key="set.id"
        padding="md"
        hover
        shadow="sm"
      >
        <div class="flex justify-between items-start mb-4">
          <h3 class="font-bold text-lg text-gray-800 dark:text-white">
            {{ set.name }}
          </h3>
          <div class="flex gap-2">
            <BaseButton variant="ghost" size="sm" @click="openModal(set)">
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
            <BaseButton variant="danger" size="sm" @click="confirmDelete(set)">
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

        <div class="space-y-2">
          <div
            v-for="element in set.elements"
            :key="element.id"
            class="flex justify-between items-center text-sm p-2 bg-gray-50 dark:bg-gray-800 rounded-lg"
          >
            <span class="text-gray-700 dark:text-gray-300 font-medium">{{
              element.label
            }}</span>
            <span
              class="text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-700 px-2 py-0.5 rounded border border-gray-100 dark:border-gray-600 text-xs"
            >
              {{ formatCurrency(element.price_modifier) }}
            </span>
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
            {{ editingSet ? "Edit Option Set" : "New Option Set" }}
          </h2>

          <form @submit.prevent="handleSubmit">
            <!-- Set Name -->
            <div class="mb-6">
              <BaseInput
                v-model="form.name"
                label="Set Name"
                placeholder="e.g. Size, Toppings"
                required
              />
            </div>

            <!-- Elements -->
            <div class="mb-6">
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2"
              >
                Options
              </label>
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
                      placeholder="Option Name (e.g. Large)"
                      class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 outline-none text-sm"
                      required
                    />
                  </div>
                  <div class="w-24">
                    <input
                      v-model.number="element.price_modifier"
                      type="number"
                      step="0.01"
                      placeholder="Price"
                      class="w-full px-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-1 focus:ring-primary-500 outline-none text-sm"
                    />
                  </div>
                  <BaseButton
                    type="button"
                    variant="ghost"
                    size="sm"
                    @click="removeElement(index)"
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
              <BaseButton
                type="button"
                variant="ghost"
                size="sm"
                @click="addElement"
                class="mt-3"
              >
                + Add Option
              </BaseButton>
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
                {{ saving ? "Saving..." : "Save Set" }}
              </BaseButton>
            </div>
          </form>
        </div>
      </BaseCard>
    </div>
  </div>
</template>
