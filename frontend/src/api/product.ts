import apiClient from "./index";
import type { AxiosResponse } from "axios";

// Types
export interface Product {
  id: number;
  name: string;
  price: number;
  description?: string;
  image_url?: string;
  category_id: number;
  shop_id: number;
  is_available: boolean;
  created_at: string;
  updated_at: string;
  category?: Category;
}

export interface Category {
  id: number;
  name: string;
  shop_id: number;
  created_at: string;
  updated_at: string;
}

export interface CreateProductPayload {
  name: string;
  price: number;
  description?: string;
  image_url?: string;
  category_id: number;
  is_available?: boolean;
}

// API Functions
export const productApi = {
  /**
   * Get all products for a shop
   */
  async getList(shopSlug: string): Promise<AxiosResponse<Product[]>> {
    return apiClient.get(`/staff/admin/${shopSlug}/menu/products`);
  },

  /**
   * Create a new product
   */
  async create(
    shopSlug: string,
    payload: CreateProductPayload
  ): Promise<AxiosResponse<Product>> {
    return apiClient.post(`/staff/admin/${shopSlug}/menu/products`, payload);
  },

  /**
   * Update a product
   */
  async update(
    shopSlug: string,
    productId: number,
    payload: Partial<CreateProductPayload>
  ): Promise<AxiosResponse<Product>> {
    return apiClient.put(
      `/staff/admin/${shopSlug}/menu/products/${productId}`,
      payload
    );
  },

  /**
   * Delete a product
   */
  async delete(
    shopSlug: string,
    productId: number
  ): Promise<AxiosResponse<{ message: string }>> {
    return apiClient.delete(
      `/staff/admin/${shopSlug}/menu/products/${productId}`
    );
  },
};

export const categoryApi = {
  /**
   * Get all categories for a shop
   */
  async getList(shopSlug: string): Promise<AxiosResponse<Category[]>> {
    return apiClient.get(`/staff/admin/${shopSlug}/menu/categories`);
  },

  /**
   * Create a new category
   */
  async create(
    shopSlug: string,
    name: string
  ): Promise<AxiosResponse<Category>> {
    return apiClient.post(`/staff/admin/${shopSlug}/menu/categories`, { name });
  },

  /**
   * Update a category
   */
  async update(
    shopSlug: string,
    categoryId: number,
    name: string
  ): Promise<AxiosResponse<Category>> {
    return apiClient.put(
      `/staff/admin/${shopSlug}/menu/categories/${categoryId}`,
      { name }
    );
  },

  /**
   * Delete a category
   */
  async delete(
    shopSlug: string,
    categoryId: number
  ): Promise<AxiosResponse<{ message: string }>> {
    return apiClient.delete(
      `/staff/admin/${shopSlug}/menu/categories/${categoryId}`
    );
  },
};

export default { productApi, categoryApi };
