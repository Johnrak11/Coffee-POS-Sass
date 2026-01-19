<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import { useKitchenStore } from "@/stores/kitchen";
import { BaseButton } from "@/components/common";

const kitchenStore = useKitchenStore();
const loading = ref(true);
let pollInterval: any = null;

const notificationSound = new Audio(
  "https://assets.mixkit.co/active_storage/sfx/2869/2869-preview.mp3"
);

watch(
  () => kitchenStore.orders.length,
  (newCount, oldCount) => {
    if (newCount > oldCount && !loading.value) {
      notificationSound
        .play()
        .catch((e) => console.warn("Audio play blocked:", e));
    }
  }
);

// Real-time polling (every 5 seconds)
onMounted(async () => {
  await fetchData();
  loading.value = false;

  pollInterval = setInterval(fetchData, 5000);
});

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval);
});

async function fetchData() {
  await kitchenStore.fetchOrders("lucky-cafe");
}

function getStatusColor(status: string) {
  switch (status) {
    case "queue":
      return "bg-primary-500/20 dark:bg-primary-900/30 text-primary-400 border-primary-500/50 dark:border-primary-800";
    case "preparing":
      return "bg-blue-500/10 dark:bg-blue-900/30 text-blue-500 dark:text-blue-400 border-blue-200 dark:border-blue-900";
    default:
      return "bg-gray-100 dark:bg-gray-800 text-gray-500 dark:text-gray-400 border-gray-200 dark:border-gray-700";
  }
}

function getTimeElapsed(createdAt: string) {
  const start = new Date(createdAt).getTime();
  const now = new Date().getTime();
  const diff = Math.floor((now - start) / 1000);

  const mins = Math.floor(diff / 60);
  const secs = diff % 60;

  return `${mins}m ${secs}s`;
}

function getTimerColor(createdAt: string) {
  const start = new Date(createdAt).getTime();
  const now = new Date().getTime();
  const mins = (now - start) / 60000;

  if (mins > 10) return "text-red-500 dark:text-red-400 animate-pulse";
  if (mins > 5) return "text-yellow-500 dark:text-yellow-400";
  return "text-success-500 dark:text-success-400";
}

async function handleNextStatus(order: any) {
  const nextStatus =
    order.fulfillment_status === "queue" ? "preparing" : "served";
  await kitchenStore.updateStatus(order.id, nextStatus);
}
</script>

<template>
  <div
    class="h-full p-6 overflow-x-auto flex gap-6 items-start bg-bg-secondary dark:bg-gray-900"
  >
    <div
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center bg-app-bg/50 dark:bg-gray-900/50 backdrop-blur-sm z-50"
    >
      <div
        class="w-12 h-12 border-4 border-primary-500 dark:border-primary-600 border-t-transparent rounded-full animate-spin"
      ></div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!loading && kitchenStore.orders.length === 0"
      class="flex-1 flex flex-col items-center justify-center opacity-30 mt-20"
    >
      <svg
        class="w-32 h-32 mb-4 text-app-muted dark:text-gray-700"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="1"
          d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"
        ></path>
      </svg>
      <h2 class="text-3xl font-bold text-app-text dark:text-white">
        Kitchen is clear!
      </h2>
      <p class="text-app-muted dark:text-gray-400">
        New orders will appear here automatically.
      </p>
    </div>

    <!-- Order Grid -->
    <TransitionGroup
      name="list"
      tag="div"
      class="flex gap-6 items-start h-full"
    >
      <div
        v-for="order in kitchenStore.orders"
        :key="order.id"
        class="w-80 bg-app-surface dark:bg-gray-800 rounded-2xl shadow-xl flex flex-col max-h-full border border-app-border dark:border-gray-700 overflow-hidden transition-colors duration-300"
      >
        <!-- Card Header -->
        <div
          class="p-4 border-b border-app-border dark:border-gray-700 flex justify-between items-start"
        >
          <div>
            <div class="text-sm text-app-muted dark:text-gray-400 font-mono">
              {{ order.order_number }}
            </div>
            <div class="text-xl font-bold text-app-text dark:text-white">
              {{ order.shop_table_name }}
            </div>
          </div>
          <div
            :class="[
              'px-2 py-1 rounded text-[10px] font-bold uppercase border',
              getStatusColor(order.fulfillment_status),
            ]"
          >
            {{ order.fulfillment_status }}
          </div>
        </div>

        <!-- Timer Bar -->
        <div
          class="bg-app-bg dark:bg-gray-900 px-4 py-2 flex justify-between items-center text-sm border-b border-app-border dark:border-gray-700"
        >
          <span class="text-app-muted dark:text-gray-400">Wait Time:</span>
          <span
            :class="['font-mono font-bold', getTimerColor(order.created_at)]"
          >
            {{ getTimeElapsed(order.created_at) }}
          </span>
        </div>

        <!-- Items List -->
        <div class="flex-1 overflow-y-auto p-4 space-y-4">
          <div v-if="order.items && order.items.length > 0" class="space-y-3">
            <div v-for="item in order.items" :key="item.id" class="flex gap-3">
              <div
                class="w-8 h-8 rounded bg-app-bg dark:bg-gray-900 flex items-center justify-center font-bold text-primary-600 dark:text-primary-400 border border-app-border dark:border-gray-700"
              >
                {{ item.quantity }}
              </div>
              <div class="flex-1">
                <div class="font-bold text-app-text dark:text-white">
                  {{ item.product?.name }}
                </div>
                <div
                  v-if="item.variant"
                  class="text-xs text-app-muted dark:text-gray-400 italic"
                >
                  {{ item.variant.name }}: {{ item.variant.option_name }}
                </div>
                <!-- Display Options -->
                <div
                  v-if="item.options && item.options.length > 0"
                  class="text-xs text-primary-600 dark:text-primary-400 mt-1 space-y-0.5 font-medium"
                >
                  <div v-for="(opt, idx) in item.options" :key="idx">
                    ▸ {{ opt.group_name }}: {{ opt.option_name }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-xs text-app-muted dark:text-gray-400 italic">
            No items found
          </div>
        </div>

        <!-- Action Area -->
        <div class="p-4 bg-app-bg/50 dark:bg-gray-900/50">
          <BaseButton
            :variant="
              order.fulfillment_status === 'queue' ? 'primary' : 'secondary'
            "
            size="lg"
            fullWidth
            @click="handleNextStatus(order)"
          >
            {{
              order.fulfillment_status === "queue"
                ? "START PREPARING"
                : "MARK AS SERVED"
            }}
          </BaseButton>
        </div>
      </div>
    </TransitionGroup>
  </div>
</template>

<style scoped>
.list-enter-active,
.list-leave-active {
  transition: all 0.5s ease;
}

.list-enter-from {
  opacity: 0;
  transform: translateX(-30px);
}

.list-leave-to {
  opacity: 0;
  transform: translateY(30px);
}
</style>
