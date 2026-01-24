import apiClient from "./index";
import type { AxiosResponse } from "axios";

// Types
export interface GuestSession {
  session_token: string;
  table_id: number;
  table_number: string;
  shop_id: number;
  shop: {
    name: string;
    slug: string;
    logo_url: string | null;
  };
}

export interface CartItem {
  id: number;
  product_id: number;
  quantity: number;
  notes?: string;
  product: {
    id: number;
    name: string;
    price: number;
    image_url: string | null;
  };
}

export interface CartResponse {
  items: CartItem[];
  total: number;
  item_count: number;
  partial_order?: any;
}

// API Functions
export const guestApi = {
  /**
   * Scan QR code and create guest session
   */
  async scanTable(qrToken: string, sessionToken?: string): Promise<AxiosResponse<GuestSession>> {
    return apiClient.post(`/guest/scan/${qrToken}`, { session_token: sessionToken });
  },

  /**
   * Get menu for a shop
   */
  async getMenu(shopSlug: string): Promise<AxiosResponse<any>> {
    return apiClient.get(`/guest/menu/${shopSlug}`);
  },

  /**
   * Check if current IP allows cash payment
   */
  async checkAccess(shopSlug: string): Promise<AxiosResponse<any>> {
    return apiClient.get(`/guest/check-access/${shopSlug}`);
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
    sessionToken: string,
    config?: { skipLoading?: boolean },
  ): Promise<AxiosResponse<CartResponse>> {
    return apiClient.get(`/guest/cart/${sessionToken}`, config as any);
  },

  /**
   * Update cart item quantity
   */
  async updateCartItem(
    cartItemId: number,
    quantity: number,
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
    return apiClient.get(`/guest/order/${orderId}/status`, {
      skipLoading: true,
    } as any);
  },

  /**
   * Check status of SINGLE transaction (md5)
   */
  async checkStatusSingle(md5: string): Promise<AxiosResponse<any>> {
    return apiClient.post("/khqr/check-status-single", { md5 }, {
      skipLoading: true,
    } as any);
  },

  /**
   * Generate KHQR for Guest (Amount + Currency only, no Order ID yet)
   */
  async generateKhqr(
    amount: number,
    currency: "USD" | "KHR",
    sessionToken: string,
  ): Promise<AxiosResponse<any>> {
    return apiClient.post("/khqr/generate", { amount, currency, session_token: sessionToken });
  },

  /**
   * Finalize Order after KHQR Success
   */
  async finalizeKhqrOrder(
    sessionToken: string,
    md5: string,
  ): Promise<AxiosResponse<any>> {
    return apiClient.post("/guest/checkout/finalize-khqr", {
      session_token: sessionToken,
      khqr_md5: md5,
    });
  },

  /**
   * Finalize REMAINING Payment for Order
   */
  async finalizeOrderPayment(
    orderId: number,
    md5: string,
  ): Promise<AxiosResponse<any>> {
    return apiClient.post("/guest/checkout/finalize-payment", {
      order_id: orderId,
      khqr_md5: md5,
    });
  },
};

export default guestApi;
