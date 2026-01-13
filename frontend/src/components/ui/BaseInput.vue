<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  modelValue: string | number;
  label?: string;
  type?: string;
  placeholder?: string;
  error?: string;
  icon?: boolean;
}>();

const emit = defineEmits(["update:modelValue", "enter"]);

const inputClasses = computed(() => {
  return [
    "w-full bg-white border rounded-xl px-4 py-3 font-medium outline-none transition-all duration-200",
    props.error
      ? "border-red-300 text-red-900 focus:ring-2 focus:ring-red-100 placeholder-red-300"
      : "border-gray-200 text-gray-900 focus:border-gray-900 focus:ring-2 focus:ring-gray-100 placeholder-gray-400",
  ].join(" ");
});
</script>

<template>
  <div class="w-full">
    <label
      v-if="label"
      class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2"
    >
      {{ label }}
    </label>
    <div class="relative">
      <input
        :value="modelValue"
        :type="type || 'text'"
        :placeholder="placeholder"
        @input="
          $emit('update:modelValue', ($event.target as HTMLInputElement).value)
        "
        @keyup.enter="$emit('enter')"
        :class="inputClasses"
      />
      <div
        v-if="$slots.suffix"
        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400"
      >
        <slot name="suffix"></slot>
      </div>
    </div>
    <p
      v-if="error"
      class="text-xs text-red-500 mt-1 font-medium flex items-center gap-1"
    >
      <span class="text-lg leading-none">•</span> {{ error }}
    </p>
  </div>
</template>
