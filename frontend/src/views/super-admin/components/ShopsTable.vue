<script setup lang="ts">
import { BaseButton } from "@/components/common";

defineProps<{
  shops: any[];
}>();

const emit = defineEmits(["toggle-status", "edit"]);

function getStatusBadgeClass(status: string) {
  if (status === "active") return "bg-green-50 text-green-600 border-green-100";
  return "bg-red-50 text-red-600 border-red-100";
}
</script>

<template>
  <div
    class="bg-white rounded-[32px] border border-gray-100 shadow-sm overflow-hidden"
  >
    <div class="p-8 border-b border-gray-100 flex justify-between items-center">
      <h2 class="text-xl font-bold text-gray-900">All Shops</h2>
      <slot name="actions"></slot>
    </div>
    <div class="overflow-x-auto">
      <table class="w-full">
        <thead class="bg-gray-50 border-b border-gray-100">
          <tr>
            <th
              class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Shop Name
            </th>
            <th
              class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Owner
            </th>
            <th
              class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Plan
            </th>
            <th
              class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Status
            </th>
            <th
              class="px-8 py-4 text-left text-xs font-bold text-gray-400 uppercase tracking-wider"
            >
              Actions
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
          <tr
            v-for="shop in shops"
            :key="shop.id"
            class="hover:bg-gray-50/50 transition-colors"
          >
            <td class="px-8 py-6">
              <div class="flex items-center gap-4">
                <div
                  class="w-10 h-10 rounded-xl bg-orange-100 text-orange-600 flex items-center justify-center font-bold text-lg"
                >
                  {{ shop.name.charAt(0) }}
                </div>
                <div>
                  <div class="font-bold text-gray-900">{{ shop.name }}</div>
                  <div class="text-xs text-gray-400">ID: {{ shop.id }}</div>
                </div>
              </div>
            </td>
            <td class="px-8 py-6 text-gray-600 font-medium">
              {{ shop.owner }}
            </td>
            <td class="px-8 py-6">
              <span
                class="px-3 py-1 rounded-full text-xs font-bold bg-blue-50 text-blue-600 border border-blue-100 uppercase"
                >{{ shop.plan }}</span
              >
            </td>
            <td class="px-8 py-6">
              <span
                class="px-3 py-1 rounded-full text-xs font-bold border uppercase"
                :class="getStatusBadgeClass(shop.status)"
              >
                {{ shop.status }}
              </span>
            </td>
            <td class="px-8 py-6">
              <div class="flex gap-3">
                <BaseButton
                  size="sm"
                  :variant="shop.status === 'active' ? 'danger' : 'secondary'"
                  class="border"
                  @click="$emit('toggle-status', shop)"
                >
                  {{ shop.status === "active" ? "Disable" : "Enable" }}
                </BaseButton>
                <BaseButton
                  size="sm"
                  variant="outline"
                  @click="$emit('edit', shop)"
                >
                  Manage
                </BaseButton>
              </div>
            </td>
          </tr>
          <tr v-if="shops.length === 0">
            <td colspan="5" class="px-8 py-12 text-center text-gray-500">
              No shops found.
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>
