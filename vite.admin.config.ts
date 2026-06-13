import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

/**
 * Admin-only build: single IIFE bundle (no ES module imports).
 * WordPress admin loads this as a classic script — no type="module" required.
 */
export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@shared': resolve(__dirname, 'src/shared'),
    },
  },
  build: {
    outDir: 'assets/dist',
    emptyOutDir: false,
    manifest: false,
    rollupOptions: {
      input: resolve(__dirname, 'src/admin/main.tsx'),
      output: {
        format: 'iife',
        name: 'SLRAdmin',
        entryFileNames: 'admin.js',
        inlineDynamicImports: true,
        assetFileNames: 'admin/[name][extname]',
      },
    },
  },
});
