import apiClient from "./index";
import type { AxiosResponse } from "axios";

// Types
export interface KhqrGenerateResponse {
  qr_string: string;
  md5: string;
  payment_link: string;
  order_id?: string;
}

export interface KhqrStatusResponse {
  responseCode: number;
  responseMessage: string;
  errorCode?: string;
  data?: any;
}

// API Functions
export const khqrApi = {
  /**
   * Generate KHQR code for a transaction
   */
  async generate(
    amount: number,
    currency: "USD" | "KHR"
  ): Promise<AxiosResponse<KhqrGenerateResponse>> {
    return apiClient.post("/khqr/generate", { amount, currency });
  },

  /**
   * Regenerate KHQR for existing order
   */
  async regenerate(
    orderId: number
  ): Promise<AxiosResponse<KhqrGenerateResponse>> {
    return apiClient.post("/khqr/regenerate", { order_id: orderId });
  },

  /**
   * Check single transaction status
   */
  async checkStatusSingle(
    md5: string
  ): Promise<AxiosResponse<{ data: KhqrStatusResponse[] }>> {
    return apiClient.post("/khqr/check-status-single", { md5 });
  },

  /**
   * Check multiple transaction statuses (batch)
   */
  async checkStatusBatch(
    md5List: string[]
  ): Promise<AxiosResponse<{ data: any[] }>> {
    return apiClient.post("/khqr/check-status", { md5_list: md5List });
  },
};

export default khqrApi;
