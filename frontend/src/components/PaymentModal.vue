<script setup lang="ts">
import { ref, computed, onUnmounted, onMounted, watch } from "vue";
import { useAuthStore } from "@/stores/auth";
import { usePosStore } from "@/stores/pos";
import apiClient from "@/api";
import { useI18n } from "vue-i18n";

const props = defineProps<{
  show: boolean;
  total: number;
  initialMethod?: "DASHBOARD" | "KHQR";
  existingOrder?: any; // New prop for Repayment Flow
}>();

const emit = defineEmits(["close", "confirm", "success", "print"]);
const authStore = useAuthStore();
const posStore = usePosStore();
const { t } = useI18n();

const currency = ref<"USD" | "KHR">("USD");
const paymentMethod = ref<"DASHBOARD" | "KHQR">(
  props.initialMethod || "DASHBOARD"
);
const receivedAmount = ref("");

// KHQR State
const qrLoading = ref(false);
const qrString = ref("");
const qrMd5 = ref("");
const qrImage = ref("");
const paymentStatus = ref<"pending" | "success" | "failed" | "expired">(
  "pending"
);
const snapshotAmount = ref(0);
const snapshotCurrency = ref<"USD" | "KHR">("USD");
let statusInterval: any = null;

// Timer State
const qrTimer = ref(120); // 2 minutes
let timerInterval: any = null;

const exchangeRate = computed(() => {
  return Number(authStore.shop?.exchange_rate) || 4100;
});

const totalInCurrency = computed(() => {
  if (currency.value === "KHR") {
    return Math.ceil((props.total * exchangeRate.value) / 100) * 100;
  }
  return props.total;
});

const change = computed(() => {
  const received = parseFloat(receivedAmount.value);
  if (isNaN(received)) return 0;
  return received - totalInCurrency.value;
});

const isValid = computed(() => {
  const received = parseFloat(receivedAmount.value);
  return !isNaN(received) && received >= totalInCurrency.value;
});

const formattedTimer = computed(() => {
  const m = Math.floor(qrTimer.value / 60);
  const s = qrTimer.value % 60;
  return `${m}:${s < 10 ? "0" + s : s}`;
});

watch(
  () => props.show,
  (newVal) => {
    if (newVal) {
      if (props.initialMethod) {
        paymentMethod.value = props.initialMethod;
      }
      // If existing order, set currency from it
      if (props.existingOrder) {
        currency.value = props.existingOrder.payment_currency || "USD";
        if (props.initialMethod === "KHQR") {
          // If we open specifically for KHQR, ensure currency matches order?
          // Actually, if order is USD, and we open KHQR, it generates in USD.
          // If order is KHR, it generates in KHR.
          // We should probably lock the currency toggle if it's an existing order?
        }
      } else if (props.initialMethod === "KHQR") {
        currency.value = "KHR";
      }
    } else {
      stopPolling();
      stopTimer();
    }
  }
);

function toggleCurrency(curr: "USD" | "KHR") {
  // If repayment, we might want to restrict currency switching if it messes up order logic
  // But typically user might want to pay USD order in Riel (Cash).
  // For KHQR, regenerate endpoint uses order's currency.
  // So for KHQR Repayment, we should probably LOCK the currency to the order's currency.
  if (props.existingOrder && paymentMethod.value === "KHQR") return;

  if (paymentStatus.value === "pending" || !qrString.value) {
    currency.value = curr;
    receivedAmount.value = "";
  }
}

function appendNumber(num: string) {
  if (num === "." && receivedAmount.value.includes(".")) return;
  receivedAmount.value += num;
}

function clear() {
  receivedAmount.value = "";
}

const currentOrder = ref<any>(null);

async function handleConfirm() {
  if (paymentMethod.value === "KHQR") {
    await handleKhqrPayment();
  } else {
    // Cash Payment
    if (isValid.value) {
      // Check if we have an existing order (prop) OR a locally created pending order
      if (props.existingOrder || currentOrder.value) {
        // Update existing/pending order
        await handleCashRepayment();
      } else {
        // Default Flow (Create New Order)
        emit("confirm", {
          receivedAmount: parseFloat(receivedAmount.value),
          currency: currency.value,
          method: "DASHBOARD",
        });
      }
    }
  }
}

async function handleCashRepayment() {
  const targetOrder = props.existingOrder || currentOrder.value;
  if (!targetOrder) return;

  try {
    const result = await posStore.updatePaymentStatus(targetOrder.id, "paid", {
      payment_method: "cash",
      received_amount: parseFloat(receivedAmount.value),
      payment_currency: currency.value,
    });

    if (result && result.success) {
      currentOrder.value = targetOrder; // Ensure currentOrder is set
      paymentStatus.value = "success";

      // If paymentStatus is success, the condition in template `paymentStatus !== 'success'` will hide the input div
      // and show the success div.

      // If this was a new order (not repayment), we MUST clear the POS cart now
      if (!props.existingOrder) {
        posStore.clearOrder();
      } else {
        emit("success"); // Notify parent (TransactionHistory) to refresh
      }
    }
  } catch (e) {
    console.error(e);
  }
}

async function handleKhqrPayment() {
  if (!authStore.shop?.id) return;
  if (qrLoading.value) return;

  try {
    qrLoading.value = true;
    qrString.value = "";
    qrImage.value = "";
    paymentStatus.value = "pending";
    qrTimer.value = 300; // 5 minutes

    // Snapshot values
    snapshotAmount.value = totalInCurrency.value;
    snapshotCurrency.value = currency.value;

    let result;
    const targetOrder = props.existingOrder || currentOrder.value;

    if (targetOrder) {
      // REGENERATE Flow (Reuse existing/pending order)
      const response = await apiClient.post("/khqr/regenerate", {
        order_id: targetOrder.id,
      });
      result = {
        success: true,
        order: {
          ...targetOrder,
          qr_data: response.data.data || response.data,
        },
      };
    } else {
      // CREATE Flow (First time)
      result = await posStore.processPayment(
        authStore.shop.id,
        "khqr",
        currency.value,
        totalInCurrency.value
      );
    }

    if (result && result.order?.qr_data) {
      // Update currentOrder
      currentOrder.value = result.order;

      const qrData = result.order.qr_data;
      qrString.value = qrData.qr_string;
      qrMd5.value = qrData.md5;
      qrImage.value = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(
        qrData.qr_string
      )}`;

      startPolling();
      startTimer();
    } else {
      console.error("Failed to initiate KHQR payment");
      paymentStatus.value = "failed";
    }
  } catch (e) {
    console.error("KHQR Error", e);
    paymentStatus.value = "failed";
  } finally {
    qrLoading.value = false;
  }
}

function exactAmount() {
  receivedAmount.value = totalInCurrency.value.toString();
}

function formatCurrency(amount: number) {
  if (currency.value === "KHR") {
    return new Intl.NumberFormat("en-US").format(amount);
  }
  return Number(amount).toFixed(2);
}

function formatSnapshotCurrency(amount: number, curr: "USD" | "KHR") {
  if (curr === "KHR") {
    return new Intl.NumberFormat("en-US").format(amount);
  }
  return Number(amount).toFixed(2);
}

function startTimer() {
  stopTimer();
  timerInterval = setInterval(() => {
    if (qrTimer.value > 0) {
      qrTimer.value--;
    } else {
      // Expired!
      stopTimer();
      stopPolling();
      paymentStatus.value = "expired";
    }
  }, 1000);
}

function stopTimer() {
  if (timerInterval) clearInterval(timerInterval);
  timerInterval = null;
}

function startPolling() {
  stopPolling();
  statusInterval = setInterval(async () => {
    if (
      !qrMd5.value ||
      paymentStatus.value === "success" ||
      paymentStatus.value === "expired"
    )
      return;

    try {
      // Single Check Status
      const response = await apiClient.post("/khqr/check-status-single", {
        md5: qrMd5.value,
      });

      const results = response.data.data || [];
      const myTx = results.length > 0 ? results[0] : null;

      // External API returns responseCode: 0 for success
      if (
        myTx &&
        (myTx.responseCode === 0 || myTx.responseMessage === "Success")
      ) {
        paymentStatus.value = "success";
        stopPolling();
        stopTimer();

        // Only clear POS cart if it was a new order (not repayment)
        if (!props.existingOrder) {
          // If we have order data in metadata, update currentOrder for printing
          if (currentOrder.value) {
            currentOrder.value.payment_status = "paid";
          }
          posStore.clearOrder();
        } else {
          emit("success"); // Notify parent to refresh list
        }
      }
    } catch (e) {
      console.error("Polling status failed", e);
    }
  }, 2000);
}

function stopPolling() {
  if (statusInterval) {
    clearInterval(statusInterval);
    statusInterval = null;
  }
}

function handleClose() {
  stopPolling();
  stopTimer();
  // If we created a temporary pending order and closed the modal, we lose reference to it.
  // It remains pending in backend. This is expected behavior if user abandons transaction.
  if (!props.existingOrder) {
    currentOrder.value = null;
  }
  emit("close");
}

function handlePrint() {
  if (currentOrder.value) {
    emit("print", currentOrder.value);
    handleClose();
  }
}

function selectMethod(method: "DASHBOARD" | "KHQR") {
  paymentMethod.value = method;
  if (method === "DASHBOARD") {
    stopPolling();
    stopTimer();
  }
}

function cancelKhqr() {
  stopPolling();
  stopTimer();
  qrString.value = "";
  qrImage.value = "";
  paymentStatus.value = "pending";
  qrLoading.value = false;
  // IMPORTANT: Do NOT clear currentOrder.value here.
  // We want to keep the pending order so we can update it if they switch to Cash.
  // if (!props.existingOrder) currentOrder.value = null; // REMOVED
}


function handleKeydown(e: KeyboardEvent) {
  if (!props.show) return;

  // Escape to Close (Already handled usually, but ensuring)
  if (e.key === "Escape") {
    handleClose();
    return;
  }

  // Only handle input for DASHBOARD (Cash) method
  if (paymentMethod.value !== "DASHBOARD") return;

  // Numbers 0-9
  if (/^[0-9]$/.test(e.key)) {
    appendNumber(e.key);
    return;
  }

  // Dot
  if (e.key === ".") {
    appendNumber(".");
    return;
  }

  // Backspace
  if (e.key === "Backspace") {
    receivedAmount.value = receivedAmount.value.slice(0, -1);
    return;
  }

  // Enter to Confirm
  if (e.key === "Enter") {
    handleConfirm();
    return;
  }

  // Delete to Clear
  if (e.key === "Delete") {
    clear();
    return;
  }
}

onMounted(() => {
  window.addEventListener("keydown", handleKeydown);
});

onUnmounted(() => {
  window.removeEventListener("keydown", handleKeydown);
  stopPolling();
  stopTimer();
});
</script>

<template>
  <div
    v-if="show"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
  >
    <!-- Backdrop -->
    <div
      class="absolute inset-0 bg-black/60 backdrop-blur-sm transition-opacity"
    ></div>

    <!-- Modal -->
    <div
      class="relative bg-app-surface rounded-3xl shadow-2xl w-full max-w-md overflow-hidden animate-scale-in border border-app-border"
    >
      <div class="w-full flex flex-col h-full">
        <!-- Mode: Calculation / Input -->
        <div
          v-if="
            !qrString &&
            !qrLoading &&
            paymentStatus !== 'failed' &&
            paymentStatus !== 'expired' &&
            paymentStatus !== 'success'
          "
        >
          <div
            class="transition-colors duration-300 text-white p-5 text-center relative overflow-hidden"
            :class="
              change > 0 && paymentMethod === 'DASHBOARD'
                ? 'bg-emerald-600'
                : 'bg-gray-900'
            "
          >
            <!-- Close Button -->
            <button
              @click="handleClose"
              class="absolute top-4 left-4 p-2 bg-white/10 hover:bg-white/20 rounded-full transition-colors z-20"
            >
              <svg
                class="w-5 h-5 text-white"
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

            <!-- Decor -->
            <div class="absolute top-0 right-0 p-4 opacity-10">
              <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24">
                <path
                  d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.31-8.86c-1.77-.45-2.34-.94-2.34-1.67 0-.84.79-1.43 2.1-1.43 1.38 0 1.9.66 1.94 1.64h1.71c-.05-1.34-.87-2.57-2.49-2.97V5H10.9v1.69c-1.51.32-2.72 1.3-2.72 2.81 0 1.79 1.49 2.69 3.66 3.21 1.95.46 2.34 1.15 2.34 1.87 0 .53-.39 1.39-2.1 1.39-1.6 0-2.23-.72-2.32-1.64H8.04c.1 1.7 1.36 2.66 2.86 2.97V19h2.34v-1.67c1.52-.29 2.72-1.16 2.73-2.77-.01-2.2-1.9-2.96-3.66-3.42z"
                />
              </svg>
            </div>

            <!-- Currency Toggles -->
            <div class="relative z-10 flex justify-center gap-2 mb-4">
              <button
                @click="toggleCurrency('USD')"
                :class="[
                  'px-4 py-1.5 rounded-full text-xs font-bold transition-all',
                  currency === 'USD'
                    ? 'bg-white text-gray-900 shadow-md transform scale-105'
                    : 'bg-gray-800 text-gray-400 hover:bg-gray-700',
                ]"
              >
                USD ($)
              </button>
              <button
                @click="toggleCurrency('KHR')"
                :class="[
                  'px-4 py-1.5 rounded-full text-xs font-bold transition-all',
                  currency === 'KHR'
                    ? 'bg-white text-gray-900 shadow-md transform scale-105'
                    : 'bg-gray-800 text-gray-400 hover:bg-gray-700',
                ]"
              >
                KHR (៛)
              </button>
            </div>

            <h2 class="relative z-10 text-xl font-medium opacity-80 mb-1">
              {{
                change > 0 && paymentMethod === "DASHBOARD"
                  ? $t("pos.change")
                  : $t("pos.totalToPay")
              }}
            </h2>
            <div
              class="relative z-10 text-4xl font-black font-display tracking-tight text-shadow-sm"
            >
              {{ currency === "USD" ? "$" : "៛" }}
              {{
                change > 0 && paymentMethod === "DASHBOARD"
                  ? formatCurrency(change)
                  : formatCurrency(totalInCurrency)
              }}
            </div>

            <!-- Payment Method Toggle -->
            <div class="relative z-10 mt-6 flex justify-center gap-2">
              <button
                @click="selectMethod('DASHBOARD')"
                :class="[
                  'px-4 py-2 rounded-xl text-sm font-bold transition-all border',
                  paymentMethod === 'DASHBOARD'
                    ? 'bg-primary-600 border-primary-500 text-white shadow-lg shadow-primary-900/50'
                    : 'border-gray-700 text-gray-400 hover:bg-gray-800',
                ]"
              >
                {{ $t("payment.cashPayment") }}
              </button>
              <button
                @click="selectMethod('KHQR')"
                :class="[
                  'px-4 py-2 rounded-xl text-sm font-bold transition-all border',
                  paymentMethod === 'KHQR'
                    ? 'bg-red-600 border-red-500 text-white shadow-lg shadow-red-900/50'
                    : 'border-gray-700 text-gray-400 hover:bg-gray-800',
                ]"
              >
                {{ $t("payment.khqrOnly") }}
              </button>
            </div>
          </div>

          <div class="p-5">
            <div class="mb-5">
              <label class="block text-xs font-bold text-app-muted mb-1.5"
                >{{ $t("pos.cashReceived") }} ({{ currency }})</label
              >
              <div class="flex gap-2">
                <div
                  class="flex-1 bg-app-bg rounded-xl p-2 text-3xl font-bold text-right text-app-text border-2 border-transparent focus-within:border-primary-500 transition-colors"
                  :class="{ 'opacity-50': paymentMethod === 'KHQR' }"
                >
                  {{
                    paymentMethod === "KHQR"
                      ? formatCurrency(totalInCurrency)
                      : receivedAmount
                      ? formatCurrency(Number(receivedAmount))
                      : "0"
                  }}
                </div>
                <button
                  v-if="paymentMethod !== 'KHQR'"
                  @click="exactAmount"
                  class="px-4 py-2 bg-app-border rounded-xl font-bold text-app-muted hover:text-app-text hover:bg-gray-300 dark:hover:bg-gray-600 active:bg-gray-400 dark:active:bg-gray-700 transition-colors"
                >
                  {{ $t("common.exact") }}
                </button>
              </div>
            </div>



            <!-- Numpad (Only for Cash) -->
            <div
              v-if="paymentMethod === 'DASHBOARD'"
              class="grid grid-cols-3 gap-2.5 mb-5"
            >
              <button
                v-for="n in [1, 2, 3, 4, 5, 6, 7, 8, 9]"
                :key="n"
                @click="appendNumber(n.toString())"
                class="h-12 rounded-xl bg-app-bg text-xl text-app-text font-bold hover:bg-gray-200 dark:hover:bg-gray-700 active:bg-gray-300 dark:active:bg-gray-600 border border-app-border transition-colors shadow-sm"
              >
                {{ n }}
              </button>
              <button
                @click="appendNumber('.')"
                :disabled="currency === 'KHR'"
                class="h-12 rounded-xl bg-app-bg text-xl text-app-text font-bold hover:bg-gray-200 dark:hover:bg-gray-700 border border-app-border transition-colors disabled:opacity-50 shadow-sm"
              >
                .
              </button>
              <button
                @click="appendNumber('0')"
                class="h-12 rounded-xl bg-app-bg text-xl text-app-text font-bold hover:bg-gray-200 dark:hover:bg-gray-700 border border-app-border transition-colors shadow-sm"
              >
                0
              </button>
              <button
                @click="clear"
                class="h-12 rounded-xl bg-red-50 text-red-500 font-bold hover:bg-red-100 border border-red-100 dark:bg-red-900/10 dark:hover:bg-red-900/30 dark:border-red-900/30 transition-colors"
              >
                C
              </button>
            </div>

            <!-- KHQR Info Text -->
            <div
              v-else
              class="mb-6 p-4 bg-gradient-to-br from-blue-50 to-indigo-50 text-blue-900 rounded-xl text-sm leading-relaxed border border-blue-100 dark:from-blue-900/20 dark:to-indigo-900/20 dark:text-blue-200 dark:border-blue-900/30 shadow-inner"
            >
              <p class="font-bold mb-1 flex items-center gap-2">
                <span
                  class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"
                ></span>
                {{ $t("payment.secureKhqr") }}
              </p>
              <p>
                {{ $t("payment.khqrDesc") }}
                <b class="text-lg"
                  >{{ formatCurrency(totalInCurrency) }} {{ currency }}</b
                >.
              </p>
            </div>

            <!-- Action Button -->
            <button
              @click="handleConfirm"
              :disabled="paymentMethod === 'DASHBOARD' && !isValid"
              :class="[
                'w-full py-3.5 rounded-2xl font-black text-xl transition-all shadow-xl transform active:scale-95',
                paymentMethod === 'KHQR'
                  ? 'bg-gradient-to-r from-red-600 to-rose-600 text-white hover:from-red-500 hover:to-rose-500 shadow-red-500/30'
                  : isValid
                  ? 'bg-gradient-to-r from-emerald-500 to-teal-600 text-white hover:from-emerald-400 hover:to-teal-500 shadow-lg shadow-emerald-500/30'
                  : 'bg-app-bg text-app-muted cursor-not-allowed border border-app-border',
              ]"
            >
              {{
                paymentMethod === "KHQR"
                  ? $t("payment.confirmToPay")
                  : $t("pos.completePayment")
              }}
            </button>
          </div>
        </div>

        <!-- Mode: Success (Exclusive) -->
        <div
          v-else-if="paymentStatus === 'success'"
          class="bg-white/95 dark:bg-gray-900/95 backdrop-blur-md flex flex-col items-center justify-center animate-fade-in text-center p-8 relative rounded-3xl min-h-[400px]"
        >
          <!-- Animated Background Blobs -->
          <div
            class="absolute inset-0 overflow-hidden pointer-events-none z-0 rounded-3xl"
          >
            <div
              class="absolute top-[-10%] left-[-10%] w-64 h-64 bg-green-200/30 rounded-full blur-3xl animate-blob"
            ></div>
            <div
              class="absolute bottom-[-10%] right-[-10%] w-64 h-64 bg-emerald-200/30 rounded-full blur-3xl animate-blob animation-delay-2000"
            ></div>
          </div>

          <!-- Content -->
          <div class="relative z-10 flex flex-col items-center w-full">
            <!-- Animated Checkmark -->
            <div class="success-checkmark mb-6">
              <div class="check-icon">
                <span class="icon-line line-tip"></span>
                <span class="icon-line line-long"></span>
                <div class="icon-circle"></div>
                <div class="icon-fix"></div>
              </div>
            </div>

            <h2
              class="text-3xl font-black text-gray-900 dark:text-white mb-2 tracking-tight"
            >
              {{ $t("common.paymentSuccessful") }}
            </h2>
            <p class="text-gray-500 mb-10 max-w-xs mx-auto font-medium">
              {{ $t("common.transactionCompleted") }}
            </p>

            <div class="flex flex-col gap-3 w-full max-w-xs">
              <button
                @click="handlePrint"
                class="w-full py-4 bg-gray-900 text-white hover:bg-black rounded-2xl font-bold transition-all shadow-xl hover:shadow-2xl flex items-center justify-center gap-3 transform active:scale-95 group"
              >
                <svg
                  class="w-6 h-6 text-gray-300 group-hover:text-white transition-colors"
                  fill="none"
                  stroke="currentColor"
                  viewBox="0 0 24 24"
                >
                  <path
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="2"
                    d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                  />
                </svg>
                Print Receipt
              </button>
              <button
                @click="handleClose"
                class="w-full py-4 bg-gray-100 hover:bg-gray-200 text-gray-600 font-bold rounded-2xl transition-colors"
              >
                Close
              </button>
            </div>
          </div>
        </div>

        <!-- Mode: KHQR Loading / Display (Excluded Success) -->
        <div
          v-else
          class="p-0 bg-gray-50 dark:bg-gray-900 flex flex-col relative transition-all duration-300"
        >
          <!-- Custom Header for KHQR Card -->

          <div
            class="flex-1 flex flex-col items-center justify-center px-4 py-6 relative"
          >
            <!-- Loading State -->
            <div
              v-if="qrLoading"
              class="flex flex-col items-center text-center animate-pulse"
            >
              <div
                class="w-16 h-16 border-4 border-red-500 border-t-transparent rounded-full animate-spin mb-4"
              ></div>
              <p class="text-gray-900 font-bold text-lg mb-1">
                {{ $t("payment.generatingQr") }}
              </p>
              <p class="text-gray-500 text-xs">
                {{ $t("payment.connectingBakong") }}
              </p>
            </div>

            <!-- Expired State -->
            <div
              v-else-if="paymentStatus === 'expired'"
              class="bg-white rounded-2xl shadow-xl overflow-hidden w-full max-w-[320px] relative border border-gray-100"
            >
              <!-- Red Header -->
              <div
                class="bg-[#E61F25] h-14 flex items-center justify-center relative"
              >
                <h2 class="text-white font-black text-2xl tracking-wider">
                  KHQR
                </h2>
                <!-- Close X -->
                <button
                  @click="handleClose"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors p-1"
                >
                  <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>

              <div class="p-8 flex flex-col items-center text-center">
                <div
                  class="w-16 h-16 bg-orange-50 text-orange-500 rounded-full flex items-center justify-center mb-4"
                >
                  <svg
                    class="w-8 h-8"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                  </svg>
                </div>
                <h3 class="font-bold text-gray-900 text-xl mb-2">
                  {{ $t("payment.qrExpired") }}
                </h3>
                <p class="text-gray-500 text-sm mb-6 leading-relaxed">
                  {{ $t("payment.sessionTimeout") }}
                </p>
                <div class="w-full flex flex-col gap-3">
                  <button
                    @click="handleKhqrPayment"
                    class="w-full py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition shadow-lg shadow-red-200"
                  >
                    Try Again
                  </button>
                  <button
                    @click="cancelKhqr"
                    class="text-gray-400 font-bold hover:text-gray-600 text-sm transition-colors"
                  >
                    Cancel Transaction
                  </button>
                </div>
              </div>
            </div>

            <!-- NEW REF DESIGN KHQR Card -->
            <div
              v-else-if="qrImage && paymentStatus !== 'failed'"
              class="bg-white rounded-2xl shadow-xl overflow-hidden w-full max-w-[320px] relative border border-gray-100"
            >
              <!-- Red Header -->
              <div
                class="bg-[#E61F25] h-14 flex items-center justify-center relative"
              >
                <!-- KHQR Text/Logo simulation -->
                <h2
                  class="text-white font-black text-2xl tracking-wider font-sans"
                >
                  KHQR
                </h2>
                <!-- Close X -->
                <button
                  @click="handleClose"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors p-1"
                >
                  <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>

              <!-- Card Body -->
              <div class="p-5 pb-3">
                <!-- Merchant Info -->
                <div class="mb-2">
                  <p
                    class="text-[10px] text-gray-400 font-bold uppercase tracking-wider mb-0.5"
                  >
                    {{ authStore.shop?.name || "Merchant Name" }}
                  </p>

                  <div class="flex items-baseline gap-1">
                    <span class="text-xl font-black text-red-600">
                      {{ snapshotCurrency === "USD" ? "$" : "៛" }}
                    </span>
                    <span
                      class="text-3xl font-black text-gray-900 tracking-tight"
                    >
                      {{
                        formatSnapshotCurrency(snapshotAmount, snapshotCurrency)
                      }}
                    </span>
                  </div>
                </div>

                <!-- Divider -->
                <div
                  class="w-full border-t-2 border-dashed border-gray-100 my-3 relative"
                >
                  <!-- Little circular cutouts for ticket look -->
                  <div
                    class="absolute -left-7 -top-2.5 w-5 h-5 rounded-full bg-gray-50 dark:bg-gray-900"
                  ></div>
                  <div
                    class="absolute -right-7 -top-2.5 w-5 h-5 rounded-full bg-gray-50 dark:bg-gray-900"
                  ></div>
                </div>

                <!-- QR Section -->
                <div class="flex justify-center mb-4">
                  <div
                    class="p-1.5 border-2 border-[#E61F25] rounded-xl relative"
                  >
                    <!-- QR Image -->
                    <img
                      :src="qrImage"
                      alt="KHQR Code"
                      class="w-44 h-44 object-contain rounded-lg"
                    />
                    <!-- Center Logo Overlay -->
                    <div
                      class="absolute inset-0 flex items-center justify-center pointer-events-none"
                    >
                      <div class="bg-white p-0.5 rounded-full shadow-sm">
                        <!-- Use Shop Logo if available -->
                        <div
                          v-if="authStore.shop?.logo"
                          class="w-9 h-9 rounded-full overflow-hidden border border-gray-100 bg-white"
                        >
                          <img
                            :src="authStore.shop.logo"
                            alt="Logo"
                            class="w-full h-full object-cover"
                          />
                        </div>
                        <!-- Fallback to Currency Symbol -->
                        <div
                          v-else
                          class="w-7 h-7 rounded-full bg-red-600 flex items-center justify-center text-white font-bold text-[8px]"
                        >
                          {{ snapshotCurrency === "USD" ? "$" : "៛" }}
                        </div>
                      </div>
                    </div>
                  </div>
                </div>

                <!-- Footer / Timer -->
                <div class="text-center pb-2">
                  <div class="flex flex-col items-center justify-center gap-2">
                    <p class="text-red-500 text-xs font-bold animate-pulse">
                      Expires in {{ formattedTimer }}
                    </p>

                    <button
                      @click="cancelKhqr"
                      class="text-xs text-gray-400 hover:text-red-500 font-bold transition-colors"
                    >
                      Cancel Transaction
                    </button>
                  </div>
                </div>
              </div>
            </div>

            <!-- Error State -->
            <div
              v-else-if="paymentStatus === 'failed'"
              class="bg-white rounded-2xl shadow-xl overflow-hidden w-full max-w-[320px] relative border border-gray-100"
            >
              <!-- Red Header -->
              <div
                class="bg-[#E61F25] h-14 flex items-center justify-center relative"
              >
                <h2 class="text-white font-black text-2xl tracking-wider">
                  KHQR
                </h2>
                <!-- Close X -->
                <button
                  @click="handleClose"
                  class="absolute right-3 top-1/2 -translate-y-1/2 text-white/50 hover:text-white transition-colors p-1"
                >
                  <svg
                    class="w-6 h-6"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M6 18L18 6M6 6l12 12"
                    ></path>
                  </svg>
                </button>
              </div>

              <div class="p-8 flex flex-col items-center text-center">
                <div
                  class="w-16 h-16 bg-red-50 text-red-500 rounded-full flex items-center justify-center mb-4"
                >
                  <svg
                    class="w-8 h-8"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                  >
                    <path
                      stroke-linecap="round"
                      stroke-linejoin="round"
                      stroke-width="2"
                      d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
                    />
                  </svg>
                </div>
                <h3 class="font-bold text-gray-900 text-xl mb-2">
                  Connection Failed
                </h3>
                <p class="text-gray-500 text-sm mb-6 leading-relaxed">
                  Unable to generate KHQR code. Please check your internet
                  connection.
                </p>
                <div class="w-full flex flex-col gap-3">
                  <button
                    @click="handleConfirm"
                    class="w-full py-3 bg-red-600 text-white font-bold rounded-xl hover:bg-red-700 transition shadow-lg shadow-red-200"
                  >
                    Try Again
                  </button>
                  <button
                    @click="
                      qrString = '';
                      paymentStatus = 'pending';
                    "
                    class="text-gray-400 font-bold hover:text-gray-600 text-sm transition-colors"
                  >
                    Go Back
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<style scoped>
/* Animated Background Blobs */
.animate-blob {
  animation: blob 7s infinite;
}
.animation-delay-2000 {
  animation-delay: 2s;
}
@keyframes blob {
  0% {
    transform: translate(0px, 0px) scale(1);
  }
  33% {
    transform: translate(30px, -50px) scale(1.1);
  }
  66% {
    transform: translate(-20px, 20px) scale(0.9);
  }
  100% {
    transform: translate(0px, 0px) scale(1);
  }
}

/* Success Checkmark Animation */
.success-checkmark {
  width: 80px;
  height: 80px;
}
.check-icon {
  width: 80px;
  height: 80px;
  position: relative;
  border-radius: 50%;
  box-sizing: content-box;
  border: 4px solid #4caf50;
}
.check-icon::before {
  top: 3px;
  left: -2px;
  width: 30px;
  transform-origin: 100% 50%;
  border-radius: 100px 0 0 100px;
}
.check-icon::after {
  top: 0;
  left: 30px;
  width: 60px;
  transform-origin: 0 50%;
  border-radius: 0 100px 100px 0;
  animation: rotate-circle 4.25s ease-in;
}
.check-icon::before,
.check-icon::after {
  content: "";
  height: 100px;
  position: absolute;
  background: #ffffff; /* Match bg color */
  transform: rotate(-45deg);
}
.dark .check-icon::before,
.dark .check-icon::after {
  background: #111827; /* Dark mode bg match */
}
.icon-line {
  height: 5px;
  background-color: #4caf50;
  display: block;
  border-radius: 2px;
  position: absolute;
  z-index: 10;
}
.icon-line.line-tip {
  top: 46px;
  left: 14px;
  width: 25px;
  transform: rotate(45deg);
  animation: icon-line-tip 0.75s;
}
.icon-line.line-long {
  top: 38px;
  right: 8px;
  width: 47px;
  transform: rotate(-45deg);
  animation: icon-line-long 0.75s;
}
.icon-circle {
  top: -4px;
  left: -4px;
  z-index: 10;
  width: 80px;
  height: 80px;
  border-radius: 50%;
  position: absolute;
  box-sizing: content-box;
  border: 4px solid rgba(76, 175, 80, 0.5);
}
.icon-fix {
  top: 8px;
  width: 5px;
  left: 26px;
  z-index: 1;
  height: 85px;
  position: absolute;
  transform: rotate(-45deg);
  background-color: #ffffff;
}
.dark .icon-fix {
  background-color: #111827;
}

@keyframes rotate-circle {
  0% {
    transform: rotate(-45deg);
  }
  5% {
    transform: rotate(-45deg);
  }
  12% {
    transform: rotate(-405deg);
  }
  100% {
    transform: rotate(-405deg);
  }
}
@keyframes icon-line-tip {
  0% {
    width: 0;
    left: 1px;
    top: 19px;
  }
  54% {
    width: 0;
    left: 1px;
    top: 19px;
  }
  70% {
    width: 50px;
    left: -8px;
    top: 37px;
  }
  84% {
    width: 17px;
    left: 21px;
    top: 48px;
  }
  100% {
    width: 25px;
    left: 14px;
    top: 46px;
  }
}
@keyframes icon-line-long {
  0% {
    width: 0;
    right: 46px;
    top: 54px;
  }
  65% {
    width: 0;
    right: 46px;
    top: 54px;
  }
  84% {
    width: 55px;
    right: 0px;
    top: 35px;
  }
  100% {
    width: 47px;
    right: 8px;
    top: 38px;
  }
}

/* Hide scrollbar for Chrome, Safari and Opera */
.no-scrollbar::-webkit-scrollbar {
  display: none;
}

/* Hide scrollbar for IE, Edge and Firefox */
.no-scrollbar {
  -ms-overflow-style: none; /* IE and Edge */
  scrollbar-width: none; /* Firefox */
}
</style>
