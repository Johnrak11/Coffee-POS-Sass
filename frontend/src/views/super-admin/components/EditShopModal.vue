<script setup lang="ts">
import { ref, watch } from "vue";
import { BaseInput, BaseButton } from "@/components/common";
import apiClient from "@/api";
import { toast } from "vue-sonner";

const props = defineProps<{
  modelValue: boolean;
  shop: any;
}>();

const emit = defineEmits(["update:modelValue", "updated"]);

const form = ref({
  id: 0,
  name: "",
  plan: "Basic",
  // Contact
  address: "",
  phone: "",
  // Branding
  logo_url: "",
  primary_color: "",
  currency_symbol: "$",
  theme_mode: "dark",
  receipt_footer: "",
  // Bakong
  bakong_wallet_id: "",
  bakong_account_id: "",
  merchant_name: "",
  merchant_city: "",
  // Security / Owner
  new_terminal_password: "",
  owner_email: "",
  owner_password: "",
});

const submitting = ref(false);

watch(
  () => props.shop,
  (newShop) => {
    if (newShop) {
      form.value = {
        id: newShop.id,
        name: newShop.name,
        plan: newShop.plan || "Basic",

        address: newShop.address || "",
        phone: newShop.phone || "",

        logo_url: newShop.logo_url || "",
        primary_color: newShop.primary_color || "",
        currency_symbol: newShop.currency_symbol || "$",
        theme_mode: newShop.theme_mode || "dark",
        receipt_footer: newShop.receipt_footer || "",

        bakong_wallet_id: newShop.bakong_wallet_id || "",
        bakong_account_id: newShop.bakong_account_id || "",
        merchant_name: newShop.merchant_name || "",
        merchant_city: newShop.merchant_city || "",

        new_terminal_password: "",
        owner_email: "", // Don't prefill for security/simplicity unless we fetch owner specifically
        owner_password: "",
      };
    }
  },
  { immediate: true },
);

async function onSubmit() {
  submitting.value = true;
  try {
    // 1. Update Shop Details (including owner if fields filled)
    await apiClient.put(`/super-admin/shops/${form.value.id}`, {
      name: form.value.name,
      plan: form.value.plan,

      address: form.value.address,
      phone: form.value.phone,

      logo_url: form.value.logo_url,
      primary_color: form.value.primary_color,
      currency_symbol: form.value.currency_symbol,
      theme_mode: form.value.theme_mode,
      receipt_footer: form.value.receipt_footer,

      bakong_wallet_id: form.value.bakong_wallet_id,
      bakong_account_id: form.value.bakong_account_id,
      merchant_name: form.value.merchant_name,
      merchant_city: form.value.merchant_city,

      // Owner Updates (handled by backend if present)
      owner_email: form.value.owner_email || undefined,
      owner_password: form.value.owner_password || undefined,
    });

    // 2. Reset Terminal Password if requested
    if (form.value.new_terminal_password) {
      await apiClient.put(
        `/super-admin/shops/${form.value.id}/reset-password`,
        {
          password: form.value.new_terminal_password,
        },
      );
    }

    toast.success("Shop updated successfully");
    emit("updated");
    emit("update:modelValue", false);
  } catch (e: any) {
    console.error(e);
    toast.error(e.response?.data?.message || "Failed to update shop");
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <BaseModal
    :isOpen="modelValue"
    @close="$emit('update:modelValue', false)"
    title="Manage Shop"
    maxWidth="4xl"
  >
    <template #subtitle>
      <p class="text-gray-500 text-sm">
        Full control over shop settings and credentials
      </p>
    </template>

    <div class="space-y-8 max-h-[70vh] overflow-y-auto px-1">
      <!-- General Info -->
      <div class="grid grid-cols-2 gap-6">
        <div>
          <BaseInput v-model="form.name" label="Shop Name" />
        </div>
        <div>
          <label
            class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"
            >Plan</label
          >
          <select
            v-model="form.plan"
            class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 font-medium outline-none focus:ring-2 focus:ring-gray-100"
          >
            <option value="Basic">Basic Plan</option>
            <option value="Pro">Pro Plan</option>
            <option value="Enterprise">Enterprise</option>
          </select>
        </div>
      </div>

      <hr class="border-gray-100" />

      <!-- Branding & Localization -->
      <div>
        <h3
          class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-wider"
        >
          Branding & Logic
        </h3>
        <div class="grid grid-cols-3 gap-6">
          <div class="col-span-1">
            <BaseInput
              v-model="form.logo_url"
              label="Logo URL"
              placeholder="https://..."
            />
          </div>
          <div class="col-span-1">
            <BaseInput
              v-model="form.primary_color"
              label="Primary Color (Hex)"
              placeholder="#000000"
            />
          </div>
          <div class="col-span-1">
            <BaseInput
              v-model="form.currency_symbol"
              label="Currency Symbol"
              placeholder="$"
            />
          </div>
          <div class="col-span-3">
            <BaseInput
              v-model="form.receipt_footer"
              label="Receipt Footer Message"
              placeholder="Thank you for coming!"
            />
          </div>
          <div class="col-span-1">
            <label
              class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"
              >Theme Mode</label
            >
            <select
              v-model="form.theme_mode"
              class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 font-medium outline-none focus:ring-2 focus:ring-gray-100"
            >
              <option value="light">Light Mode</option>
              <option value="dark">Dark Mode</option>
            </select>
          </div>
        </div>
      </div>

      <hr class="border-gray-100" />

      <!-- Contact Info -->
      <div>
        <h3
          class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-wider"
        >
          Contact Details
        </h3>
        <div class="grid grid-cols-2 gap-6">
          <BaseInput
            v-model="form.phone"
            label="Phone Number"
            placeholder="+855..."
          />
          <BaseInput
            v-model="form.address"
            label="Address"
            placeholder="Street 123..."
          />
        </div>
      </div>

      <hr class="border-gray-100" />

      <!-- Bakong / KHQR -->
      <div>
        <h3
          class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-wider"
        >
          Bakong / KHQR Config
        </h3>
        <div class="grid grid-cols-2 gap-6">
          <BaseInput
            v-model="form.merchant_name"
            label="Merchant Name"
            placeholder="Lucky Cafe KH"
          />
          <BaseInput
            v-model="form.merchant_city"
            label="Merchant City"
            placeholder="Phnom Penh"
          />
          <BaseInput
            v-model="form.bakong_wallet_id"
            label="Bakong Wallet ID"
            placeholder="..."
          />
          <BaseInput
            v-model="form.bakong_account_id"
            label="Bakong Account ID"
            placeholder="..."
          />
        </div>
      </div>

      <hr class="border-gray-100" />

      <!-- Advanced Owner Management -->
      <div class="bg-orange-50 p-6 rounded-2xl border border-orange-100">
        <h3
          class="text-xs font-bold text-orange-500 uppercase mb-4 tracking-wider flex items-center gap-2"
        >
          ⚠️ Owner Management
        </h3>
        <p class="text-sm text-orange-600 mb-4">
          You can forcibly reset the owner's email or password here. Leave blank
          to keep unchanged.
        </p>
        <div class="grid grid-cols-2 gap-6">
          <BaseInput
            v-model="form.owner_email"
            label="Update Owner Email"
            placeholder="New email..."
          />
          <BaseInput
            v-model="form.owner_password"
            label="Reset Owner Password"
            placeholder="New password..."
            type="password"
          />
        </div>
      </div>

      <!-- Security Zone -->
      <div class="bg-red-50 p-6 rounded-2xl border border-red-100">
        <h3
          class="text-xs font-bold text-red-500 uppercase mb-4 tracking-wider"
        >
          🔒 Security Zone
        </h3>
        <div>
          <BaseInput
            v-model="form.new_terminal_password"
            label="Reset Terminal (Shop) Password"
            placeholder="Type new password to reset..."
            class="border-red-100 focus:border-red-500 placeholder-red-300 text-red-600"
          />
        </div>
      </div>
    </div>

    <template #footer>
      <BaseButton variant="ghost" @click="$emit('update:modelValue', false)"
        >Cancel</BaseButton
      >
      <BaseButton :loading="submitting" @click="onSubmit"
        >Save Changes</BaseButton
      >
    </template>
  </BaseModal>
</template>
