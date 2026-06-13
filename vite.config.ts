import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import { resolve } from 'path';

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@shared': resolve(__dirname, 'src/shared'),
    },
  },
  build: {
    outDir: 'assets/dist',
    emptyOutDir: true,
    manifest: 'manifest.json',
    rollupOptions: {
      input: {
        'src/frontend/main-popup.tsx': resolve(__dirname, 'src/frontend/main-popup.tsx'),
        'src/frontend/main-page.tsx': resolve(__dirname, 'src/frontend/main-page.tsx'),
        'src/frontend/bootstrap.ts': resolve(__dirname, 'src/frontend/bootstrap.ts'),
      },
      output: {
        entryFileNames: (chunk) => {
          if (chunk.name.includes('bootstrap')) return 'frontend/bootstrap.js';
          if (chunk.name.includes('main-popup')) return 'frontend/popup.js';
          if (chunk.name.includes('main-page')) return 'frontend/page.js';
          return '[name].js';
        },
        chunkFileNames: 'chunks/[name]-[hash].js',
        assetFileNames: (assetInfo) => {
          const name = assetInfo.name || '';
          if (name.endsWith('.css')) {
            if (name.includes('admin')) return 'admin/[name][extname]';
            return 'frontend/[name][extname]';
          }
          return 'assets/[name][extname]';
        },
      },
    },
  },
  server: {
    port: 5173,
    strictPort: true,
    cors: true,
    origin: 'http://localhost:5173',
    // Allow WordPress admin (e.g. bhashaskool.local) to load scripts from the dev server.
    headers: {
      'Access-Control-Allow-Origin': '*',
    },
  },
});
