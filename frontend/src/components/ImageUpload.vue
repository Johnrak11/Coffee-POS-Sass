<script setup lang="ts">
import { ref, watch } from "vue";
import { toast } from "vue-sonner";

const props = defineProps<{
  modelValue?: string;
  folder?: string;
}>();

const emit = defineEmits(["update:modelValue", "fileSelected"]);

const selectedFile = ref<File | null>(null);
const preview = ref(props.modelValue || "");

// Watch for external changes to modelValue
watch(
  () => props.modelValue,
  (newVal) => {
    preview.value = newVal || "";
    if (!newVal) {
      selectedFile.value = null;
    }
  }
);

function handleFileChange(event: Event) {
  const target = event.target as HTMLInputElement;
  const file = target.files?.[0];
  if (!file) return;

  // Validate file type
  if (!file.type.startsWith("image/")) {
    toast.error("Please select an image file");
    return;
  }

  // Validate file size (5MB max)
  if (file.size > 5 * 1024 * 1024) {
    toast.error("Image must be less than 5MB");
    return;
  }

  // Store file and show preview
  selectedFile.value = file;
  preview.value = URL.createObjectURL(file);

  // Emit file to parent (parent will upload on save)
  emit("fileSelected", file);
  toast.success("Image selected - will upload when you save");
}

function removeImage() {
  selectedFile.value = null;
  preview.value = "";
  emit("update:modelValue", "");
  emit("fileSelected", null);
  toast.success("Image removed");
}
</script>

<template>
  <div class="space-y-3">
    <label class="block text-xs font-bold text-gray-400 uppercase"
      >Product Image</label
    >

    <!-- Preview -->
    <div
      v-if="preview"
      class="relative w-40 h-40 rounded-xl overflow-hidden border-2 border-gray-200 group"
    >
      <img :src="preview" class="w-full h-full object-cover" alt="Preview" />

      <!-- Overlay with Remove Button -->
      <div
        class="absolute inset-0 bg-black/0 group-hover:bg-black/50 transition-all flex items-center justify-center"
      >
        <button
          @click="removeImage"
          type="button"
          class="opacity-0 group-hover:opacity-100 transition-opacity p-2 bg-red-500 text-white rounded-lg hover:bg-red-600 flex items-center gap-2"
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
              d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"
            />
          </svg>
          Remove
        </button>
      </div>
    </div>

    <!-- Upload Button -->
    <div
      v-else
      class="w-full border-2 border-dashed border-gray-300 rounded-xl p-8 cursor-pointer hover:border-orange-500 hover:bg-orange-50/50 transition-all"
    >
      <label class="cursor-pointer flex flex-col items-center gap-3">
        <div
          class="w-16 h-16 rounded-full bg-orange-100 flex items-center justify-center"
        >
          <svg
            class="w-8 h-8 text-orange-600"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"
            />
          </svg>
        </div>
        <div class="text-center">
          <span class="text-sm font-medium text-gray-700 block"
            >Click to upload image</span
          >
          <span class="text-xs text-gray-500">or drag and drop</span>
        </div>
        <input
          type="file"
          accept="image/*"
          class="hidden"
          @change="handleFileChange"
        />
      </label>
    </div>

    <p class="text-xs text-gray-500">
      <span class="font-medium">Recommended:</span> Square image (1:1 ratio),
      max 5MB
    </p>
  </div>
</template>
