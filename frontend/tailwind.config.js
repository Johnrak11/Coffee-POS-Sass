/** @type {import('tailwindcss').Config} */
export default {
  darkMode: "class",
  content: ["./index.html", "./src/**/*.{vue,js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        // App Semantic Colors (Theming)
        app: {
          bg: "var(--color-app-bg)",
          sidebar: "var(--color-sidebar-bg)",
          surface: "var(--color-surface)",
          text: "var(--color-text-main)",
          muted: "var(--color-text-muted)",
          border: "var(--color-border)",
        },
        // Coffee-themed premium palette (Single Source of Truth)
        primary: {
          50: "#fdf8f6",
          100: "#f2e8e5",
          200: "#eaddd7",
          300: "#e0cec7",
          400: "#d2bab0",
          500: "#bfa094",
          600: "#a18072",
          700: "#977669",
          800: "#846358",
          900: "#43302b",
          950: "#2a1e1b", // Added deep coffee black
        },
        // Semantic Colors (replacing ad-hoc colors)
        success: "#10b981",
        warning: "#f59e0b",
        danger: "#ef4444",
        info: "#3b82f6",
        border: "#e2e8f0",
      },
      fontFamily: {
        sans: ["Inter", "system-ui", "sans-serif"],
        display: ["Outfit", "Inter", "sans-serif"],
      },
      boxShadow: {
        soft: "0 2px 15px -3px rgba(67, 48, 43, 0.07), 0 10px 20px -2px rgba(67, 48, 43, 0.04)",
        medium:
          "0 4px 20px -2px rgba(67, 48, 43, 0.1), 0 12px 30px -3px rgba(67, 48, 43, 0.08)",
        large:
          "0 10px 40px -5px rgba(67, 48, 43, 0.2), 0 20px 50px -8px rgba(67, 48, 43, 0.15)",
      },
      animation: {
        "fade-in": "fadeIn 0.3s ease-in-out",
        "slide-up": "slideUp 0.4s ease-out",
        "scale-in": "scaleIn 0.2s ease-out",
      },
      keyframes: {
        fadeIn: {
          "0%": { opacity: "0" },
          "100%": { opacity: "1" },
        },
        slideUp: {
          "0%": { transform: "translateY(20px)", opacity: "0" },
          "100%": { transform: "translateY(0)", opacity: "1" },
        },
        scaleIn: {
          "0%": { transform: "scale(0.95)", opacity: "0" },
          "100%": { transform: "scale(1)", opacity: "1" },
        },
      },
    },
  },
  plugins: [],
};
