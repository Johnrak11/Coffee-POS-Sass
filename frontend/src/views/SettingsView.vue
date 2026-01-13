<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import { toast } from "vue-sonner";

const authStore = useAuthStore();
const loading = ref(true);
const saving = ref(false);

const form = ref({
  name: "",
  logo_url: "",
  address: "",
  phone: "",
  receipt_footer: "",
  currency_symbol: "$",
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

async function saveSettings() {
  saving.value = true;
  const shopSlug = authStore.shop?.slug || "lucky-cafe";
  try {
    await apiClient.put(
      `/staff/admin/${shopSlug}/menu/settings`,
      form.value
    );
    // Update local store if name changed
    if (authStore.shop) {
      authStore.shop.name = form.value.name;
      authStore.shop.primary_color = form.value.primary_color;
      authStore.shop.theme_mode = form.value.theme_mode;
    }

    // Force refresh the settings to apply theme immediately
    await fetchSettings();

    toast.success("Settings saved successfully!");
  } catch (e) {
    console.error(e);
    toast.error("Failed to update settings");
  } finally {
    saving.value = false;
  }
}

function handleLogoUpload(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];

  if (!file) return;

  // Check file size (max 2MB)
  if (file.size > 2 * 1024 * 1024) {
    toast.error("Logo file is too large. Maximum size is 2MB.");
    return;
  }

  // Check file type
  if (!file.type.startsWith("image/")) {
    toast.error("Please upload an image file.");
    return;
  }

  // Convert to base64
  const reader = new FileReader();
  reader.onload = (e) => {
    form.value.logo_url = e.target?.result as string;
    toast.success("Logo uploaded! Don't forget to save.");
  };
  reader.onerror = () => {
    toast.error("Failed to read file.");
  };
  reader.readAsDataURL(file);
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

    <div v-else class="bg-white rounded-[32px] border border-gray-100 shadow-sm p-8 space-y-8">
      <!-- General Section -->
      <div>
        <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          General Profile
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Shop Name</label>
            <input v-model="form.name" type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Phone Number</label>
            <input v-model="form.phone" type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Currency</label>
            <div class="relative">
              <select v-model="form.currency_symbol"
                class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none appearance-none bg-white font-medium">
                <option value="$">USD ($)</option>
                <option value="៛">Khmer Riel (៛)</option>
              </select>
              <div class="absolute inset-y-0 right-0 flex items-center px-4 pointer-events-none">
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </div>
            </div>
          </div>
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Address</label>
            <textarea v-model="form.address" rows="2"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"></textarea>
          </div>
        </div>
      </div>

      <!-- Receipt Section -->
      <div class="pt-8 border-t border-gray-50">
        <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          Receipt Customization
        </h3>
        <div>
          <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Footer Message</label>
          <textarea v-model="form.receipt_footer" rows="2"
            class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
            placeholder="Thank you for visiting!"></textarea>
        </div>
      </div>

      <!-- Branding & Theme -->
      <div class="pt-8 border-t border-gray-50">
        <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          Branding & Theme
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <!-- Theme Mode -->
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Theme Mode</label>
            <div class="flex gap-3">
              <button type="button" @click="form.theme_mode = 'light'" :class="[
                'flex-1 py-3 px-4 rounded-xl font-semibold transition-all',
                form.theme_mode === 'light'
                  ? 'bg-orange-600 text-white shadow-lg'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
              ]">
                ☀️ Light
              </button>
              <button type="button" @click="form.theme_mode = 'dark'" :class="[
                'flex-1 py-3 px-4 rounded-xl font-semibold transition-all',
                form.theme_mode === 'dark'
                  ? 'bg-gray-900 text-white shadow-lg'
                  : 'bg-gray-100 text-gray-600 hover:bg-gray-200',
              ]">
                🌙 Dark
              </button>
            </div>
          </div>
          <!-- Logo Avatar -->
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Shop Logo</label>
            <div class="flex items-center gap-4">
              <div v-if="form.logo_url"
                class="w-16 h-16 rounded-full bg-white border-4 border-gray-200 overflow-hidden flex-shrink-0 shadow-md">
                <img :src="form.logo_url" alt="Logo" class="w-full h-full object-cover" />
              </div>
              <div v-else
                class="w-16 h-16 rounded-full bg-gray-100 border-4 border-gray-200 flex items-center justify-center flex-shrink-0">
                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                </svg>
              </div>
              <input type="file" accept="image/*" @change="handleLogoUpload" class="hidden" id="logo-upload" />
              <label for="logo-upload"
                class="flex-1 px-4 py-3 rounded-xl border border-gray-200 hover:border-orange-500 transition-colors cursor-pointer flex items-center justify-center gap-2 bg-white text-gray-700 font-medium">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                </svg>
                {{ form.logo_url ? "Change Logo" : "Upload Logo" }}
              </label>
            </div>
            <p class="text-xs text-gray-400 mt-2">PNG or JPG • Max 2MB</p>
          </div>
        </div>
      </div>

      <!-- Payment Configuration Section -->
      <div class="pt-8 border-t border-gray-50">
        <h3 class="text-lg font-bold text-gray-900 mb-6 flex items-center gap-2">
          <span class="w-1.5 h-6 bg-orange-600 rounded-full"></span>
          Bakong Payment Configuration
        </h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div class="md:col-span-2">
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Bakong Account ID</label>
            <input v-model="form.bakong_account_id" type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="your_account@bakong" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Merchant Name</label>
            <input v-model="form.merchant_name" type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="Lucky Café" />
          </div>
          <div>
            <label class="block text-xs font-bold text-gray-400 uppercase mb-2">Merchant City</label>
            <input v-model="form.merchant_city" type="text"
              class="w-full px-4 py-3 rounded-xl border border-gray-200 focus:ring-2 focus:ring-orange-500 outline-none"
              placeholder="Phnom Penh" />
          </div>
        </div>
        <div class="mt-4 p-4 bg-blue-50 border border-blue-100 rounded-2xl flex gap-3">
          <svg class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
          </svg>
          <p class="text-sm text-blue-700">
            These details will appear on guest payment QR codes for Bakong
            transfers.
          </p>
        </div>
      </div>

      <div class="pt-8 flex justify-end">
        <button @click="saveSettings" :disabled="saving"
          class="px-10 py-4 bg-orange-600 rounded-2xl text-white font-bold shadow-lg shadow-orange-100 hover:bg-orange-500 transition-all disabled:opacity-50 active:scale-95">
          {{ saving ? "Saving Changes..." : "Save Settings" }}
        </button>
      </div>
    </div>
  </div>
</template>
