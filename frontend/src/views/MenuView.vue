<script setup lang="ts">
import { ref, onMounted, computed, onUnmounted } from "vue";
import { useRoute } from "vue-router";
import { useI18n } from "vue-i18n";
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
const { t } = useI18n();
const cartStore = useCartStore();
const sessionStore = useSessionStore();

const categories = ref<Category[]>([]);
const selectedCategory = ref<number | null>(null);
const loading = ref(true);
const searchQuery = ref("");
const showCustomizeModal = ref(false);
const selectedProduct = ref<Product | null>(null);

const isHeaderHidden = ref(false);
let lastScrollY = 0;
let scrollDelta = 0;

function handleScroll() {
  const currentScrollY = window.scrollY;
  const delta = currentScrollY - lastScrollY;

  // Update accumulated delta if direction matches
  if ((delta > 0 && scrollDelta < 0) || (delta < 0 && scrollDelta > 0)) {
    scrollDelta = 0;
  }
  scrollDelta += delta;

  // HIDE: Scrolling DOWN significantly (> 100px accumulated or instantaneous)
  if (currentScrollY > 100 && delta > 0 && scrollDelta > 60) {
    isHeaderHidden.value = true;
  }
  // SHOW: Scrolling UP significantly (< -30px accumulated) or near top
  else if (currentScrollY < 10 || (delta < 0 && scrollDelta < -30)) {
    isHeaderHidden.value = false;
  }

  lastScrollY = currentScrollY;
}

const searchResults = computed(() => {
  if (!searchQuery.value.trim()) return [];
  const q = searchQuery.value.toLowerCase();
  return categories.value
    .flatMap((cat) => cat.products)
    .filter((p) => p.name.toLowerCase().includes(q));
});

function scrollToCategory(categoryId: number) {
  selectedCategory.value = categoryId;
  const element = document.getElementById(`category-${categoryId}`);
  if (element) {
    // Reveal header first to ensure consistent offset
    isHeaderHidden.value = false;

    // Allow state update then scroll
    setTimeout(() => {
      // Offset = Header Full Height (~220px)
      const headerOffset = 220;
      const elementPosition = element.getBoundingClientRect().top;
      const offsetPosition =
        elementPosition + window.pageYOffset - headerOffset;

      window.scrollTo({
        top: offsetPosition,
        behavior: "smooth",
      });
    }, 50);
  }
}

// Scroll Spy with Intersection Observer
let observer: IntersectionObserver | null = null;

const pollInterval = ref<any>(null);

onMounted(async () => {
  window.addEventListener("scroll", handleScroll, { passive: true });

  const shopSlug = route.params.shopSlug as string;

  try {
    const response = await guestApi.getMenu(shopSlug);
    categories.value = response.data.categories || [];

    // Update shop session info if missing
    if (response.data.shop) {
      sessionStore.shopName = response.data.shop.name;
      sessionStore.shopSlug = response.data.shop.slug;
      if (response.data.shop.logo_url) {
        sessionStore.shopLogo = response.data.shop.logo_url;
        localStorage.setItem("shop_logo", response.data.shop.logo_url);
      }
      localStorage.setItem("shop_name", response.data.shop.name);
      localStorage.setItem("shop_slug", response.data.shop.slug);
    }

    if (categories.value.length > 0 && categories.value[0]?.id) {
      // Don't auto-set selectedCategory here to allow observer to do it,
      // BUT set it initially for the UI tab highlight if needed.
      selectedCategory.value = categories.value[0].id;
    }

    // Setup Spy
    // Wait for DOM
    setTimeout(() => {
      setupScrollSpy();
    }, 500);
  } catch (error) {
    // console.error("Failed to load menu:", error);
  } finally {
    loading.value = false;
  }

  // Load cart
  await cartStore.fetchCart();

  // START POLLING for multi-user updates
  // Refresh cart every 4 seconds to see friends' orders
  pollInterval.value = setInterval(() => {
    if (sessionStore.isActive) {
      cartStore.fetchCart(true);
    }
  }, 4000); // 4s interval
});

onUnmounted(() => {
  window.removeEventListener("scroll", handleScroll);
  if (pollInterval.value) clearInterval(pollInterval.value);
  if (observer) observer.disconnect();
});

function setupScrollSpy() {
  observer = new IntersectionObserver(
    (entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          const id = Number(entry.target.getAttribute("data-category-id"));
          if (id && !searchQuery.value) {
            // Only update if not searching
            selectedCategory.value = id;
            // Optional: scroll tab into view
            const tabBtn = document.getElementById(`tab-${id}`);
            if (tabBtn) {
              tabBtn.scrollIntoView({
                behavior: "smooth",
                block: "nearest",
                inline: "center",
              });
            }
          }
        }
      });
    },
    {
      rootMargin: "-20% 0px -50% 0px", // Trigger when element is near top
    },
  );

  document.querySelectorAll(".section-category").forEach((el) => {
    observer?.observe(el);
  });
}

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
  const success = await cartStore.addItem(data.product, data.quantity);
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
  const success = await cartStore.addItem(product, 1);
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
    <!-- Fixed Header Container -->
    <header
      class="glass-card fixed top-0 left-0 right-0 z-40 px-4 py-4 transition-transform duration-300 ease-in-out"
      :class="[isHeaderHidden ? '-translate-y-[120px]' : 'translate-y-0']"
    >
      <!-- Top Row: Logo + Info + Cart -->
      <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
          <img
            v-if="sessionStore.shopLogo"
            :src="sessionStore.shopLogo"
            alt="Logo"
            class="w-10 h-10 rounded-full object-cover shadow-sm bg-white"
          />
          <div>
            <h1
              class="text-xl font-display font-bold text-gray-900 leading-tight"
            >
              {{ sessionStore.shopName }}
            </h1>
            <p class="text-xs text-gray-500 font-medium">
              Table {{ sessionStore.tableNumber }}
            </p>
          </div>
        </div>

        <!-- Cart Icon -->
        <button
          @click="$router.push('/checkout')"
          class="relative bg-white text-gray-700 w-10 h-10 rounded-full shadow-sm border border-gray-100 flex items-center justify-center hover:bg-gray-50 active:scale-95 transition-all"
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
            class="absolute -top-1 -right-1 bg-[#3E2723] text-white text-xs w-5 h-5 rounded-full flex items-center justify-center font-bold"
          >
            {{ cartStore.itemCount }}
          </span>
        </button>
      </div>

      <!-- Search Bar -->
      <div class="mt-4 relative">
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
          :id="`tab-${category.id}`"
          @click="scrollToCategory(category.id)"
          :class="[
            'px-5 py-3 rounded-xl font-medium whitespace-nowrap transition-all min-w-touch text-base',
            selectedCategory === category.id
              ? 'bg-primary-600 text-white shadow-medium'
              : 'bg-white text-gray-700 hover:bg-gray-100',
          ]"
        >
          {{ category.name }}
        </button>
      </div>
    </header>

    <!-- Spacer to push content down below fixed header -->
    <div class="h-[220px]"></div>

    <!-- Products Grid (Continuous Scroll) -->
    <main class="container mx-auto px-4 py-6 space-y-8">
      <div v-if="loading" class="grid grid-cols-2 gap-4">
        <div v-for="i in 6" :key="i" class="skeleton h-64 rounded-2xl"></div>
      </div>

      <template v-else>
        <!-- Search Results Mode -->
        <div v-if="searchQuery.trim()">
          <h2 class="text-lg font-bold mb-4 text-gray-900">Search Results</h2>
          <div v-auto-animate class="grid grid-cols-2 gap-4">
            <div
              v-for="product in searchResults"
              :key="product.id"
              class="product-card"
              @click="handleProductClick(product)"
            >
              <div
                class="aspect-square bg-gray-200 rounded-t-2xl overflow-hidden"
              >
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
                  <svg
                    class="w-16 h-16"
                    fill="currentColor"
                    viewBox="0 0 20 20"
                  >
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
                <p class="text-primary-600 font-bold mt-1">
                  ${{ Number(product.price).toFixed(2) }}
                </p>
              </div>
            </div>
          </div>
          <div v-if="searchResults.length === 0" class="text-center py-12">
            <p class="text-gray-500">
              No products found matching "{{ searchQuery }}"
            </p>
          </div>
        </div>

        <!-- Continuous Category List -->
        <div v-else>
          <div
            v-for="category in categories"
            :key="category.id"
            :id="`category-${category.id}`"
            class="scroll-mt-64 mb-8 section-category"
            :data-category-id="category.id"
          >
            <h2 class="text-xl font-display font-bold text-gray-900 mb-4 py-2">
              {{ category.name }}
            </h2>
            <div class="grid grid-cols-2 gap-4">
              <div
                v-for="product in category.products"
                :key="product.id"
                class="product-card"
                @click="handleProductClick(product)"
              >
                <div
                  class="aspect-square bg-gray-200 rounded-t-2xl overflow-hidden"
                >
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
                    <svg
                      class="w-16 h-16"
                      fill="currentColor"
                      viewBox="0 0 20 20"
                    >
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
                  <p class="text-primary-600 font-bold mt-1">
                    ${{ Number(product.price).toFixed(2) }}
                  </p>
                </div>
              </div>
            </div>
            <div
              v-if="category.products.length === 0"
              class="text-center py-6 text-gray-400 italic text-sm"
            >
              No items in this category
            </div>
          </div>

          <div v-if="categories.length === 0" class="text-center py-12">
            <p class="text-gray-500">No categories available</p>
          </div>
        </div>
      </template>
    </main>

    <!-- Checkout FAB -->
    <router-link
      v-if="!cartStore.isEmpty"
      to="/checkout"
      class="fixed bottom-6 left-4 right-4 bg-[#3E2723] text-white text-center py-4 rounded-2xl shadow-xl shadow-[#3E2723]/30 font-bold text-lg flex justify-between px-6 items-center active:scale-[0.98] transition-transform z-50 hover:bg-[#4E342E]"
    >
      <span class="flex items-center gap-2">
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
            d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"
          ></path>
        </svg>
        {{ t("customer.viewCart") }}
      </span>
      <span class="bg-white/20 px-2 py-1 rounded-lg text-sm"
        >${{ Number(cartStore.total).toFixed(2) }}</span
      >
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
