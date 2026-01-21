<script setup lang="ts">
import { ref } from "vue";
import { BaseInput, BaseButton } from "@/components/common";
import { useRouter } from "vue-router";

defineProps<{
  loading: boolean;
  error?: string;
}>();

const emit = defineEmits(["verify"]);

const slug = ref("");
const password = ref("");

function onVerify() {
  emit("verify", { slug: slug.value, password: password.value });
}
</script>

<template>
  <div class="w-full max-w-md z-10 transition-all duration-300">
    <div class="text-center mb-8">
      <h1 class="text-4xl font-display font-black text-white mb-2">
        Welcome Back
      </h1>
      <p class="text-gray-400">Secure Terminal Access</p>
    </div>

    <div
      class="glass-card bg-gray-800/80 backdrop-blur-xl p-8 rounded-[32px] border border-gray-700 shadow-2xl"
    >
      <!-- Shop ID Input -->
      <div class="mb-5">
        <BaseInput
          v-model="slug"
          label="Shop Identifier"
          placeholder="e.g. lucky-cafe"
          class="bg-gray-900/50 text-white border-gray-600 focus:border-primary-500"
        >
          <template #suffix>
            <span class="text-gray-500 font-medium select-none"
              >.snaporder.shop</span
            >
          </template>
        </BaseInput>
      </div>

      <!-- Shop Password Input -->
      <div class="mb-6">
        <BaseInput
          v-model="password"
          label="Terminal Password"
          type="password"
          placeholder="••••••••"
          @enter="onVerify"
          class="bg-gray-900/50 text-white border-gray-600 focus:border-primary-500"
        />
      </div>

      <div
        v-if="error"
        class="mb-6 p-4 bg-red-500/10 border border-red-500/20 rounded-xl flex items-center gap-3 text-red-200 text-sm"
      >
        <svg
          class="w-5 h-5 shrink-0"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
          />
        </svg>
        {{ error }}
      </div>

      <BaseButton
        variant="primary"
        block
        size="lg"
        :loading="loading"
        :disabled="!slug || loading"
        @click="onVerify"
      >
        Verify & Access
      </BaseButton>
    </div>
  </div>
</template>
