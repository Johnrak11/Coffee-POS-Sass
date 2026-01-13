<script setup lang="ts">
import { onMounted, onUnmounted, ref, watch } from "vue";
import { useKitchenStore } from "@/stores/kitchen";

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
  // Use 'lucky-cafe' or getting from auth store
  await kitchenStore.fetchOrders("lucky-cafe");
}

function getStatusColor(status: string) {
  switch (status) {
    case "queue":
      return "bg-orange-500/20 text-orange-400 border-orange-500/50";
    case "preparing":
      return "bg-blue-500/10 text-blue-500 border-blue-200 dark:border-blue-900";
    default:
      return "bg-gray-100 dark:bg-gray-800 text-gray-500 border-gray-200 dark:border-gray-700";
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

  if (mins > 10) return "text-red-500 animate-pulse";
  if (mins > 5) return "text-yellow-500";
  return "text-green-500";
}

async function handleNextStatus(order: any) {
  const nextStatus =
    order.fulfillment_status === "queue" ? "preparing" : "served";
  await kitchenStore.updateStatus(order.id, nextStatus);
}
</script>

<template>
  <div class="h-full p-6 overflow-x-auto flex gap-6 items-start">
    <div
      v-if="loading"
      class="absolute inset-0 flex items-center justify-center bg-app-bg/50 backdrop-blur-sm z-50"
    >
      <div
        class="w-12 h-12 border-4 border-orange-500 border-t-transparent rounded-full animate-spin"
      ></div>
    </div>

    <!-- Empty State -->
    <div
      v-if="!loading && kitchenStore.orders.length === 0"
      class="flex-1 flex flex-col items-center justify-center opacity-30 mt-20"
    >
      <svg
        class="w-32 h-32 mb-4 text-app-muted"
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
      <h2 class="text-3xl font-bold text-app-text">Kitchen is clear!</h2>
      <p class="text-app-muted">New orders will appear here automatically.</p>
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
        class="w-80 bg-app-surface rounded-2xl shadow-xl flex flex-col max-h-full border border-app-border overflow-hidden transition-colors duration-300"
      >
        <!-- Card Header -->
        <div
          class="p-4 border-b border-app-border flex justify-between items-start"
        >
          <div>
            <div class="text-sm text-app-muted font-mono">
              {{ order.order_number }}
            </div>
            <div class="text-xl font-bold text-app-text">
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
          class="bg-app-bg px-4 py-2 flex justify-between items-center text-sm border-b border-app-border"
        >
          <span class="text-app-muted">Wait Time:</span>
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
                class="w-8 h-8 rounded bg-app-bg flex items-center justify-center font-bold text-orange-600 border border-app-border"
              >
                {{ item.quantity }}
              </div>
              <div class="flex-1">
                <div class="font-bold text-app-text">
                  {{ item.product?.name }}
                </div>
                <div v-if="item.variant" class="text-xs text-app-muted italic">
                  {{ item.variant.name }}: {{ item.variant.option_name }}
                </div>
                <!-- Display Options -->
                <div
                  v-if="item.options && item.options.length > 0"
                  class="text-xs text-orange-600 mt-1 space-y-0.5 font-medium"
                >
                  <div v-for="(opt, idx) in item.options" :key="idx">
                    ▸ {{ opt.group_name }}: {{ opt.option_name }}
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="text-xs text-app-muted italic">No items found</div>
        </div>

        <!-- Action Area -->
        <div class="p-4 bg-app-bg/50">
          <button
            @click="handleNextStatus(order)"
            :class="[
              'w-full py-3 rounded-xl font-bold transition-all active:scale-95 text-white',
              order.fulfillment_status === 'queue'
                ? 'bg-orange-600 hover:bg-orange-500 shadow-lg shadow-orange-900/20'
                : 'bg-blue-600 hover:bg-blue-500 shadow-lg shadow-blue-900/20',
            ]"
          >
            {{
              order.fulfillment_status === "queue"
                ? "START PREPARING"
                : "MARK AS SERVED"
            }}
          </button>
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
