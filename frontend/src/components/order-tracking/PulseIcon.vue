<script setup lang="ts">
import { computed } from "vue";

interface Props {
  color?: string;
  size?: "sm" | "md" | "lg";
  speed?: "slow" | "normal" | "fast";
}

const props = withDefaults(defineProps<Props>(), {
  color: "#FFA500",
  size: "md",
  speed: "normal",
});

const sizeClasses = computed(() => {
  switch (props.size) {
    case "sm":
      return "w-8 h-8";
    case "lg":
      return "w-20 h-20";
    default:
      return "w-12 h-12";
  }
});

const animationClass = computed(() => {
  switch (props.speed) {
    case "slow":
      return "animate-pulse-slow";
    case "fast":
      return "animate-pulse-fast";
    default:
      return "animate-pulse";
  }
});
</script>

<template>
  <div
    :class="[sizeClasses, animationClass]"
    class="rounded-full flex items-center justify-center"
    :style="{ backgroundColor: color }"
  >
    <slot />
  </div>
</template>

<style scoped>
@keyframes pulse-slow {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.7;
    transform: scale(1.05);
  }
}

@keyframes pulse-fast {
  0%,
  100% {
    opacity: 1;
    transform: scale(1);
  }
  50% {
    opacity: 0.6;
    transform: scale(1.1);
  }
}

.animate-pulse-slow {
  animation: pulse-slow 3s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

.animate-pulse-fast {
  animation: pulse-fast 1s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}
</style>
