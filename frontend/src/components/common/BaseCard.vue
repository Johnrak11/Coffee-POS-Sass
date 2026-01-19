<script setup lang="ts">
interface CardProps {
  padding?: "none" | "sm" | "md" | "lg";
  shadow?: "none" | "sm" | "md" | "lg";
  rounded?: "none" | "md" | "lg" | "xl" | "2xl" | "3xl";
  hover?: boolean;
}

const props = withDefaults(defineProps<CardProps>(), {
  padding: "md",
  shadow: "sm",
  rounded: "2xl",
  hover: false,
});

const cardClasses = computed(() => {
  const base =
    "bg-white dark:bg-gray-800 border border-gray-100 dark:border-gray-700 transition-all duration-200";

  const paddings = {
    none: "",
    sm: "p-4",
    md: "p-6",
    lg: "p-8",
  };

  const shadows = {
    none: "",
    sm: "shadow-sm",
    md: "shadow-md",
    lg: "shadow-lg",
  };

  const roundings = {
    none: "",
    md: "rounded-md",
    lg: "rounded-lg",
    xl: "rounded-xl",
    "2xl": "rounded-2xl",
    "3xl": "rounded-3xl",
  };

  const hoverEffect = props.hover
    ? "hover:shadow-md hover:border-gray-200 dark:hover:border-gray-600"
    : "";

  return [
    base,
    paddings[props.padding],
    shadows[props.shadow],
    roundings[props.rounded],
    hoverEffect,
  ].join(" ");
});
</script>

<template>
  <div :class="cardClasses">
    <slot />
  </div>
</template>

<script lang="ts">
import { computed } from "vue";
export default {
  name: "BaseCard",
};
</script>
