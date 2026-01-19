import apiClient from "./index";
import type { AxiosResponse } from "axios";

// Types
export interface Order {
  id: number;
  order_number: string;
  shop_id: number;
  total_amount: number;
  payment_method: "cash" | "khqr";
  payment_status: "pending" | "paid" | "failed";
  payment_currency?: "USD" | "KHR";
  received_amount?: number;
  khqr_md5?: string;
  khqr_string?: string;
  items: OrderItem[];
  created_at: string;
  updated_at: string;
}

export interface OrderItem {
  id: number;
  product_id: number;
  quantity: number;
  price: number;
  subtotal: number;
  variant_price?: number;
  product?: Product;
}

export interface Product {
  id: number;
  name: string;
  price: number;
  image_url?: string;
  category_id?: number;
}

export interface CreateOrderPayload {
  shop_id: number;
  items: {
    product_id: number;
    quantity: number;
    price: number;
    variant_price?: number;
    options?: any[];
  }[];
  payment_method: "cash" | "khqr";
  payment_currency?: "USD" | "KHR";
  received_amount?: number;
}

// API Functions
export const orderApi = {
  /**
   * Create a new order (POS)
   */
  async create(
    payload: CreateOrderPayload
  ): Promise<AxiosResponse<{ success: boolean; order: Order }>> {
    return apiClient.post("/staff/orders", payload);
  },

  /**
   * Get orders list with filters
   */
  async getList(params: {
    shop_id: number;
    start_date?: string;
    end_date?: string;
    payment_status?: string;
    search?: string;
    page?: number;
  }): Promise<
    AxiosResponse<{
      data: Order[];
      current_page: number;
      last_page: number;
      total: number;
    }>
  > {
    return apiClient.get("/staff/orders", { params });
  },

  /**
   * Get single order details
   */
  async getById(orderId: number): Promise<AxiosResponse<Order>> {
    return apiClient.get(`/staff/orders/${orderId}`);
  },

  /**
   * Update payment status
   */
  async updatePaymentStatus(
    orderId: number,
    status: "paid" | "failed",
    metadata?: any
  ): Promise<AxiosResponse<{ success: boolean; order: Order }>> {
    return apiClient.put(`/staff/orders/${orderId}/payment-status`, {
      status,
      metadata,
    });
  },
};

export default orderApi;
