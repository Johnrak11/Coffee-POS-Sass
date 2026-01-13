<script setup lang="ts">
import { computed } from "vue";

const props = defineProps<{
  variant?: "primary" | "secondary" | "outline" | "ghost" | "danger";
  size?: "sm" | "md" | "lg";
  loading?: boolean;
  disabled?: boolean;
  block?: boolean;
  icon?: string;
}>();

const baseClasses =
  "inline-flex items-center justify-center font-bold rounded-xl transition-all duration-200 active:scale-[0.98] disabled:opacity-50 disabled:cursor-not-allowed gap-2";

const variants = {
  primary:
    "bg-gray-900 text-white hover:bg-gray-800 shadow-lg shadow-gray-900/20",
  secondary:
    "bg-orange-600 text-white hover:bg-orange-700 shadow-lg shadow-orange-600/20",
  outline:
    "border-2 border-gray-200 text-gray-700 hover:border-gray-900 hover:text-gray-900 bg-transparent",
  ghost: "bg-transparent text-gray-500 hover:bg-gray-100 hover:text-gray-900",
  danger: "bg-red-50 text-red-600 hover:bg-red-100 border border-red-100",
};

const sizes = {
  sm: "px-3 py-1.5 text-sm",
  md: "px-5 py-3 text-base",
  lg: "px-8 py-4 text-lg",
};

const classes = computed(() => {
  return [
    baseClasses,
    variants[props.variant || "primary"],
    sizes[props.size || "md"],
    props.block ? "w-full" : "",
  ].join(" ");
});
</script>

<template>
  <button :class="classes" :disabled="disabled || loading">
    <span v-if="loading" class="animate-spin mr-1">↻</span>
    <slot name="prefix"></slot>
    <slot></slot>
    <slot name="suffix"></slot>
  </button>
</template>
