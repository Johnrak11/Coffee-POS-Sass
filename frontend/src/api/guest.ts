import apiClient from "./index";
import type { AxiosResponse } from "axios";

// Types
export interface GuestSession {
  session_token: string;
  table_id: number;
  shop_id: number;
}

export interface CartItem {
  id: number;
  product_id: number;
  quantity: number;
  notes?: string;
  product?: any;
}

// API Functions
export const guestApi = {
  /**
   * Scan QR code and create guest session
   */
  async scanTable(qrToken: string): Promise<AxiosResponse<GuestSession>> {
    return apiClient.post(`/guest/scan/${qrToken}`);
  },

  /**
   * Get menu for a shop
   */
  async getMenu(shopSlug: string): Promise<AxiosResponse<any>> {
    return apiClient.get(`/guest/menu/${shopSlug}`);
  },

  /**
   * Add item to cart
   */
  async addToCart(data: {
    session_token: string;
    product_id: number;
    quantity?: number;
    notes?: string;
  }): Promise<AxiosResponse<any>> {
    return apiClient.post("/guest/cart/add", data);
  },

  /**
   * Get cart contents
   */
  async getCart(
    sessionToken: string
  ): Promise<AxiosResponse<{ cart: CartItem[] }>> {
    return apiClient.get(`/guest/cart/${sessionToken}`);
  },

  /**
   * Update cart item quantity
   */
  async updateCartItem(
    cartItemId: number,
    quantity: number
  ): Promise<AxiosResponse<any>> {
    return apiClient.patch(`/guest/cart/${cartItemId}`, { quantity });
  },

  /**
   * Remove item from cart
   */
  async removeCartItem(cartItemId: number): Promise<AxiosResponse<any>> {
    return apiClient.delete(`/guest/cart/${cartItemId}`);
  },

  /**
   * Checkout and create order
   */
  async checkout(data: {
    session_token: string;
    payment_method: "cash" | "khqr";
  }): Promise<AxiosResponse<any>> {
    return apiClient.post("/guest/checkout", data);
  },

  /**
   * Get order status (for payment polling)
   */
  async getOrderStatus(orderId: number): Promise<AxiosResponse<any>> {
    return apiClient.get(`/guest/order/${orderId}/status`);
  },
};

export default guestApi;
