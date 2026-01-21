import { createRouter, createWebHistory } from "vue-router";
import type { RouteRecordRaw } from "vue-router";
import NProgress from "nprogress";

const routes: RouteRecordRaw[] = [
  {
    path: "/table/:qrToken",
    name: "TableScan",
    component: () => import("@/views/TableWelcome.vue"),
  },
  {
    path: "/menu/:shopSlug",
    name: "Menu",
    component: () => import("@/views/MenuView.vue"),
  },
  {
    path: "/checkout",
    name: "Checkout",
    component: () => import("@/views/CheckoutView.vue"),
  },
  {
    path: "/payment/:orderId",
    name: "Payment",
    component: () => import("@/views/PaymentView.vue"),
  },
  {
    path: "/success/:orderId",
    name: "Success",
    component: () => import("@/views/SuccessView.vue"),
  },
  {
    path: "/",
    redirect: "/menu/demo-shop",
  },
  {
    path: "/login",
    name: "login",
    component: () => import("../views/LoginView.vue"),
  },
  {
    path: "/super-login",
    name: "SuperAdminLogin",
    component: () => import("../views/SuperAdminLoginView.vue"),
  },
  {
    path: "/pos",
    component: () => import("../views/POSLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "pos-dashboard",
        component: () => import("../views/POSView.vue"),
      },
      {
        path: "orders",
        name: "pos-orders",
        component: () => import("../views/OrderHistoryView.vue"),
      },

    ],
  },
  {
    path: "/kitchen",
    component: () => import("../views/KitchenLayout.vue"),
    meta: { requiresAuth: true },
    children: [
      {
        path: "",
        name: "kitchen-display",
        component: () => import("../views/KitchenView.vue"),
      },
    ],
  },
  {
    path: "/admin",
    component: () => import("../views/AdminLayout.vue"),
    meta: { requiresAuth: true, role: "owner" },
    children: [
      {
        path: "",
        name: "admin-dashboard",
        component: () => import("../views/AdminDashboard.vue"),
      },
      {
        path: "transactions",
        name: "admin-transactions",
        component: () => import("../views/TransactionHistory.vue"),
      },
      {
        path: "products",
        name: "admin-products",
        component: () => import("../views/ProductListView.vue"),
      },
      {
        path: "categories",
        name: "admin-categories",
        component: () => import("../views/CategoryListView.vue"),
      },
      {
        path: "option-sets",
        name: "admin-option-sets",
        component: () => import("../views/OptionSetsView.vue"),
      },
      {
        path: "staff",
        name: "admin-staff",
        component: () => import("../views/StaffListView.vue"),
      },
      {
        path: "settings",
        name: "admin-settings",
        component: () => import("../views/SettingsView.vue"),
      },
      {
        path: "tables",
        name: "admin-tables",
        component: () => import("../views/TableListView.vue"),
      },
    ],
  },
  {
    path: "/super-admin",
    name: "SuperAdminDashboard",
    component: () => import("@/views/SuperAdminDashboard.vue"),
    // In a real app, add a "requiresAuth" and "role: super-admin" check
    // For now, we will trust the backend to reject non-super-admins if we implemented auth middleware there
  },
  {
    path: "/",
    redirect: "/login",
  },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

router.beforeEach((to, _from, next) => {
  NProgress.start();
  const isAuthenticated = localStorage.getItem("staff_token");
  const userRole = localStorage.getItem("staff_role");

  // Check authentication
  if (to.meta.requiresAuth && !isAuthenticated) {
    next("/login");
    return;
  }

  // Check role-based access for admin routes
  if (to.meta.role === "owner" && userRole !== "owner") {
    // Non-owners trying to access admin routes
    next("/pos");
    return;
  }

  next();
});

router.afterEach(() => {
  NProgress.done();
});

export default router;
