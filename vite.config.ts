import { defineConfig } from "vite";
import react from "@vitejs/plugin-react-swc";
import path from "path";
import { componentTagger } from "lovable-tagger";

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => ({
  server: {
    host: "::",
    port: 8080,
  },
  plugins: [
    react(),
    mode === 'development' &&
    componentTagger(),
  ].filter(Boolean),
  resolve: {
    alias: {
      "@": path.resolve(__dirname, "./src"),
    },
  },
  build: {
    outDir: "includes/modules/Editor/assets/js",
    emptyOutDir: false,
    rollupOptions: {
      input: path.resolve(__dirname, "src/story-studio.tsx"),
      output: {
        format: "iife",
        inlineDynamicImports: true,
        entryFileNames: "story-studio.js",
      },
    },
  },
}));
