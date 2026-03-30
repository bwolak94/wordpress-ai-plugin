import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'path';

export default defineConfig({
  plugins: [react()],
  build: {
    outDir: 'assets/build',
    manifest: true,
    rollupOptions: {
      input: 'assets/src/main.tsx',
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'assets/src'),
    },
  },
  define: {
    'process.env.NODE_ENV': JSON.stringify(process.env.NODE_ENV),
  },
  server: {
    port: 5173,
    cors: true,
  },
  test: {
    globals: true,
    environment: 'jsdom',
    setupFiles: ['./assets/src/test/setup.ts'],
  },
});
