import axios, {
  type AxiosInstance,
  type InternalAxiosRequestConfig,
} from "axios";
import NProgress from "nprogress";

const BASE_URL = import.meta.env.VITE_API_URL || "http://localhost:8001/api";

// Create axios instance
const apiClient: AxiosInstance = axios.create({
  baseURL: BASE_URL,
  timeout: 30000,
  headers: {
    "Content-Type": "application/json",
    Accept: "application/json",
  },
});

// Request interceptor
apiClient.interceptors.request.use(
  (config: InternalAxiosRequestConfig) => {
    // Start loading bar
    NProgress.start();

    // Add auth token if exists
    const token = localStorage.getItem("staff_token");
    if (token && config.headers) {
      config.headers.Authorization = `Bearer ${token}`;
    }

    return config;
  },
  (error) => {
    NProgress.done();
    return Promise.reject(error);
  },
);

// Response interceptor
apiClient.interceptors.response.use(
  (response) => {
    // Stop loading bar
    NProgress.done();
    return response;
  },
  (error) => {
    // Stop loading bar
    NProgress.done();

    // Handle specific error cases
    if (error.response) {
      switch (error.response.status) {
        case 401:
          // Unauthorized - clear auth and redirect to login
          localStorage.removeItem("staff_token");
          window.location.href = "/login";
          break;
        case 403:
          console.error("Forbidden access");
          break;
        case 404:
          console.error("Resource not found");
          break;
        case 500:
          console.error("Server error");
          break;
        default:
          console.error(
            "An error occurred:",
            error.response.data?.message || error.message,
          );
      }
    } else if (error.request) {
      console.error("No response received from server");
    } else {
      console.error("Error setting up request:", error.message);
    }

    return Promise.reject(error);
  },
);

export default apiClient;

// Re-export guestApi for customer pages
export { default as guestApi } from "./guest";
