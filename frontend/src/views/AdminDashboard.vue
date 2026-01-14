<script setup lang="ts">
import { onMounted, ref } from "vue";
import apiClient from "@/services/api";
import { useAuthStore } from "@/stores/auth";
import {
  Chart as ChartJS,
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler,
} from "chart.js";
import { Line } from "vue-chartjs";

ChartJS.register(
  CategoryScale,
  LinearScale,
  PointElement,
  LineElement,
  Title,
  Tooltip,
  Legend,
  Filler
);

const authStore = useAuthStore();
const stats = ref<any>(null);
const loading = ref(true);

const chartData = ref<any>({
  labels: [],
  datasets: [],
});

const chartOptions = {
  responsive: true,
  maintainAspectRatio: false,
  plugins: {
    legend: { display: false },
  },
  scales: {
    y: {
      beginAtZero: true,
      grid: { display: false },
    },
    x: {
      grid: { display: false },
    },
  },
};

onMounted(async () => {
  await fetchStats();
});

async function fetchStats() {
  try {
    const shopSlug = authStore.shop?.slug || "lucky-cafe";
    const response = await apiClient.get(`/staff/admin/${shopSlug}/stats`);
    stats.value = response.data;

    // Prepare chart data
    chartData.value = {
      labels: response.data.chart_data.map((d: any) => d.date),
      datasets: [
        {
          label: "Revenue",
          backgroundColor: "rgba(234, 88, 12, 0.1)",
          borderColor: "#ea580c",
          borderWidth: 3,
          data: response.data.chart_data.map((d: any) => d.revenue),
          fill: true,
          tension: 0.4,
        },
      ],
    };
  } catch (e) {
    console.error("Failed to fetch stats", e);
  } finally {
    loading.value = false;
  }
}

function formatCurrency(val: number) {
  return new Intl.NumberFormat("en-US", {
    style: "currency",
    currency: "USD",
  }).format(val);
}

function downloadReport() {
  if (!stats.value || !stats.value.chart_data) return;

  const headers = ["Date", "Revenue", "Orders"];
  const rows = stats.value.chart_data.map((d: any) => [
    d.date,
    d.revenue,
    d.count,
  ]);

  const csvContent = [
    headers.join(","),
    ...rows.map((row: any[]) => row.join(",")),
  ].join("\n");

  const blob = new Blob([csvContent], { type: "text/csv;charset=utf-8;" });
  const url = URL.createObjectURL(blob);
  const link = document.createElement("a");
  link.setAttribute("href", url);
  link.setAttribute(
    "download",
    `report_${new Date().toISOString().split("T")[0]}.csv`
  );
  link.style.visibility = "hidden";
  document.body.appendChild(link);
  link.click();
  document.body.removeChild(link);
}
</script>

<template>
  <div class="p-8">
    <div class="mb-8 flex justify-between items-end">
      <div>
        <h1 class="text-3xl font-bold text-app-text">Dashboard Overview</h1>
        <p class="text-app-muted">Welcome back, {{ authStore.user?.name }}</p>
      </div>
      <div class="flex gap-3">
        <button
          class="px-4 py-2 bg-app-surface border border-app-border rounded-xl text-sm font-bold text-app-muted hover:bg-app-bg hover:text-app-text transition-colors"
        >
          Last 7 Days
        </button>
        <button
          @click="downloadReport"
          class="px-4 py-2 bg-orange-600 rounded-xl text-sm font-bold text-white shadow-lg shadow-orange-200 hover:bg-orange-500 transition-all active:scale-95 flex items-center gap-2"
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
              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"
            />
          </svg>
          Download Report
        </button>
      </div>
    </div>

    <!-- Stats Grid -->
    <div v-auto-animate class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <template v-if="loading">
        <div
          v-for="i in 3"
          :key="i"
          class="h-32 bg-white rounded-3xl border border-gray-100 animate-pulse"
        ></div>
      </template>

      <template v-else-if="stats">
        <div
          class="bg-app-surface p-6 rounded-3xl border border-app-border shadow-sm hover:shadow-md transition-shadow"
        >
          <div class="flex items-center gap-4 mb-4">
            <div
              class="w-12 h-12 bg-green-500/10 text-green-600 rounded-2xl flex items-center justify-center"
            >
              <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                ></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-app-muted">Total Revenue</p>
              <h3 class="text-2xl font-bold text-app-text">
                {{ formatCurrency(stats.metrics.revenue) }}
              </h3>
            </div>
          </div>
          <div
            class="text-xs text-green-600 font-bold bg-green-500/10 px-2 py-1 rounded-lg inline-block"
          >
            +12% from last week
          </div>
        </div>

        <div
          class="bg-app-surface p-6 rounded-3xl border border-app-border shadow-sm"
        >
          <div class="flex items-center gap-4 mb-4">
            <div
              class="w-12 h-12 bg-blue-500/10 text-blue-600 rounded-2xl flex items-center justify-center"
            >
              <svg
                class="w-6 h-6"
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
            </div>
            <div>
              <p class="text-sm font-medium text-app-muted">Total Orders</p>
              <h3 class="text-2xl font-bold text-app-text">
                {{ stats.metrics.orders }}
              </h3>
            </div>
          </div>
          <div
            class="text-xs text-blue-600 font-bold bg-blue-500/10 px-2 py-1 rounded-lg inline-block"
          >
            +5 new today
          </div>
        </div>

        <div
          class="bg-app-surface p-6 rounded-3xl border border-app-border shadow-sm"
        >
          <div class="flex items-center gap-4 mb-4">
            <div
              class="w-12 h-12 bg-orange-500/10 text-orange-600 rounded-2xl flex items-center justify-center"
            >
              <svg
                class="w-6 h-6"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <path
                  stroke-linecap="round"
                  stroke-linejoin="round"
                  stroke-width="2"
                  d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"
                ></path>
              </svg>
            </div>
            <div>
              <p class="text-sm font-medium text-app-muted">Avg. Order Value</p>
              <h3 class="text-2xl font-bold text-app-text">
                {{ formatCurrency(stats.metrics.avg_order_value) }}
              </h3>
            </div>
          </div>
          <div
            class="text-xs text-orange-600 font-bold bg-orange-500/10 px-2 py-1 rounded-lg inline-block"
          >
            Steady growth
          </div>
        </div>
      </template>
    </div>

    <!-- Charts Section -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
      <!-- Revenue Chart -->
      <div
        class="lg:col-span-2 bg-app-surface p-8 rounded-[32px] border border-app-border shadow-sm h-96"
      >
        <h3 class="font-bold text-app-text mb-6 flex items-center gap-2">
          Revenue Trend
          <span class="text-xs font-normal text-app-muted font-mono"
            >(Last 7 Days)</span
          >
        </h3>
        <div class="h-64">
          <Line v-if="!loading" :data="chartData" :options="chartOptions" />
        </div>
      </div>

      <!-- Top Products -->
      <div
        class="bg-app-surface p-8 rounded-[32px] border border-app-border shadow-sm flex flex-col h-96"
      >
        <h3 class="font-bold text-app-text mb-6">Top Selling Items</h3>
        <div
          v-if="stats"
          v-auto-animate
          class="flex-1 space-y-6 overflow-y-auto pr-2"
        >
          <div
            v-for="product in stats.top_products"
            :key="product.name"
            class="flex items-center gap-4 group cursor-default"
          >
            <div
              class="w-10 h-10 rounded-xl bg-app-bg flex items-center justify-center font-bold text-app-muted text-xs group-hover:bg-orange-500/10 group-hover:text-orange-600 transition-colors"
            >
              {{ product.total_sold }}x
            </div>
            <div class="flex-1">
              <p
                class="text-sm font-bold text-app-text group-hover:text-orange-600 transition-colors"
              >
                {{ product.name }}
              </p>
              <p class="text-[10px] text-app-muted uppercase font-medium">
                {{ formatCurrency(product.total_revenue) }} revenue
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>
