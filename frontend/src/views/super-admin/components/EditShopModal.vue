<script setup lang="ts">
import { ref, watch } from "vue";
// BaseModal removed - needs to be recreated in @/components/common
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
  bakong_account_id: "",
  merchant_name: "",
  new_terminal_password: "",
});

const submitting = ref(false);

watch(
  () => props.shop,
  (newShop) => {
    if (newShop) {
      form.value = {
        id: newShop.id,
        name: newShop.name,
        plan: newShop.plan,
        bakong_account_id: newShop.bakong_account_id || "",
        merchant_name: newShop.merchant_name || "",
        new_terminal_password: "",
      };
    }
  },
  { immediate: true }
);

async function onSubmit() {
  submitting.value = true;
  try {
    await apiClient.put(
      `/super-admin/shops/${form.value.id}`,
      {
        name: form.value.name,
        plan: form.value.plan,
        bakong_account_id: form.value.bakong_account_id,
        merchant_name: form.value.merchant_name,
      }
    );

    if (form.value.new_terminal_password) {
      await apiClient.put(
        `/super-admin/shops/${form.value.id}/reset-password`,
        {
          password: form.value.new_terminal_password,
        }
      );
    }

    toast.success("Shop updated successfully");
    emit("updated");
    emit("update:modelValue", false);
  } catch (e: any) {
    toast.error(e.response?.data?.message || "Failed to update shop");
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <BaseModal :isOpen="modelValue" @close="$emit('update:modelValue', false)" title="Manage Shop" maxWidth="2xl">
    <template #subtitle>
      <p class="text-gray-500 text-sm">Update settings and credentials</p>
    </template>

    <div class="space-y-6">
      <div>
        <BaseInput v-model="form.name" label="Shop Name" />
      </div>
      <div>
        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Plan</label>
        <select v-model="form.plan"
          class="w-full bg-white border border-gray-200 rounded-xl px-4 py-3 font-medium outline-none focus:ring-2 focus:ring-gray-100">
          <option value="Basic">Basic Plan</option>
          <option value="Pro">Pro Plan</option>
          <option value="Enterprise">Enterprise</option>
        </select>
      </div>

      <hr class="border-gray-100" />

      <!-- Bakong -->
      <h3 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-wider">
        Bakong Configuration
      </h3>
      <div class="grid grid-cols-2 gap-6">
        <div>
          <BaseInput v-model="form.merchant_name" label="Merchant Name" placeholder="e.g. Lucky Cafe KH" />
        </div>
        <div>
          <BaseInput v-model="form.bakong_account_id" label="Bakong Account ID" placeholder="e.g. lucky_cafe@bakong" />
        </div>
      </div>

      <hr class="border-gray-100" />

      <!-- Security Zone -->
      <h3 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-wider text-red-400">
        Security Zone
      </h3>
      <div>
        <BaseInput v-model="form.new_terminal_password" label="Reset Terminal Password"
          placeholder="Type new password to reset..."
          class="border-red-100 focus:border-red-500 placeholder-red-300 text-red-600" />
        <p class="text-xs text-red-400 mt-2">
          Leave empty to keep current password.
        </p>
      </div>
    </div>

    <template #footer>
      <BaseButton variant="ghost" @click="$emit('update:modelValue', false)">Cancel</BaseButton>
      <BaseButton :loading="submitting" @click="onSubmit">Save Changes</BaseButton>
    </template>
  </BaseModal>
</template>
