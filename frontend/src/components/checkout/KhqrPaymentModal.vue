<script setup lang="ts">
import { ref, watch, onUnmounted } from "vue";
import { guestApi } from "@/api";
import { toast } from "vue-sonner";
import QrcodeVue from "qrcode.vue";

const props = defineProps<{
  modelValue: boolean;
  sessionToken: string;
  amount: number;
  shopName?: string;
  isPartial?: boolean;
  partialOrderId?: number;
}>();

const emit = defineEmits<{
  (e: "update:modelValue", value: boolean): void;
  (e: "success", result: any): void;
  (e: "close"): void;
}>();

const khqrString = ref<string | null>(null);
const khqrMd5 = ref<string | null>(null);
const khqrLoading = ref(false);
const pollingInterval = ref<any>(null);

function close() {
  stopPolling();
  emit("update:modelValue", false);
  emit("close");
}

function stopPolling() {
  if (pollingInterval.value) {
    clearInterval(pollingInterval.value);
    pollingInterval.value = null;
  }
}

watch(
  () => props.modelValue,
  (newVal) => {
    if (newVal) {
      generateKhqr();
    } else {
      stopPolling();
    }
  },
);

async function generateKhqr() {
  khqrLoading.value = true;
  khqrString.value = null;
  khqrMd5.value = null;

  try {
    const response = await guestApi.generateKhqr(
      props.amount,
      "USD",
      props.sessionToken,
    );

    const data = response.data.data || response.data;

    if (data && data.qr_string && data.md5) {
      khqrString.value = data.qr_string;
      khqrMd5.value = data.md5;
      startPolling();
    } else {
      throw new Error("Invalid KHQR response");
    }
  } catch (error: any) {
    if (error.response?.status === 404) {
      toast.error("Session Expired");
    } else {
      toast.error("Failed to generate QR Code");
    }
    close();
  } finally {
    khqrLoading.value = false;
  }
}

function startPolling() {
  stopPolling();
  pollingInterval.value = setInterval(async () => {
    if (!khqrMd5.value) return;

    try {
      const res = await guestApi.checkStatusSingle(khqrMd5.value);
      const data = res.data.data?.[0];
      if (data && data.responseCode === 0) {
        await handleSuccess();
      }
    } catch (e) {
      // ignore
    }
  }, 5000);
}

async function handleSuccess() {
  stopPolling();
  const loadingToast = toast.loading("Payment verified! Creating order...");
  emit("update:modelValue", false); // Close visual modal immediately

  try {
    let res;
    if (props.isPartial && props.partialOrderId) {
      res = await guestApi.finalizeOrderPayment(
        props.partialOrderId,
        khqrMd5.value!,
      );
    } else {
      res = await guestApi.finalizeKhqrOrder(
        props.sessionToken,
        khqrMd5.value!,
      );
    }

    if (res.data.success) {
      toast.dismiss(loadingToast);
      emit("success", res.data);
    } else {
      throw new Error(res.data.message || "Payment failed");
    }
  } catch (e: any) {
    toast.dismiss(loadingToast);
    toast.error("Failed to finalize order. Please contact staff.");
    // If failed, we might want to re-open modal?
    // For now let parent handle it via resume polling or error state.
  }
}

async function shareQrCode() {
  const canvas = document.querySelector("#khqr-canvas") as HTMLCanvasElement;
  if (!canvas) {
    toast.error("QR Code not ready");
    return;
  }

  canvas.toBlob(async (blob) => {
    if (!blob) return;
    const file = new File([blob], "khqr-payment.png", { type: "image/png" });
    const shareData = {
      files: [file],
      title: "Pay with KHQR",
      text: "Scan this QR code to pay",
    };

    if (navigator.canShare && navigator.canShare(shareData)) {
      try {
        await navigator.share(shareData);
      } catch (err: any) {
        if (err.name !== "AbortError") fallbackDownload(blob);
      }
    } else {
      fallbackDownload(blob);
    }
  });
}

function fallbackDownload(blob: Blob) {
  const link = document.createElement("a");
  link.href = URL.createObjectURL(blob);
  link.download = "khqr-payment.png";
  link.click();
  toast.success("QR Code saved to gallery");
}

onUnmounted(() => {
  stopPolling();
});
</script>

<template>
  <div
    v-if="modelValue"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
  >
    <div
      class="absolute inset-0 bg-black/80 backdrop-blur-sm transition-opacity"
    ></div>
    <div
      class="relative bg-white rounded-[32px] w-full max-w-sm overflow-hidden shadow-2xl animate-scale-in"
    >
      <!-- Header -->
      <div
        class="bg-[#E61F25] p-6 pb-8 text-center text-white relative rounded-t-[32px]"
      >
        <button
          @click="close"
          class="absolute right-4 top-4 p-2 bg-black/10 rounded-full hover:bg-black/20 z-10 transition-colors"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            viewBox="0 0 24 24"
            stroke="currentColor"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M6 18L18 6M6 6l12 12"
            />
          </svg>
        </button>

        <div class="flex justify-center">
          <h2 class="text-3xl font-black tracking-widest font-sans">KHQR</h2>
        </div>
      </div>

      <div
        class="px-8 pb-8 pt-6 flex flex-col items-center bg-white rounded-b-[32px]"
      >
        <div class="w-full mb-6">
          <div class="flex flex-col text-left">
            <p class="text-sm font-medium text-gray-500 mb-2">
              {{ shopName || "Coffee Shop" }}
            </p>

            <div class="flex items-baseline gap-2 mb-6">
              <span class="text-4xl font-bold text-gray-900">{{
                Number(amount).toLocaleString("en-US", {
                  minimumFractionDigits: 2,
                })
              }}</span>
              <span class="text-xl font-medium text-gray-500">USD</span>
            </div>

            <div
              class="border-b-2 border-dashed border-gray-200 w-full mb-6"
            ></div>
          </div>

          <!-- QR Code Area -->
          <div class="relative flex justify-center">
            <div
              v-if="khqrLoading"
              class="w-64 h-64 flex items-center justify-center bg-gray-50 rounded-2xl"
            >
              <div
                class="animate-spin rounded-full h-12 w-12 border-4 border-red-500 border-t-transparent"
              ></div>
            </div>

            <div v-else-if="khqrString" class="bg-white">
              <QrcodeVue
                id="khqr-canvas"
                :value="khqrString"
                :size="240"
                level="H"
                render-as="canvas"
                class="mx-auto"
              />

              <div
                class="absolute inset-0 flex items-center justify-center pointer-events-none"
              >
                <div class="bg-white p-1.5 rounded-full">
                  <div
                    class="w-9 h-9 bg-black rounded-full flex items-center justify-center text-white font-bold text-sm"
                  >
                    $
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Update Share Button -->
        <button
          @click="shareQrCode"
          class="flex items-center gap-2 px-6 py-3 bg-gray-50 text-gray-900 rounded-xl font-bold text-sm hover:bg-gray-100 transition-colors w-full justify-center"
        >
          <svg
            class="w-5 h-5"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"
            />
          </svg>
          Open with Bank
        </button>

        <div
          class="flex items-center gap-2 text-xs font-bold text-amber-600 bg-amber-50 px-3 py-1.5 rounded-full mt-4"
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
              d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"
            />
          </svg>
          QR code is for one-time use only
        </div>

        <div
          class="flex items-center gap-2 text-xs font-bold text-gray-400 animate-pulse mt-4"
        >
          <div class="w-2 h-2 bg-red-500 rounded-full"></div>
          Waiting for payment...
        </div>
      </div>

      <div class="p-4 bg-gray-50 border-t border-gray-100 text-center">
        <p class="text-xs text-gray-400">
          Please verify the Merchant Name matches exactly.
        </p>
      </div>
    </div>
  </div>
</template>
