<script setup lang="ts">
import { computed } from "vue";
import { useI18n } from "vue-i18n";
import html2canvas from "html2canvas";

const props = defineProps<{
  show: boolean;
  receiptData: {
    items: any[];
    total: number;
    cashReceived: number;
    change: number;
    orderNumber: string;
    queueNumber?: string | number;
    shopName?: string;
    date?: string;
    currency?: "USD" | "KHR";
    shopAddress?: string;
    shopPhone?: string;
    receiptFooter?: string;
    wifiSsid?: string;
    wifiPassword?: string;
  };
}>();

const emit = defineEmits(["close", "print"]);
const { t } = useI18n();

const formattedDate = computed(() => {
  return props.receiptData.date || new Date().toLocaleString();
});

const currencySymbol = computed(() => {
  return props.receiptData.currency === "KHR" ? "៛" : "$";
});

function formatAmount(amount: number) {
  if (props.receiptData.currency === "KHR") {
    // Format Khmer Riel: No decimals, commas separators
    return new Intl.NumberFormat("en-US").format(amount);
  }
  return Number(amount).toFixed(2);
}

function handlePrint() {
  window.print();
  emit("print");
}

async function handleDownloadImage() {
  const element = document.querySelector(".receipt-content") as HTMLElement;
  if (!element) return;

  try {
    const canvas = await html2canvas(element, {
      scale: 2, // Retain high quality
      backgroundColor: "#ffffff",
      logging: false,
      useCORS: true, // if images are used
    });

    const link = document.createElement("a");
    link.download = `Receipt-${props.receiptData.orderNumber || "Order"}.png`;
    link.href = canvas.toDataURL("image/png");
    link.click();
  } catch (e) {
    console.error("Failed to generate receipt image", e);
  }
}

function calculateItemTotal(item: any) {
  let price = Number(item.product.price);
  if (item.variant) price += Number(item.variant.extra_price || 0);
  if (item.options) {
    item.options.forEach((opt: any) => {
      price += Number(opt.extra_price || 0);
    });
  }
  return "$" + (price * item.quantity).toFixed(2);
}
</script>

<template>
  <Teleport to="body">
    <div
      v-if="show"
      class="fixed inset-0 z-[9999] flex items-center justify-center p-4 print:p-0 receipt-modal-wrapper"
    >
      <!-- Backdrop (Hide in print) -->
      <div
        @click="$emit('close')"
        class="absolute inset-0 bg-black/60 backdrop-blur-sm print:hidden"
      ></div>

      <!-- Receipt Modal -->
      <div
        class="relative bg-app-surface w-full max-w-sm rounded-lg shadow-2xl flex flex-col max-h-[90vh] border border-app-border print:border-none print:shadow-none print:w-full print:max-w-none print:max-h-none print:static"
      >
        <!-- Print Actions (Hide in print) -->
        <div
          class="bg-app-bg p-4 flex justify-between items-center print:hidden border-b border-app-border shrink-0"
        >
          <button
            @click="$emit('close')"
            class="px-4 py-2 text-app-muted hover:bg-gray-200 dark:hover:bg-gray-700 rounded-lg text-sm font-medium transition-colors"
          >
            {{ $t("receipt.close") }}
          </button>

          <div class="flex gap-2">
            <button
              @click="handleDownloadImage"
              class="px-4 py-2 bg-gray-100 hover:bg-gray-200 text-gray-800 rounded-lg text-sm font-bold flex items-center gap-2"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
                />
              </svg>
              Save Image
            </button>
            <button
              @click="handlePrint"
              class="px-4 py-2 bg-primary-600 text-white hover:bg-primary-700 rounded-lg text-sm font-bold flex items-center gap-2"
            >
              <svg
                class="w-4 h-4"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"
                ></path>
              </svg>
              {{ $t("receipt.print") }}
            </button>
          </div>
        </div>

        <!-- Actual Receipt Content -->
        <div
          class="p-6 print:p-0 bg-white text-gray-900 font-mono text-sm leading-relaxed receipt-content flex-1 overflow-y-auto"
        >
          <div class="text-center mb-6">
            <h1 class="text-xl font-bold uppercase mb-2">
              {{ receiptData.shopName || "Lucky Cafe" }}
            </h1>
            <div class="text-xs text-center flex flex-col items-center">
              <p v-if="receiptData.shopAddress">
                {{ receiptData.shopAddress }}
              </p>
              <p v-else>Phnom Penh, Cambodia</p>

              <p v-if="receiptData.shopPhone">
                Tel: {{ receiptData.shopPhone }}
              </p>
              <p v-else>Tel: 012 345 678</p>
            </div>
          </div>

          <div class="border-b border-dashed border-gray-300 pb-4 mb-4">
            <!-- Queue Number Display -->
            <div class="text-center mb-4 pb-4 border-b border-gray-200">
              <div class="text-xs uppercase font-bold text-gray-500 mb-1">
                {{ $t("receipt.queueNumber") || "Queue Number" }}
              </div>
              <div class="text-4xl font-black">
                {{ receiptData.queueNumber || "-" }}
              </div>
            </div>

            <div class="flex justify-between">
              <span>{{ $t("receipt.orderNumber") }}</span>
              <span class="font-bold">{{
                receiptData.orderNumber || "PENDING"
              }}</span>
            </div>
            <div class="flex justify-between">
              <span>{{ $t("receipt.date") }}</span>
              <span>{{ formattedDate }}</span>
            </div>
            <div class="flex justify-between">
              <span>{{ $t("receipt.cashier") }}</span>
              <span>{{ $t("nav.staff") }}</span>
            </div>
          </div>

          <div class="mb-4">
            <table class="w-full text-left">
              <thead>
                <tr class="border-b border-gray-300">
                  <th class="py-1 w-1/2">{{ $t("receipt.item") }}</th>
                  <th class="py-1 text-center">{{ $t("receipt.qty") }}</th>
                  <th class="py-1 text-right">{{ $t("receipt.amount") }}</th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-100">
                <tr v-for="item in receiptData.items" :key="item.id">
                  <td class="py-2 pr-2 align-top">
                    <div class="font-bold">{{ item.product.name }}</div>
                    <div v-if="item.variant" class="text-xs text-gray-500">
                      {{ item.variant.name }}: {{ item.variant.option_name }}
                    </div>
                    <div
                      v-if="item.options && item.options.length > 0"
                      class="text-xs text-gray-500 mt-0.5"
                    >
                      <div v-for="(opt, idx) in item.options" :key="idx">
                        + {{ opt.group_name }}: {{ opt.option_name }}
                      </div>
                    </div>
                  </td>
                  <td class="py-2 text-center align-top">
                    {{ item.quantity }}
                  </td>
                  <td class="py-2 text-right align-top">
                    {{ calculateItemTotal(item) }}
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <div class="border-t border-dashed border-gray-300 pt-4 space-y-1">
            <div class="flex justify-between text-base font-bold">
              <span>{{ $t("receipt.total") }}</span>
              <span
                >{{ currencySymbol
                }}{{
                  receiptData.currency === "KHR"
                    ? formatAmount(
                        receiptData.cashReceived - receiptData.change,
                      )
                    : formatAmount(receiptData.total)
                }}</span
              >
            </div>
            <div
              v-if="receiptData.cashReceived"
              class="flex justify-between text-xs text-gray-600"
            >
              <span>{{ $t("receipt.cashReceived") }}</span>
              <span
                >{{ currencySymbol
                }}{{ formatAmount(receiptData.cashReceived) }}</span
              >
            </div>
            <div
              v-if="receiptData.change !== undefined"
              class="flex justify-between text-xs text-gray-600"
            >
              <span>{{ $t("receipt.change") }}</span>
              <span
                >{{ currencySymbol
                }}{{ formatAmount(receiptData.change) }}</span
              >
            </div>
          </div>

          <div class="mt-8 text-center text-xs">
            <p class="whitespace-pre-wrap">
              {{
                receiptData.receiptFooter ||
                "Thank you for visiting " +
                  (receiptData.shopName || "Lucky Cafe") +
                  "!"
              }}
            </p>
            <p
              v-if="receiptData.wifiSsid"
              class="mt-2 border-t border-dashed border-gray-200 pt-2"
            >
              <strong>{{ $t("settings.wifiSsid") }}:</strong>
              {{ receiptData.wifiSsid }} <br />
              <strong>{{ $t("receipt.password") }}:</strong>
              {{ receiptData.wifiPassword }}
            </p>
            <div class="mt-4 border-t pt-2 text-[10px] text-gray-400">
              {{ $t("receipt.poweredBy") }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </Teleport>
</template>

<style>
@media print {
  /* Hide the entire app */
  #app {
    display: none !important;
  }

  /* Reset body size/overscroll */
  body,
  html {
    height: auto !important;
    overflow: visible !important;
    background: white !important;
  }

  /* Force the modal wrapper to be visible */
  .receipt-modal-wrapper {
    position: absolute !important;
    top: 0 !important;
    left: 0 !important;
    width: 100% !important;
    height: auto !important;
    display: block !important;
    background: white !important;
    padding: 0 !important;
    margin: 0 !important;
  }

  /* Ensure content is strictly black and white */
  .receipt-content {
    width: 100% !important;
    /* max-width: 80mm !important; Optional: constrain width if needed */
    margin: 0 auto !important;
    color: black !important;
    background: white !important;
  }

  .receipt-content * {
    color: black !important;
    visibility: visible !important;
  }
}
</style>
