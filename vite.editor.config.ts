import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react-swc';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  build: {
    emptyOutDir: false,
    outDir: 'includes/modules/Editor/assets/js',
    lib: {
      entry: path.resolve(__dirname, 'src/editor-studio/main.tsx'),
      formats: ['iife'],
      name: 'CMEditorStudio',
      fileName: () => 'studio.js',
    },
  },
});
