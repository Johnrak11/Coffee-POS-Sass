<script setup lang="ts">
defineProps<{
  users: any[];
  selectedUserId?: number;
  dimmed?: boolean; // For when PIN pad is active
}>();

const emit = defineEmits(["select"]);
</script>

<template>
  <div class="space-y-4">
    <p class="text-gray-500 font-medium">Who is logging in?</p>

    <div
      class="grid grid-cols-2 gap-4 max-h-[60vh] overflow-y-auto pr-2 custom-scrollbar"
    >
      <button
        v-for="user in users"
        :key="user.id"
        @click="emit('select', user)"
        :class="[
          'p-4 rounded-2xl text-left transition-all duration-200 border flex items-center gap-4 group',
          selectedUserId === user.id
            ? 'bg-primary-600 border-primary-500 text-white shadow-lg ring-2 ring-primary-500/50'
            : 'bg-gray-800/40 border-gray-700 text-gray-300 hover:bg-gray-800 hover:border-gray-600',
          dimmed && selectedUserId !== user.id ? 'opacity-40 grayscale' : '',
        ]"
      >
        <div
          class="w-12 h-12 rounded-full flex items-center justify-center flex-shrink-0 transition-transform group-hover:scale-110"
          :class="[
            selectedUserId === user.id
              ? 'bg-white text-primary-600'
              : 'bg-gray-700 text-gray-400',
          ]"
        >
          <span class="text-xl font-bold">{{ user.name.charAt(0) }}</span>
        </div>
        <div>
          <h3 class="font-bold text-lg leading-tight">{{ user.name }}</h3>
          <p class="text-xs opacity-60 capitalize mt-0.5">
            {{ user.role }}
          </p>
        </div>
      </button>
    </div>
  </div>
</template>
