<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";

interface Props {
  currentStage: "queue" | "preparing" | "served";
}

const props = defineProps<Props>();
const { t } = useI18n();

const stages = computed(() => [
  { id: "queue", label: t("tracking.inQueue"), icon: "📋" },
  { id: "preparing", label: t("tracking.currentlyPreparing"), icon: "👨‍🍳" },
  { id: "served", label: t("tracking.readyForPickup"), icon: "🎉" },
]);

const currentIndex = computed(() => {
  return stages.value.findIndex((s) => s.id === props.currentStage);
});

function isCompleted(index: number) {
  return index <= currentIndex.value;
}

function isCurrent(index: number) {
  return index === currentIndex.value;
}
</script>

<template>
  <div class="status-timeline">
    <div v-for="(stage, index) in stages" :key="stage.id" class="timeline-step">
      <div class="timeline-content">
        <div
          :class="[
            'timeline-icon',
            {
              'bg-green-500 text-white': isCompleted(index),
              'bg-primary-500 text-white animate-pulse': isCurrent(index),
              'bg-gray-200 text-gray-400':
                !isCompleted(index) && !isCurrent(index),
            },
          ]"
        >
          <span class="text-2xl">{{ stage.icon }}</span>
        </div>
        <div
          :class="[
            'timeline-label',
            {
              'text-green-600 font-bold': isCompleted(index),
              'text-primary-600 font-bold': isCurrent(index),
              'text-gray-400': !isCompleted(index) && !isCurrent(index),
            },
          ]"
        >
          {{ stage.label }}
        </div>
      </div>
      <div
        v-if="index < stages.length - 1"
        :class="[
          'timeline-line',
          {
            'bg-green-500': isCompleted(index),
            'bg-gray-200': !isCompleted(index),
          },
        ]"
      ></div>
    </div>
  </div>
</template>

<style scoped>
.status-timeline {
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 1rem;
  overflow-x: auto;
}

.timeline-step {
  display: flex;
  flex-direction: column;
  align-items: center;
  position: relative;
  flex: 1;
}

.timeline-content {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 0.5rem;
  z-index: 10;
}

.timeline-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all 0.3s ease;
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.timeline-label {
  font-size: 0.75rem;
  text-align: center;
  white-space: nowrap;
  transition: all 0.3s ease;
}

.timeline-line {
  position: absolute;
  top: 28px;
  left: 50%;
  right: -50%;
  height: 4px;
  transition: all 0.3s ease;
  z-index: 1;
}

.timeline-step:last-child .timeline-line {
  display: none;
}
</style>
