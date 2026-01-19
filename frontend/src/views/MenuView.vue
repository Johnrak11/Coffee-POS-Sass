<script setup lang="ts">
import { ref, onMounted, computed } from "vue";
import { useRoute } from "vue-router";
import { guestApi } from "@/api";
import { useCartStore } from "@/stores/cart";
import { useSessionStore } from "@/stores/session";
import ProductCustomizeModal from "@/components/ProductCustomizeModal.vue";

interface Product {
  id: number;
  name: string;
  price: number;
  image_url: string | null;
  category_id: number;
  variants?: any[];
}

interface Category {
  id: number;
  name: string;
  products: Product[];
}

import { toast } from "vue-sonner";

const route = useRoute();
const cartStore = useCartStore();
const sessionStore = useSessionStore();

const categories = ref<Category[]>([]);
const selectedCategory = ref<number | null>(null);
const loading = ref(true);
const showCart = ref(false);
const searchQuery = ref("");
const showCustomizeModal = ref(false);
const selectedProduct = ref<Product | null>(null);

const filteredProducts = computed(() => {
  let products: Product[] = [];
  if (!selectedCategory.value) {
    products = categories.value.flatMap((cat) => cat.products);
  } else {
    products =
      categories.value.find((c) => c.id === selectedCategory.value)?.products ||
      [];
  }

  if (searchQuery.value.trim()) {
    const q = searchQuery.value.toLowerCase();
    return products.filter((p) => p.name.toLowerCase().includes(q));
  }

  return products;
});

onMounted(async () => {
  const shopSlug = route.params.shopSlug as string;

  try {
    const response = await guestApi.getMenu(shopSlug);
    categories.value = response.data.categories || [];

    // Update shop session info if missing
    if (response.data.shop) {
      sessionStore.shopName = response.data.shop.name;
      sessionStore.shopSlug = response.data.shop.slug;
      localStorage.setItem("shop_name", response.data.shop.name);
      localStorage.setItem("shop_slug", response.data.shop.slug);
    }

    if (categories.value.length > 0 && categories.value[0]?.id) {
      selectedCategory.value = categories.value[0].id;
    }
  } catch (error) {
    console.error("Failed to load menu:", error);
  } finally {
    loading.value = false;
  }

  // Load cart
  await cartStore.fetchCart();
});

function handleProductClick(product: Product) {
  // If product has variants, show customize modal
  if (product.variants && product.variants.length > 0) {
    selectedProduct.value = product;
    showCustomizeModal.value = true;
  } else {
    // Direct add without customization
    addToCart(product);
  }
}

async function handleCustomizeAdd(data: any) {
  // Add product to cart (backend doesn't support options yet)
  const success = await cartStore.addItem(data.product.id, data.quantity);
  if (success) {
    showCustomizeModal.value = false;
    toast.success(`Added ${data.product.name} to cart`, {
      description: `Quantity: ${data.quantity}`,
      duration: 2000,
    });
  } else {
    toast.error("Failed to add to cart", {
      description: "Please try again",
    });
  }
}

async function addToCart(product: Product) {
  const success = await cartStore.addItem(product.id, 1);
  if (success) {
    toast.success(`Added ${product.name} to cart`, {
      description: "You can check your items in the cart.",
      duration: 2000,
    });
  }
}
</script>

<template>
  <div class="min-h-screen bg-gray-50 pb-24">
    <!-- Header -->
    <header class="glass-card sticky top-0 z-40 px-4 py-4">
      <div class="flex items-center justify-between">
        <div>
          <h1 class="text-xl font-display font-bold text-gray-900">
            {{ sessionStore.shopName }}
          </h1>
          <p class="text-sm text-gray-600">
            Table {{ sessionStore.tableNumber }}
          </p>
        </div>

        <!-- Cart Icon -->
        <button
          @click="showCart = true"
          class="relative bg-accent-500 text-white w-12 h-12 rounded-full shadow-medium hover:scale-110 active:scale-95 transition-all"
        >
          <svg
            class="w-6 h-6 mx-auto"
            fill="none"
            stroke="currentColor"
            viewBox="0 0 24 24"
          >
            <path
              stroke-linecap="round"
              stroke-linejoin="round"
              stroke-width="2"
              d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"
            />
          </svg>
          <span
            v-if="cartStore.itemCount > 0"
            class="absolute -top-1 -right-1 bg-red-500 text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"
          >
            {{ cartStore.itemCount }}
          </span>
        </button>
      </div>

      <!-- Search Bar -->
      <div class="mt-6 relative">
        <input
          v-model="searchQuery"
          type="text"
          placeholder="Search for coffee, tea..."
          class="w-full bg-gray-100 border-none rounded-2xl py-4 pl-12 pr-4 text-gray-900 placeholder-gray-400 focus:ring-2 focus:ring-primary-500 transition-all shadow-inner"
        />
        <svg
          class="w-5 h-5 text-gray-400 absolute left-4 top-1/2 -translate-y-1/2"
          fill="none"
          stroke="currentColor"
          viewBox="0 0 24 24"
        >
          <path
            stroke-linecap="round"
            stroke-linejoin="round"
            stroke-width="2"
            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"
          />
        </svg>
      </div>

      <!-- Category Tabs -->
      <div class="flex gap-2 mt-4 overflow-x-auto pb-2 hide-scrollbar">
        <button
          v-for="category in categories"
          :key="category.id"
          @click="selectedCategory = category.id"
          :class="[
            'px-4 py-2 rounded-xl font-medium whitespace-nowrap transition-all min-w-touch',
            selectedCategory === category.id
              ? 'bg-primary-600 text-white shadow-medium'
              : 'bg-white text-gray-700 hover:bg-gray-100',
          ]"
        >
          {{ category.name }}
        </button>
      </div>
    </header>

    <!-- Products Grid -->
    <main class="container mx-auto px-4 py-6">
      <div v-if="loading" class="grid grid-cols-2 gap-4">
        <div v-for="i in 6" :key="i" class="skeleton h-64 rounded-2xl"></div>
      </div>

      <div v-else v-auto-animate class="grid grid-cols-2 gap-4">
        <div
          v-for="product in filteredProducts"
          :key="product.id"
          class="product-card"
          @click="handleProductClick(product)"
        >
          <div class="aspect-square bg-gray-200 rounded-t-2xl overflow-hidden">
            <img
              v-if="product.image_url"
              :src="product.image_url"
              :alt="product.name"
              class="w-full h-full object-cover"
            />
            <div
              v-else
              class="w-full h-full flex items-center justify-center text-gray-400"
            >
              <svg class="w-16 h-16" fill="currentColor" viewBox="0 0 20 20">
                <path d="M4 3a2 2 0 100 4h12a2 2 0 100-4H4z" />
                <path
                  fill-rule="evenodd"
                  d="M3 8h14v7a2 2 0 01-2 2H5a2 2 0 01-2-2V8zm5 3a1 1 0 011-1h2a1 1 0 110 2H9a1 1 0 01-1-1z"
                  clip-rule="evenodd"
                />
              </svg>
            </div>
          </div>
          <div class="p-3">
            <h3 class="font-semibold text-gray-900 text-sm line-clamp-2">
              {{ product.name }}
            </h3>
            <p class="text-accent-600 font-bold mt-1">
              ${{ Number(product.price).toFixed(2) }}
            </p>
          </div>
        </div>
      </div>

      <div
        v-if="!loading && filteredProducts.length === 0"
        class="text-center py-12"
      >
        <p class="text-gray-500">No products available</p>
      </div>
    </main>

    <!-- Checkout FAB -->
    <router-link
      v-if="!cartStore.isEmpty"
      to="/checkout"
      class="fixed bottom-6 left-4 right-4 btn-accent text-center py-4 shadow-large"
    >
      View Cart · ${{ Number(cartStore.total).toFixed(2) }}
    </router-link>

    <!-- Product Customize Modal -->
    <ProductCustomizeModal
      :show="showCustomizeModal"
      :product="selectedProduct"
      @close="showCustomizeModal = false"
      @add-to-cart="handleCustomizeAdd"
    />
  </div>
</template>

<style scoped>
.hide-scrollbar::-webkit-scrollbar {
  display: none;
}

.product-card {
  background: white;
  border-radius: 1rem;
  box-shadow: 0 1px 3px 0 rgb(0 0 0 / 0.1);
  cursor: pointer;
  overflow: hidden;
  transition: all 0.2s;
}

.product-card:hover {
  box-shadow: 0 10px 15px -3px rgb(0 0 0 / 0.1);
}

.product-card:active {
  transform: scale(0.95);
}

.skeleton {
  background-color: rgb(229 231 235);
  animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
}

@keyframes pulse {
  0%,
  100% {
    opacity: 1;
  }
  50% {
    opacity: 0.5;
  }
}
</style>
