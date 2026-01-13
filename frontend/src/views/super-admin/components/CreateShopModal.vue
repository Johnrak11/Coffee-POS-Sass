<script setup lang="ts">
import { ref, reactive } from "vue";
import BaseModal from "@/components/ui/BaseModal.vue";
import BaseInput from "@/components/ui/BaseInput.vue";
import BaseButton from "@/components/ui/BaseButton.vue";
import apiClient from "@/services/api";
import { toast } from "vue-sonner";

const props = defineProps<{
  modelValue: boolean;
}>();

const emit = defineEmits(["update:modelValue", "created"]);

const form = reactive({
  name: "",
  slug: "",
  password: "",
  plan: "Basic",
  owner_email: "",
  owner_password: "",
});

const submitting = ref(false);

async function onSubmit() {
  if (
    !form.name ||
    !form.slug ||
    !form.password ||
    !form.owner_email ||
    !form.owner_password
  ) {
    toast.error("Please fill all required fields");
    return;
  }

  submitting.value = true;
  try {
    await apiClient.post("/super-admin/shops", form);
    toast.success("Shop created successfully");
    emit("created");
    emit("update:modelValue", false);
    // Reset
    form.name = "";
    form.slug = "";
    form.password = "";
    form.owner_email = "";
    form.owner_password = "";
  } catch (e: any) {
    toast.error(e.response?.data?.message || "Failed to create shop");
  } finally {
    submitting.value = false;
  }
}
</script>

<template>
  <BaseModal :isOpen="modelValue" @close="$emit('update:modelValue', false)" title="Onboard New Shop" maxWidth="2xl">
    <template #subtitle>
      <p class="text-gray-500 text-sm">Create a new tenant and owner account</p>
    </template>

    <div class="space-y-6">
      <!-- Shop Details -->
      <div class="grid grid-cols-2 gap-6">
        <div class="col-span-2">
          <h3 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-wider">
            Shop Details
          </h3>
        </div>
        <div>
          <BaseInput v-model="form.name" label="Shop Name" placeholder="e.g. My Coffee Shop" />
        </div>
        <div>
          <BaseInput v-model="form.slug" label="Shop Slug (ID)" placeholder="e.g. my-coffee-shop" />
        </div>
        <div>
          <BaseInput v-model="form.password" label="Terminal Password" placeholder="For finding shop..." />
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
      </div>

      <hr class="border-gray-100" />

      <!-- Owner Details -->
      <div class="grid grid-cols-2 gap-6">
        <div class="col-span-2">
          <h3 class="text-xs font-bold text-gray-400 uppercase mb-4 tracking-wider">
            Owner Account
          </h3>
        </div>
        <div class="col-span-2">
          <BaseInput v-model="form.owner_email" label="Owner Email" type="email" placeholder="owner@example.com" />
        </div>
        <div class="col-span-2">
          <BaseInput v-model="form.owner_password" label="Initial Password" placeholder="Set a temporary password" />
        </div>
      </div>
    </div>

    <template #footer>
      <BaseButton variant="ghost" @click="$emit('update:modelValue', false)">Cancel</BaseButton>
      <BaseButton :loading="submitting" @click="onSubmit">Create Shop</BaseButton>
    </template>
  </BaseModal>
</template>
