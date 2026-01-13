<script setup lang="ts">
import { ref } from "vue";
import { useRouter } from "vue-router";
import apiClient from "@/services/api";
import { toast } from "vue-sonner";
import BaseInput from "@/components/ui/BaseInput.vue";
import BaseButton from "@/components/ui/BaseButton.vue";

const router = useRouter();
const email = ref("");
const password = ref("");
const loading = ref(false);

async function handleLogin() {
  if (!email.value || !password.value) return;
  loading.value = true;
  try {
    const response = await apiClient.post(
      "/auth/super-admin",
      {
        email: email.value,
        password: password.value,
      }
    );

    if (response.data.success) {
      toast.success("Welcome Super Admin!");
      localStorage.setItem("staff_token", response.data.token);
      localStorage.setItem("staff_role", "super-admin");
      router.push("/super-admin");
    }
  } catch (e: any) {
    console.error(e);
    toast.error("Invalid Credentials");
  } finally {
    loading.value = false;
  }
}
</script>

<template>
  <div class="min-h-screen flex items-center justify-center bg-gray-900 p-6 relative overflow-hidden">
    <!-- Background Decor -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden z-0 opacity-20 pointer-events-none">
      <div class="absolute top-[-10%] left-[-10%] w-[50%] h-[50%] bg-purple-600 rounded-full blur-[150px]"></div>
      <div class="absolute bottom-[-10%] right-[-10%] w-[50%] h-[50%] bg-blue-600 rounded-full blur-[150px]"></div>
    </div>

    <div class="glass-card bg-gray-900 border border-gray-700 p-8 rounded-3xl w-full max-w-md shadow-2xl relative z-10">
      <h2 class="text-3xl font-display font-black text-white mb-2">
        Super Admin
      </h2>
      <p class="text-gray-400 mb-8">Secure Platform Access</p>

      <div class="space-y-4">
        <BaseInput v-model="email" label="Email Address" placeholder="admin@snaporder.com"
          class="bg-gray-800 border-gray-600 text-white focus:border-purple-500" />
        <BaseInput v-model="password" label="Password" type="password" placeholder="••••••••" @enter="handleLogin"
          class="bg-gray-800 border-gray-600 text-white focus:border-purple-500" />

        <BaseButton class="w-full mt-4 bg-purple-600 hover:bg-purple-500 shadow-purple-900/50" :loading="loading"
          @click="handleLogin" size="lg">
          Login to Dashboard
        </BaseButton>
      </div>
    </div>
  </div>
</template>
