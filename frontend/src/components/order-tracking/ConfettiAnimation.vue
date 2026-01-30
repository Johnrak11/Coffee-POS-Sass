<script setup lang="ts">
import { onMounted, ref } from "vue";

interface Confetti {
  id: number;
  left: number;
  delay: number;
  duration: number;
  color: string;
}

const confettiPieces = ref<Confetti[]>([]);

const colors = [
  "#10B981",
  "#3B82F6",
  "#F59E0B",
  "#EF4444",
  "#8B5CF6",
  "#EC4899",
];

onMounted(() => {
  // Generate 50 confetti pieces
  for (let i = 0; i < 50; i++) {
    confettiPieces.value.push({
      id: i,
      left: Math.random() * 100,
      delay: Math.random() * 0.5,
      duration: 2 + Math.random() * 1,
      color: colors[Math.floor(Math.random() * colors.length)] || "#10B981",
    });
  }
});
</script>

<template>
  <div class="confetti-container">
    <div
      v-for="piece in confettiPieces"
      :key="piece.id"
      class="confetti-piece"
      :style="{
        left: `${piece.left}%`,
        animationDelay: `${piece.delay}s`,
        animationDuration: `${piece.duration}s`,
        backgroundColor: piece.color,
      }"
    ></div>
  </div>
</template>

<style scoped>
.confetti-container {
  position: fixed;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  pointer-events: none;
  overflow: hidden;
  z-index: 100;
}

.confetti-piece {
  position: absolute;
  width: 10px;
  height: 10px;
  top: -20px;
  border-radius: 2px;
  animation: confetti-fall linear forwards;
}

@keyframes confetti-fall {
  to {
    transform: translateY(100vh) rotate(360deg);
    opacity: 0;
  }
}
</style>
