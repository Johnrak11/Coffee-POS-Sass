<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import { toast } from "vue-sonner";
import ImageUpload from "@/components/ImageUpload.vue";

const authStore = useAuthStore();
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

    // IMPORTANT: Update authStore.shop with the fetched data
    authStore.shop = response.data;

    console.log("Settings loaded and shop updated:", authStore.shop);
  } catch (e) {
    console.error(e);
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
    // Upload logo if changed
    if (pendingLogoFile.value) {
      const logoUrl = await uploadToCloudinary(pendingLogoFile.value);
      form.value.logo_url = logoUrl;
    }

    await apiClient.put(`/staff/admin/${shopSlug}/menu/settings`, form.value);
    // Update local store if name changed
    if (authStore.shop) {
      Object.assign(authStore.shop, form.value);
    }

    // Force refresh the settings to apply theme immediately
    await fetchSettings();
    pendingLogoFile.value = null;

    toast.success("Settings saved successfully!");
  } catch (e) {
    console.error(e);
    toast.error("Failed to update settings");
  } finally {
    saving.value = false;
  }
}
</script>

<template>
  <div class="p-8 max-w-4xl">
    <div class="mb-8">
      <h1 class="text-3xl font-bold text-gray-900">Shop Settings</h1>
      <p class="text-gray-500">
        Configure your shop profile and receipt preferences.
      </p>
    </div>

    <div v-if="loading" class="animate-pulse space-y-6">
      <div class="h-64 bg-white rounded-3xl border border-gray-100"></div>
    </div>

    <div
      v-else
      class="bg-white rounded-[32px] border border-gray-100 shadow-sm p-8 space-y-8"
    >
      <!-- General Section -->
      <div>
        <h3
          class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2"
        >
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          General Profile
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Shop Name</label
            >
            <input
              v-model="form.name"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Phone Number</label
            >
            <input
              v-model="form.phone"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Exchange Rate (1 USD = ? KHR)</label
            >
            <div class="relative">
              <input
                v-model="form.exchange_rate"
                type="number"
                min="0"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
                placeholder="4100"
              />
              <div
                class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none text-gray-500 font-bold"
              >
                ៛
              </div>
            </div>
            <p class="text-xs text-gray-400 mt-2">
              Used for cash payments in Khmer Riel.
            </p>
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Address</label
            >
            <textarea
              v-model="form.address"
              rows="2"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
            ></textarea>
          </div>
        </div>
      </div>

      <!-- Receipt Section -->
      <div class="pt-8 border-t border-gray-50">
        <h3
          class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2"
        >
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          Receipt Customization
        </h3>
        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
            >Footer Message</label
          >
          <textarea
            v-model="form.receipt_footer"
            rows="2"
            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
            placeholder="Thank you for visiting!"
          ></textarea>
        </div>
      </div>

      <!-- Branding & Theme -->
      <div class="pt-8 border-t border-gray-50">
        <h3
          class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2"
        >
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          Branding & Theme
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Theme Mode -->
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Theme Mode</label
            >
            <div class="flex gap-3">
              <button
                type="button"
                @click="form.theme_mode = 'light'"
                :class="[
                  'flex-1 py-3 px-4 rounded-xl font-semibold transition-all',
                  form.theme_mode === 'light'
                    ? 'bg-orange-600 text-white shadow-lg'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                ]"
              >
                ☀️ Light
              </button>
              <button
                type="button"
                @click="form.theme_mode = 'dark'"
                :class="[
                  'flex-1 py-3 px-4 rounded-xl font-semibold transition-all',
                  form.theme_mode === 'dark'
                    ? 'bg-gray-900 text-white shadow-lg'
                    : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
                ]"
              >
                🌙 Dark
              </button>
            </div>
          </div>
          <!-- Logo Avatar -->
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Shop Logo</label
            >
            <ImageUpload
              v-model="form.logo_url"
              folder="logos"
              @fileSelected="pendingLogoFile = $event"
            />
          </div>
        </div>
      </div>

      <!-- Payment Configuration Section -->
      <div class="pt-8 border-t border-gray-50">
        <h3
          class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2"
        >
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          Bakong Payment Configuration
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Bakong Account ID</label
            >
            <input
              v-model="form.bakong_account_id"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="your_account@bakong"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Merchant Name</label
            >
            <input
              v-model="form.merchant_name"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="Lucky Café"
            />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2"
              >Merchant City</label
            >
            <input
              v-model="form.merchant_city"
              type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="Phnom Penh"
            />
          </div>
        </div>
        <div
          class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl flex gap-3"
        >
          <svg
            class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"
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
          <p class="text-sm text-blue-700">
            These details will appear on guest payment QR codes for Bakong
            transfers.
          </p>
        </div>
      </div>

      <div class="pt-8 flex justify-end">
        <button
          @click="saveSettings"
          :disabled="saving"
          class="px-10 py-4 bg-orange-600 rounded-2xl text-white font-bold shadow-lg shadow-orange-100 hover:bg-orange-500 transition-all disabled:opacity-50 active:scale-95"
        >
          {{ saving ? "Saving Changes..." : "Save Settings" }}
        </button>
      </div>
    </div>
  </div>
</template>
