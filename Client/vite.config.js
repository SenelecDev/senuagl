import { fileURLToPath, URL } from "node:url";

import { defineConfig } from "vite";
import vue from "@vitejs/plugin-vue";
import vueDevTools from "vite-plugin-vue-devtools";

// https://vite.dev/config/
export default defineConfig({
  plugins: [vue(), vueDevTools()],
  resolve: {
    alias: {
      "@": fileURLToPath(new URL("./src", import.meta.url)),
    },
  },
  css: {
    preprocessorOptions: {
      scss: {
        additionalData: `@use "vuetify/styles" as *;`,
      },
    },
  },
  build: {
    chunkSizeWarningLimit: 1600,
  },
  server: {
    port: 5173,
    host: true,
    proxy: {
      '/api': {
        // 127.0.0.1 avoids occasional localhost DNS resolution issues on Windows.
        target: 'http://127.0.0.1:8081',
        changeOrigin: true,
        secure: false,
        timeout: 65000,
        proxyTimeout: 65000,
      },
    },
  },
});
