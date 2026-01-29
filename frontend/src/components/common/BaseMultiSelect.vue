<script setup lang="ts">
import { computed, ref, onMounted, onUnmounted } from "vue";

interface Option {
  value: any;
  label: string;
}

interface Props {
  modelValue: any[];
  options: Option[];
  label?: string;
  placeholder?: string;
}

const props = withDefaults(defineProps<Props>(), {
  modelValue: () => [],
  options: () => [],
  label: "",
  placeholder: "Select items...",
});

const emit = defineEmits<{
  "update:modelValue": [value: any[]];
}>();

const isOpen = ref(false);
const searchQuery = ref("");
const dropdownRef = ref<HTMLElement | null>(null);

const filteredOptions = computed(() => {
  if (!searchQuery.value) return props.options;
  return props.options.filter((opt) =>
    opt.label.toLowerCase().includes(searchQuery.value.toLowerCase()),
  );
});

const displayValue = computed(() => {
  if (props.modelValue.length === 0) return props.placeholder;
  if (props.modelValue.length === props.options.length) return "All selected";
  if (props.modelValue.length > 2)
    return `${props.modelValue.length} items selected`;

  return props.options
    .filter((opt) => props.modelValue.includes(opt.value))
    .map((opt) => opt.label)
    .join(", ");
});

function toggleOption(value: any) {
  const newValue = [...props.modelValue];
  const index = newValue.indexOf(value);

  if (index === -1) {
    newValue.push(value);
  } else {
    newValue.splice(index, 1);
  }

  emit("update:modelValue", newValue);
}

function handleOutsideClick(event: MouseEvent) {
  if (dropdownRef.value && !dropdownRef.value.contains(event.target as Node)) {
    isOpen.value = false;
  }
}

onMounted(() => {
  document.addEventListener("click", handleOutsideClick);
});

onUnmounted(() => {
  document.removeEventListener("click", handleOutsideClick);
});
</script>

<template>
  <div class="relative" ref="dropdownRef">
    <label
      v-if="label"
      class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1"
    >
      {{ label }}
    </label>

    <!-- Trigger -->
    <div
      @click="isOpen = !isOpen"
      class="w-full px-4 py-2 bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-xl cursor-pointer flex justify-between items-center hover:border-primary-500 transition-colors"
      :class="{ 'ring-2 ring-primary-500 border-primary-500': isOpen }"
    >
      <span
        class="truncate"
        :class="
          modelValue.length === 0
            ? 'text-gray-400 dark:text-gray-500'
            : 'text-gray-900 dark:text-white'
        "
      >
        {{ displayValue }}
      </span>
      <svg
        class="w-5 h-5 text-gray-400 transition-transform duration-200"
        :class="{ 'rotate-180': isOpen }"
        fill="none"
        stroke="currentColor"
        viewBox="0 0 24 24"
      >
        <path
          stroke-linecap="round"
          stroke-linejoin="round"
          stroke-width="2"
          d="M19 9l-7 7-7-7"
        />
      </svg>
    </div>

    <!-- Dropdown -->
    <div
      v-if="isOpen"
      class="absolute z-50 mt-2 w-full bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-100 dark:border-gray-700 max-h-60 flex flex-col overflow-hidden"
    >
      <!-- Search -->
      <div class="p-2 border-b border-gray-100 dark:border-gray-700 shrink-0">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search..."
          class="w-full px-3 py-1.5 bg-gray-50 dark:bg-gray-900 rounded-lg border border-gray-200 dark:border-gray-700 text-sm focus:outline-none focus:ring-1 focus:ring-primary-500"
          @click.stop
        />
      </div>

      <!-- Options -->
      <div class="overflow-y-auto flex-1 p-1">
        <div
          v-for="option in filteredOptions"
          :key="option.value"
          @click.stop="toggleOption(option.value)"
          class="flex items-center gap-3 px-3 py-2 hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded-lg cursor-pointer transition-colors"
        >
          <div
            class="w-5 h-5 rounded border flex items-center justify-center transition-colors"
            :class="
              modelValue.includes(option.value)
                ? 'bg-primary-600 border-primary-600'
                : 'border-gray-300 dark:border-gray-600'
            "
          >
            <svg
              v-if="modelValue.includes(option.value)"
              class="w-3.5 h-3.5 text-white"
              fill="none"
              stroke="currentColor"
              viewBox="0 0 24 24"
            >
              <path
                stroke-linecap="round"
                stroke-linejoin="round"
                stroke-width="3"
                d="M5 13l4 4L19 7"
              />
            </svg>
          </div>
          <span class="text-sm text-gray-700 dark:text-gray-200 truncate">{{
            option.label
          }}</span>
        </div>

        <div
          v-if="filteredOptions.length === 0"
          class="px-3 py-8 text-center text-sm text-gray-400"
        >
          No results found
        </div>
      </div>
    </div>
  </div>
</template>
