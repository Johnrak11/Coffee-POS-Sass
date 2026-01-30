<script setup lang="ts">
import { ref, computed, watch } from "vue";
import { useRouter } from "vue-router";
import { useI18n } from "vue-i18n";
import { useSessionStore } from "@/stores/session";
import { guestApi } from "@/api";

interface Order {
  id: number;
  order_number: string;
  queue_number: number;
  total_amount: string;
  payment_status: string;
  payment_method: string;
  fulfillment_status: string;
  item_count: number;
  created_at: string;
  items: Array<{ name: string; quantity: number }>;
}

interface Props {
  show: boolean;
}

const props = defineProps<Props>();
const emit = defineEmits(["close"]);

const router = useRouter();
const { t } = useI18n();
const sessionStore = useSessionStore();
const orders = ref<Order[]>([]);
const loading = ref(false);

const activeOrders = computed(() =>
  orders.value.filter(
    (order) =>
      (order.fulfillment_status === "queue" ||
        order.fulfillment_status === "preparing") &&
      // Filter out rejected/failed orders
      order.payment_status !== "rejected" &&
      order.payment_status !== "failed",
  ),
);

const pastOrders = computed(() =>
  orders.value.filter((order) => order.fulfillment_status === "served"),
);

async function fetchOrders() {
  loading.value = true;
  try {
    const token = sessionStore.sessionToken;
    if (!token) {
      console.error("No session token");
      loading.value = false;
      return;
    }
    const response = await guestApi.getSessionOrders(token);
    orders.value = response.data.orders;
  } catch (error) {
    console.error("Failed to load orders:", error);
  } finally {
    loading.value = false;
  }
}

function getStatusBadgeClass(status: string) {
  switch (status) {
    case "queue":
      return "bg-blue-100 text-blue-700";
    case "preparing":
      return "bg-orange-100 text-orange-700";
    case "served":
      return "bg-green-100 text-green-700";
    default:
      return "bg-gray-100 text-gray-700";
  }
}

function getStatusText(status: string) {
  switch (status) {
    case "queue":
      return "In Queue";
    case "preparing":
      return "Preparing";
    case "served":
      return "Ready";
    default:
      return status;
  }
}

function trackOrder(orderId: number) {
  router.push(`/success/${orderId}`);
  emit("close");
}

// Touch gesture handling for swipe-to-dismiss
const dragStartY = ref(0);
const dragCurrentY = ref(0);
const isDragging = ref(false);

const modalTransform = computed(() => {
  if (!isDragging.value) return "translateY(0)";

  const deltaY = dragCurrentY.value - dragStartY.value;
  // Only allow downward drag
  if (deltaY > 0) {
    return `translateY(${deltaY}px)`;
  }
  return "translateY(0)";
});

function handleTouchStart(e: TouchEvent) {
  const touch = e.touches[0];
  if (!touch) return;

  dragStartY.value = touch.clientY;
  dragCurrentY.value = touch.clientY;
  isDragging.value = true;
}

function handleTouchMove(e: TouchEvent) {
  if (!isDragging.value) return;

  const touch = e.touches[0];
  if (!touch) return;

  dragCurrentY.value = touch.clientY;
  const deltaY = dragCurrentY.value - dragStartY.value;

  // Only allow downward drag
  if (deltaY > 0) {
    e.preventDefault();
  }
}

function handleTouchEnd() {
  if (!isDragging.value) return;

  const deltaY = dragCurrentY.value - dragStartY.value;

  // Close if dragged down more than 100px
  if (deltaY > 100) {
    // Don't reset drag state immediately - let the close animation use it
    emit("close");
    // Reset after short delay
    setTimeout(() => {
      isDragging.value = false;
      dragStartY.value = 0;
      dragCurrentY.value = 0;
    }, 50);
  } else {
    // Not closing - reset immediately to snap back
    isDragging.value = false;
    dragStartY.value = 0;
    dragCurrentY.value = 0;
  }
}

// Watch for modal open and fetch orders + lock scroll
watch(
  () => props.show,
  (newValue) => {
    if (newValue) {
      fetchOrders();
      // Lock body scroll
      document.body.style.overflow = "hidden";
    } else {
      // Unlock body scroll
      document.body.style.overflow = "";
    }
  },
);
</script>

<template>
  <Teleport to="body">
    <Transition name="modal">
      <div
        v-if="show"
        class="fixed inset-0 z-50 flex items-end justify-center bg-black/50"
        @click.self="emit('close')"
      >
        <div
          class="w-full max-w-2xl bg-white rounded-t-[40px] shadow-2xl max-h-[90vh] overflow-hidden flex flex-col animate-slide-up"
          :class="{ 'transition-transform': !isDragging }"
          :style="{ transform: modalTransform }"
          @touchstart="handleTouchStart"
          @touchmove="handleTouchMove"
          @touchend="handleTouchEnd"
        >
          <!-- Drag Handle -->
          <div class="w-full flex justify-center pt-3 pb-1">
            <div class="w-12 h-1.5 bg-gray-300 rounded-full"></div>
          </div>

          <!-- Header -->
          <div
            class="sticky top-0 bg-white border-b border-gray-200 px-6 py-4 flex items-center justify-between z-10"
          >
            <h2 class="text-2xl font-black text-gray-900">
              {{ $t("ordersModal.myOrders") }}
            </h2>
            <button
              @click="emit('close')"
              class="w-10 h-10 rounded-full bg-gray-100 flex items-center justify-center hover:bg-gray-200 transition-colors"
            >
              <svg
                class="w-5 h-5 text-gray-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M6 18L18 6M6 6l12 12"
                />
              </svg>
            </button>
          </div>

          <!-- Content -->
          <div class="flex-1 overflow-y-auto px-6 py-6">
            <div v-if="loading" class="space-y-4">
              <div
                v-for="i in 3"
                :key="i"
                class="skeleton h-32 rounded-2xl"
              ></div>
            </div>

            <div v-else-if="orders.length === 0" class="text-center py-16">
              <div class="text-6xl mb-4">🛒</div>
              <h3 class="text-xl font-bold text-gray-900 mb-2">
                {{ $t("ordersModal.noOrdersYet") }}
              </h3>
              <p class="text-gray-500">
                {{ $t("ordersModal.placeFirstOrder") }}
              </p>
            </div>

            <div v-else class="space-y-8">
              <!-- Active Orders -->
              <div v-if="activeOrders.length > 0">
                <h3 class="text-lg font-black text-gray-900 mb-4">
                  📍 {{ $t("ordersModal.activeOrders") }}
                </h3>
                <div class="space-y-3">
                  <div
                    v-for="order in activeOrders"
                    :key="order.id"
                    class="bg-white border-2 border-gray-200 rounded-2xl p-5 shadow-sm hover:shadow-md transition-shadow"
                  >
                    <div class="flex items-start justify-between mb-3">
                      <div>
                        <div class="flex items-center gap-2 mb-1">
                          <span class="text-lg font-black text-gray-900"
                            >Queue #{{ order.queue_number }}</span
                          >
                          <span
                            :class="
                              getStatusBadgeClass(order.fulfillment_status)
                            "
                            class="text-xs font-bold px-2 py-1 rounded-full"
                          >
                            {{ getStatusText(order.fulfillment_status) }}
                          </span>
                        </div>
                        <p class="text-xs text-gray-500 font-mono">
                          {{ order.order_number }}
                        </p>
                      </div>
                      <span class="text-lg font-black text-gray-900"
                        >${{ Number(order.total_amount).toFixed(2) }}</span
                      >
                    </div>

                    <div class="text-sm text-gray-600 mb-4">
                      <span class="font-medium"
                        >{{ order.item_count }} item{{
                          order.item_count > 1 ? "s" : ""
                        }}</span
                      >
                      <span class="mx-2">•</span>
                      <span>{{
                        order.payment_method === "cash" ? "💵 Cash" : "📱 KHQR"
                      }}</span>
                    </div>

                    <button
                      @click="trackOrder(order.id)"
                      class="w-full py-3 bg-gray-900 text-white font-bold rounded-xl hover:bg-gray-800 transition-colors active:scale-[0.98]"
                    >
                      {{ $t("ordersModal.trackOrder") }}
                    </button>
                  </div>
                </div>
              </div>

              <!-- Past Orders -->
              <div v-if="pastOrders.length > 0">
                <h3 class="text-lg font-black text-gray-900 mb-4">
                  ✅ Past Orders
                </h3>
                <div class="space-y-3">
                  <div
                    v-for="order in pastOrders"
                    :key="order.id"
                    class="bg-gray-50 border border-gray-200 rounded-2xl p-5"
                  >
                    <div class="flex items-start justify-between mb-2">
                      <div>
                        <div class="flex items-center gap-2 mb-1">
                          <span class="text-base font-bold text-gray-700"
                            >Queue #{{ order.queue_number }}</span
                          >
                          <span
                            class="bg-green-100 text-green-700 text-xs font-bold px-2 py-1 rounded-full"
                          >
                            Ready
                          </span>
                        </div>
                        <p class="text-xs text-gray-400 font-mono">
                          {{ order.order_number }}
                        </p>
                      </div>
                      <span class="text-base font-bold text-gray-700"
                        >${{ Number(order.total_amount).toFixed(2) }}</span
                      >
                    </div>

                    <div class="text-sm text-gray-500">
                      <span
                        >{{ order.item_count }} item{{
                          order.item_count > 1 ? "s" : ""
                        }}</span
                      >
                      <span class="mx-2">•</span>
                      <span>{{
                        order.payment_method === "cash" ? "💵 Cash" : "📱 KHQR"
                      }}</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </Transition>
  </Teleport>
</template>

<style scoped>
.modal-enter-active,
.modal-leave-active {
  transition: opacity 0.3s ease;
}

.modal-enter-from,
.modal-leave-to {
  opacity: 0;
}

.modal-enter-active .animate-slide-up {
  animation: slide-up 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}

.modal-leave-active .animate-slide-up {
  animation: slide-down 0.2s ease-in;
}

@keyframes slide-up {
  from {
    transform: translateY(100%);
  }
  to {
    transform: translateY(0);
  }
}

@keyframes slide-down {
  from {
    transform: translateY(0);
  }
  to {
    transform: translateY(100%);
  }
}

.skeleton {
  background-color: rgb(229 231 235);
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>
