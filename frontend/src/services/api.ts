import { config } from "@/config";
import axios from "axios";

const apiClient = axios.create({
  baseURL: config.apiBaseUrl,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

apiClient.interceptors.request.use((config) => {
  const token = localStorage.getItem("staff_token");
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

export const guestApi = {
  // Scan QR and create session
  scanTable: (qrToken: string) => apiClient.post(`/guest/scan/${qrToken}`),

  // Get menu
  getMenu: (shopSlug: string) => apiClient.get(`/guest/menu/${shopSlug}`),

  // Cart operations
  addToCart: (data: {
    session_token: string;
    product_id: number;
    quantity?: number;
    notes?: string;
  }) => apiClient.post("/guest/cart/add", data),

  getCart: (sessionToken: string) =>
    apiClient.get(`/guest/cart/${sessionToken}`),

  updateCartItem: (cartItemId: number, quantity: number) =>
    apiClient.patch(`/guest/cart/${cartItemId}`, { quantity }),

  removeCartItem: (cartItemId: number) =>
    apiClient.delete(`/guest/cart/${cartItemId}`),

  // Checkout
  checkout: (data: {
    session_token: string;
    payment_method: "cash" | "khqr";
  }) => apiClient.post("/guest/checkout", data),

  // Order status
  getOrderStatus: (orderId: number) =>
    apiClient.get(`/guest/order/${orderId}/status`),
};

export default apiClient;
