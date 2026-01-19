<script setup lang="ts">
import { onMounted, ref } from "vue";
import { useI18n } from "vue-i18n";
import apiClient from "@/api";
import { useAuthStore } from "@/stores/auth";
import { useUIStore } from "@/stores/ui";
import { BaseButton, BaseCard, BaseInput } from "@/components/common";
import ImageUpload from "@/components/ImageUpload.vue";

const { t } = useI18n();
const authStore = useAuthStore();
const uiStore = useUIStore();
const loading = ref(true);
const saving = ref(false);
const pendingLogoFile = ref<File | null>(null);

const form = ref({
  name: "",
  logo_url: "",
  address: "",
  phone: "",
  receipt_footer: "",
  currency_symbol: "$",
  exchange_rate: 4100,
  primary_color: "#F97316",
  bakong_account_id: "",
  merchant_name: "",
  merchant_city: "",
  bakong_telegram_chat_id: "",
  theme_mode: "light",
});

onMounted(async () => {
  await fetchSettings();
});

async function fetchSettings() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/settings`
    );
    form.value = response.data;
    authStore.shop = response.data;
    console.log("Settings  loaded and shop updated:", authStore.shop);
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to load settings");
  } finally {
    loading.value = false;
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
  formData.append("folder", "coffee-pos/logos");

  const response = await fetch(
    `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
    { method: "POST", body: formData }
  );

  if (!response.ok) throw new Error("Image upload failed");
  const data = await response.json();
  return data.secure_url;
}

async function saveSettings() {
  saving.value = true;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";

  try {
    if (pendingLogoFile.value) {
      const logoUrl = await uploadToCloudinary(pendingLogoFile.value);
      form.value.logo_url = logoUrl;
    }

    await apiClient.put(`/staff/admin/${shopSlug}/menu/settings`, form.value);
    if (authStore.shop) {
      Object.assign(authStore.shop, form.value);
    }

    await fetchSettings();
    pendingLogoFile.value = null;

    uiStore.showToast("success", "Settings saved successfully!");
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to update settings");
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div
    class="p-8 max-w-4xl mx-auto bg-bg-secondary dark:bg-gray-900 min-h-screen"
  >
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-text-primary dark:text-white">
        {{ t("nav.settings") || "Shop Settings" }}
      </h1>
      <p class="text-text-secondary dark:text-gray-400">
        Configure your shop profile and receipt preferences.
      </p>
    </div>

    <div v-if="loading" class="animate-pulse space-y-6">
      <div
        class="h-64 bg-white dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700"
      ></div>
    </div>

    <BaseCard v-else padding="lg" shadow="md" rounded="2xl">
      <div class="space-y-8">
        <!-- General Profile Section -->
        <div>
          <h3
            class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2"
          >
            <span
              class="w-1.5 h-6 bg-primary-600 dark:bg-primary-500 rounded-full"
            ></span>
            General Profile
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Logo Col -->
            <div
              class="flex flex-col items-center justify-start p-6 bg-gray-50 dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700"
            >
              <label
                class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase mb-4 self-start"
              >
                Shop Logo
              </label>
              <ImageUpload
                v-model="form.logo_url"
                folder="logos"
                @fileSelected="pendingLogoFile = $event"
              />
              <p
                class="text-xs text-gray-400 dark:text-gray-500 mt-4 text-center"
              >
                Recommended: 800x800px or square image.
              </p>
            </div>

            <!-- Fields Col -->
            <div class="space-y-6">
              <BaseInput
                v-model="form.name"
                label="Shop Name"
                placeholder="Enter shop name"
              />
              <BaseInput
                v-model="form.phone"
                label="Phone Number"
                type="tel"
                placeholder="+855 12 345 678"
              />
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >
                  Address
                </label>
                <textarea
                  v-model="form.address"
                  rows="3"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                  placeholder="Street address, city, country"
                ></textarea>
              </div>
            </div>
          </div>

          <!-- Exchange Rate -->
          <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >
                Exchange Rate (1 USD = ? KHR)
              </label>
              <div class="relative">
                <input
                  v-model="form.exchange_rate"
                  type="number"
                  min="0"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all"
                  placeholder="4100"
                />
                <div
                  class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500 dark:text-gray-400 font-bold"
                >
                  ៛
                </div>
              </div>
              <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">
                Used for cash payments in Khmer Riel.
              </p>
            </div>
          </div>
        </div>

        <!-- Payment Configuration Section -->
        <div class="pt-8 border-t border-gray-100 dark:border-gray-700">
          <h3
            class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2"
          >
            <span
              class="w-1.5 h-6 bg-primary-600 dark:bg-primary-500 rounded-full"
            ></span>
            Bakong Payment Configuration
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <BaseInput
                v-model="form.bakong_account_id"
                label="Bakong Account ID"
                placeholder="your_account@bakong"
              />
            </div>
            <BaseInput
              v-model="form.merchant_name"
              label="Merchant Name"
              placeholder="Lucky Café"
            />
            <BaseInput
              v-model="form.merchant_city"
              label="Merchant City"
              placeholder="Phnom Penh"
            />
            <div class="md:col-span-2">
              <BaseInput
                v-model="form.bakong_telegram_chat_id"
                label="Telegram Chat ID (Optional)"
                placeholder="Enter Telegram Chat ID for notifications"
              />
            </div>
          </div>
          <div
            class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl flex gap-3"
          >
            <svg
              class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="2"
                d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
              />
            </svg>
            <p class="text-sm text-blue-700 dark:text-blue-300">
              These details will appear on guest payment QR codes for Bakong
              transfers.
            </p>
          </div>
        </div>

        <div class="pt-8 flex justify-end">
          <BaseButton
            variant="primary"
            size="lg"
            @click="saveSettings"
            :loading="saving"
            :disabled="saving"
          >
            {{
              saving ? "Saving Changes..." : t("common.save") || "Save Settings"
            }}
          </BaseButton>
        </div>
      </div>
    </BaseCard>
  </div>
</template>
