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
  wifi_ssid: "",
  wifi_password: "",
  // Trusted Networks
  trusted_ips: [] as string[],
  ip_check_enabled: false,
});

async function addCurrentNetwork() {
  uiStore.showToast("info", "Detecting network...");
  try {
    const res = await apiClient.get("/utils/ip");
    const ip = res.data.ip;
    if (ip) {
      if (ip === "127.0.0.1" || ip === "::1") {
        uiStore.showToast(
          "warning",
          "Localhost Detected (127.0.0.1). This is normal for development. To test real Wi-Fi IPs, access this page from your Phone.",
        );
      } else {
        uiStore.showToast("success", "Current network added!");
      }
      addTrustedIp(ip);
    }
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", "Failed to detect IP");
  }
}

const manualIp = ref("");
function addTrustedIp(ip: string) {
  if (!form.value.trusted_ips) form.value.trusted_ips = [];
  if (!form.value.trusted_ips.includes(ip)) {
    form.value.trusted_ips.push(ip);
  }
  manualIp.value = "";
}

function removeTrustedIp(index: number) {
  form.value.trusted_ips.splice(index, 1);
}

onMounted(async () => {
  await fetchSettings();
});

async function fetchSettings() {
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    const response = await apiClient.get(
      `/staff/admin/${shopSlug}/menu/settings`,
    );
    const data = response.data;

    // Explicitly map fields to preserve defaults and ensures types
    form.value.name = data.name;
    form.value.logo_url = data.logo_url;
    form.value.address = data.address;
    form.value.phone = data.phone;
    form.value.receipt_footer = data.receipt_footer;
    form.value.currency_symbol = data.currency_symbol || "$";
    form.value.exchange_rate = data.exchange_rate || 4100;
    form.value.primary_color = data.primary_color || "#F97316";
    form.value.bakong_account_id = data.bakong_account_id;
    form.value.merchant_name = data.merchant_name;
    form.value.merchant_city = data.merchant_city;
    form.value.bakong_telegram_chat_id = data.bakong_telegram_chat_id;
    form.value.theme_mode = data.theme_mode || "light";
    form.value.wifi_ssid = data.wifi_ssid;
    form.value.wifi_password = data.wifi_password;

    // Trusted Networks
    form.value.trusted_ips = data.trusted_ips || [];
    // Ensure boolean cast (sometimes API returns 1/0)
    form.value.ip_check_enabled = Boolean(data.ip_check_enabled);

    authStore.shop = data;
    console.log("Settings loaded:", form.value);
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
    import.meta.env.VITE_CLOUDINARY_UPLOAD_PRESET || "coffee-pos-unsigned",
  );
  formData.append("folder", "coffee-pos/logos");

  const response = await fetch(
    `https://api.cloudinary.com/v1_1/${cloudName}/image/upload`,
    { method: "POST", body: formData },
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

    uiStore.showToast("success", t("common.success"));
  } catch (e) {
    console.error(e);
    uiStore.showToast("error", t("common.error"));
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="p-8 max-w-4xl bg-bg-secondary dark:bg-gray-900 min-h-screen">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-text-primary dark:text-white">
        {{ t("settings.shopSettings") }}
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
            {{ t("settings.generalProfile") }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
            <!-- Logo Col -->
            <div
              class="flex flex-col items-center justify-start p-6 bg-gray-50 dark:bg-gray-800 rounded-3xl border border-gray-100 dark:border-gray-700"
            >
              <label
                class="block text-xs font-bold text-gray-400 dark:text-gray-500 uppercase mb-4 self-start"
              >
                {{ t("settings.shopLogo") }}
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
                :label="t('settings.shopName')"
                :placeholder="t('settings.shopName')"
              />
              <BaseInput
                v-model="form.phone"
                :label="t('settings.shopPhone')"
                type="tel"
                placeholder="+855 12 345 678"
              />
              <div>
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >
                  {{ t("settings.shopAddress") }}
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

          <!-- Receipt & WiFi Configuration -->
          <div class="mt-8 pt-8 border-t border-gray-100 dark:border-gray-700">
            <h3
              class="text-lg font-bold text-gray-900 dark:text-white mb-6 flex items-center gap-2"
            >
              <span class="w-1.5 h-6 bg-emerald-500 rounded-full"></span>
              {{ t("settings.receiptDetails") }}
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div class="md:col-span-2">
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
                >
                  {{ t("settings.receiptFooter") }}
                </label>
                <textarea
                  v-model="form.receipt_footer"
                  rows="2"
                  class="w-full px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-emerald-500 outline-none transition-all"
                  placeholder="Thank you for visiting! See you again soon."
                ></textarea>
                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                  Printed at the bottom of every receipt.
                </p>
              </div>

              <BaseInput
                v-model="form.wifi_ssid"
                :label="t('settings.wifiSsid')"
                placeholder="Lucky-Guest"
              />
              <BaseInput
                v-model="form.wifi_password"
                :label="t('settings.wifiPassword')"
                placeholder="12345678"
              />
              <div class="md:col-span-2">
                <p class="text-xs text-gray-400 dark:text-gray-500">
                  WiFi details will be printed on the receipt for customers.
                </p>
              </div>
            </div>
          </div>

          <!-- Exchange Rate -->
          <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
              <label
                class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
              >
                {{ t("settings.exchangeRate") }}
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

          <!-- Trusted Networks -->
          <div class="mt-8 pt-8 border-t border-gray-100 dark:border-gray-700">
            <div class="flex items-center justify-between mb-6">
              <h3
                class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2"
              >
                <span class="w-1.5 h-6 bg-blue-500 rounded-full"></span>
                Trusted Networks (Wi-Fi)
              </h3>
              <div class="flex items-center gap-3">
                <span
                  class="text-sm text-gray-500 dark:text-gray-400 font-medium"
                >
                  {{ form.ip_check_enabled ? "Enabled" : "Disabled" }}
                </span>
                <button
                  @click="form.ip_check_enabled = !form.ip_check_enabled"
                  :class="[
                    'relative inline-flex h-6 w-11 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none focus:ring-2 focus:ring-primary-500 focus:ring-offset-2',
                    form.ip_check_enabled
                      ? 'bg-primary-600'
                      : 'bg-gray-200 dark:bg-gray-700',
                  ]"
                >
                  <span
                    aria-hidden="true"
                    :class="[
                      'pointer-events-none inline-block h-5 w-5 transform rounded-full bg-white shadow ring-0 transition duration-200 ease-in-out',
                      form.ip_check_enabled ? 'translate-x-5' : 'translate-x-0',
                    ]"
                  />
                </button>
              </div>
            </div>

            <div v-if="form.ip_check_enabled" v-auto-animate class="space-y-6">
              <div
                class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-xl text-sm text-blue-800 dark:text-blue-200 border border-blue-100 dark:border-blue-800"
              >
                Customers must be connected to one of these networks to pay with
                Cash. Otherwise, they will be restricted to KHQR payment only.
              </div>

              <!-- Trusted IPs List -->
              <div
                v-if="form.trusted_ips && form.trusted_ips.length > 0"
                class="space-y-3"
              >
                <label
                  class="block text-sm font-medium text-gray-700 dark:text-gray-300"
                >
                  Allowed IP Addresses
                </label>
                <div
                  v-for="(ip, index) in form.trusted_ips"
                  :key="index"
                  class="flex items-center justify-between p-3 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl"
                >
                  <div class="flex items-center gap-3">
                    <div
                      class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center text-green-600 dark:text-green-400"
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
                          d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0"
                        />
                      </svg>
                    </div>
                    <span
                      class="font-mono text-sm text-gray-700 dark:text-gray-300"
                    >
                      {{ ip }}
                    </span>
                  </div>
                  <button
                    @click="removeTrustedIp(index)"
                    class="p-2 text-gray-400 hover:text-red-500 transition-colors"
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

              <!-- Add Networks Actions -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <button
                  @click="addCurrentNetwork"
                  type="button"
                  class="flex items-center justify-center gap-2 px-4 py-3 bg-primary-50 dark:bg-primary-900/20 text-primary-700 dark:text-primary-300 font-bold rounded-xl border border-primary-100 dark:border-primary-800 hover:bg-primary-100 dark:hover:bg-primary-900/30 transition-all"
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
                      d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"
                    />
                  </svg>
                  Add Current Network
                </button>

                <div class="flex gap-2">
                  <input
                    v-model="manualIp"
                    type="text"
                    placeholder="Enter IP (e.g. 192.168.1.1)"
                    class="flex-1 px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white focus:ring-2 focus:ring-primary-500 outline-none transition-all font-mono text-sm"
                    @keyup.enter="manualIp && addTrustedIp(manualIp)"
                  />
                  <button
                    @click="manualIp && addTrustedIp(manualIp)"
                    :disabled="!manualIp"
                    class="px-4 py-3 bg-gray-900 dark:bg-white text-white dark:text-black rounded-xl font-bold hover:opacity-90 disabled:opacity-50 transition-opacity"
                  >
                    Add
                  </button>
                </div>
              </div>
            </div>
            <div v-else class="text-sm text-gray-400 italic">
              Enable to restrict Cash payments to specific Wi-Fi networks only.
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
            {{ t("settings.bakongConfig") }}
          </h3>
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="md:col-span-2">
              <BaseInput
                v-model="form.bakong_account_id"
                :label="t('settings.bakongId')"
                placeholder="your_account@bakong"
              />
            </div>
            <BaseInput
              v-model="form.merchant_name"
              :label="t('settings.merchantName')"
              placeholder="Lucky Café"
            />
            <BaseInput
              v-model="form.merchant_city"
              :label="t('settings.merchantCity')"
              placeholder="Phnom Penh"
            />
            <div class="md:col-span-2">
              <BaseInput
                v-model="form.bakong_telegram_chat_id"
                :label="t('settings.telegramChatId')"
                placeholder="Enter Telegram Chat ID for notifications"
              />
            </div>
          </div>
          <div
            class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-100 dark:border-blue-800 rounded-2xl flex gap-3"
          >
            <svg
              class="w-5 h-5 text-blue-600 dark:text-blue-400 shrink-0 mt-0.5"
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
            {{ saving ? t("common.loading") : t("common.save") }}
          </BaseButton>
        </div>
      </div>
    </BaseCard>
  </div>
</template>
