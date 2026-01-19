<script setup lang="ts">
import { computed } from "vue";

interface InputProps {
  modelValue: string | number;
  type?: "text" | "number" | "email" | "password" | "tel" | "url";
  placeholder?: string;
  label?: string;
  error?: string;
  required?: boolean;
  disabled?: boolean;
  size?: "sm" | "md" | "lg";
  fullWidth?: boolean;
}

const props = withDefaults(defineProps<InputProps>(), {
  type: "text",
  size: "md",
  fullWidth: true,
  required: false,
  disabled: false,
});

const emit = defineEmits<{
  "update:modelValue": [value: string | number];
}>();

const inputClasses = computed(() => {
  const base =
    "block rounded-lg border transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-0 disabled:opacity-50 disabled:cursor-not-allowed";

  const state = props.error
    ? "border-error-300 focus:border-error-500 focus:ring-error-500 dark:border-error-700"
    : "border-gray-300 focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600";

  const bg = "bg-white dark:bg-gray-800 text-gray-900 dark:text-gray-100";

  const sizes = {
    sm: "px-3 py-1.5 text-sm h-8",
    md: "px-4 py-2 text-base h-10",
    lg: "px-5 py-3 text-lg h-12",
  };

  const width = props.fullWidth ? "w-full" : "";

  return [base, state, bg, sizes[props.size], width].join(" ");
});

function handleInput(event: Event) {
  const target = event.target as HTMLInputElement;
  const value = props.type === "number" ? Number(target.value) : target.value;
  emit("update:modelValue", value);
}
</script>

<template>
  <div class="input-wrapper" :class="{ 'w-full': fullWidth }">
    <!-- Label -->
    <label
      v-if="label"
      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1.5"
    >
      {{ label }}
      <span v-if="required" class="text-error-500">*</span>
    </label>

    <!-- Input Field -->
    <input
      :type="type"
      :value="modelValue"
      :placeholder="placeholder"
      :required="required"
      :disabled="disabled"
      :class="inputClasses"
      @input="handleInput"
    />

    <!-- Error Message -->
    <p v-if="error" class="mt-1.5 text-sm text-error-600 dark:text-error-400">
      {{ error }}
    </p>
  </div>
</template>
