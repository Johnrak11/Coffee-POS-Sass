<script setup lang="ts">
import { ref, onMounted } from "vue";
import { useAuthStore } from "@/stores/auth";
import { useRouter } from "vue-router";
import apiClient from "@/services/api";

// Components
import ShopFinderStep from "./auth/components/ShopFinderStep.vue";
import StaffSelectionStep from "./auth/components/StaffSelectionStep.vue";
import PinPadStep from "./auth/components/PinPadStep.vue";

const authStore = useAuthStore();
const router = useRouter();

// State
const currentStep = ref<"find-shop" | "select-staff" | "pin">("find-shop");
const shopSlug = ref("");
const shopName = ref("");
const users = ref<any[]>([]);
const selectedUser = ref<any>(null);
const loading = ref(false);
const error = ref("");

onMounted(async () => {
  // Check Subdomain
  const hostname = window.location.hostname;
  const parts = hostname.split(".");
  if (parts.length > 1 && parts[0] !== "localhost" && parts[0] !== "www") {
    shopSlug.value = parts[0] || "";
  }
  // Check LocalStorage
  const savedSlug = localStorage.getItem("last_shop_slug");
  if (savedSlug) {
    shopSlug.value = savedSlug;
    // Auto-load staff list if shop slug is saved
    loading.value = true;
    try {
      const fetchedUsers = await authStore.getStaffList(savedSlug);
      if (fetchedUsers && fetchedUsers.length > 0) {
        users.value = fetchedUsers;
        currentStep.value = "select-staff";
        if (authStore.shop) {
          shopName.value = authStore.shop.name;
        }
      }
    } catch (e) {
      console.error(e);
      // Silently fail to shop finder
    } finally {
      loading.value = false;
    }
  }
});

async function onShopVerify(payload: { slug: string; password: string }) {
  loading.value = true;
  error.value = "";
  shopSlug.value = payload.slug;

  try {
    const response = await apiClient.post(
      "/shop/verify",
      payload
    );
    if (response.data.success) {
      shopName.value = response.data.shop.name;
      const fetchedUsers = await authStore.getStaffList(shopSlug.value);

      if (fetchedUsers && fetchedUsers.length > 0) {
        users.value = fetchedUsers;
        currentStep.value = "select-staff";
        localStorage.setItem("last_shop_slug", shopSlug.value);
      } else {
        error.value = "Shop verified but no staff found.";
      }
    }
  } catch (e: any) {
    if (e.response && e.response.status === 401)
      error.value = "Invalid Shop Password";
    else if (e.response && e.response.status === 404)
      error.value = "Shop not found";
    else error.value = "Connection failed";
  } finally {
    loading.value = false;
  }
}

function onStaffSelect(user: any) {
  selectedUser.value = user;
  currentStep.value = "pin";
  error.value = "";
}

async function onPinLogin(pin: string) {
  if (!selectedUser.value) return;
  loading.value = true;
  error.value = "";

  const success = await authStore.login(selectedUser.value.id, pin);
  if (success) {
    if (selectedUser.value.role === 'barista') {
      router.push("/kitchen");
    } else {
      router.push("/pos");
    }
  } else {
    error.value = "Invalid PIN Code";
  }
  loading.value = false;
}

function resetShop() {
  currentStep.value = "find-shop";
  users.value = [];
  selectedUser.value = null;
  error.value = "";
  localStorage.removeItem("last_shop_slug");
}
</script>

<template>
  <div
    class="min-h-screen flex flex-col items-center justify-center bg-gray-900 p-6 relative overflow-hidden text-balance">
    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 opacity-20 pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-purple-600 rounded-full blur-[150px]"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-600 rounded-full blur-[150px]"></div>
    </div>

    <!-- Container -->
    <div class="w-full max-w-5xl z-10">

      <!-- Initial Loading State -->
      <div v-if="initializing" class="flex justify-center items-center h-64">
        <div class="w-12 h-12 border-4 border-primary-500 border-t-transparent rounded-full animate-spin"></div>
      </div>

      <template v-else>
        <!-- Step 1 -->
        <div v-if="currentStep === 'find-shop'" class="flex justify-center">
          <ShopFinderStep :loading="loading" :error="error" @verify="onShopVerify" />
        </div>

        <!-- Step 2 & 3 -->
        <div v-else class="grid md:grid-cols-2 gap-8 items-start animate-fade-in">
          <!-- Left: Info & Staff List -->
          <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between">
              <div>
                <h2 class="text-lg font-bold text-gray-400">Login to</h2>
                <h1 class="text-3xl font-display font-black text-white">
                  {{ shopSlug }}
                </h1>
              </div>
              <button @click="authStore.logoutShop(); resetShop()"
                class="text-sm font-bold text-gray-500 hover:text-white bg-gray-800/50 px-4 py-2 rounded-xl transition-colors">
                Lock Terminal
              </button>
            </div>

            <StaffSelectionStep :users="users" :selectedUserId="selectedUser?.id" :dimmed="currentStep === 'pin'"
              @select="onStaffSelect" />
          </div>

          <!-- Right: PIN Pad -->
          <div v-if="currentStep === 'pin'">
            <PinPadStep :user="selectedUser" :loading="loading" :error="error" @login="onPinLogin" />
          </div>
        </div>
      </template>
    </div>
  </div>
</template>
