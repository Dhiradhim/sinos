/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./application/views/**/*.php",
    "./application/helpers/**/*.php",
    "./assets/js/**/*.js"
  ],
  theme: {
    extend: {
      colors: {
        // Palet merek SINOS: primer #0A2463, aksen merah #FB3640.
        brand: {
          50: "#eef2fb",
          100: "#dbe3f7",
          200: "#b9c7ee",
          300: "#8ea4e0",
          400: "#5d7dcc",
          500: "#3b5cb2",
          600: "#2b4795",
          700: "#1f3579",
          800: "#152a63",
          900: "#0a2463",
          950: "#071a45"
        },
        accent: {
          50: "#fff1f1",
          100: "#ffe0e1",
          200: "#ffc6c8",
          300: "#ff9ea2",
          400: "#ff656b",
          500: "#fb3640",
          600: "#e81c28",
          700: "#c4131e",
          800: "#a2141d",
          900: "#86171f"
        }
      },
      fontFamily: {
        sans: ["Inter", "ui-sans-serif", "system-ui", "-apple-system", "Segoe UI", "sans-serif"]
      },
      borderRadius: {
        xl: "0.75rem"
      },
      boxShadow: {
        card: "0 1px 2px 0 rgb(15 23 42 / 0.04), 0 1px 3px 0 rgb(15 23 42 / 0.06)"
      }
    }
  },
  plugins: [
    // preline v2 plugin (alias) — v3 pipeline only. Preline 5 has no Tailwind plugin.
    (() => { try { return require("preline2/plugin"); } catch (e) { return { handler() {} }; } })(),
  ]
};
