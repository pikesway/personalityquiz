import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  base: '',
  build: {
    outDir: '../dist',
    emptyOutDir: true,
    manifest: true,
    rollupOptions: {
      input: {
        main: './src/main.jsx'
      },
      // ADD THIS 'EXTERNAL' ARRAY
      // This tells Vite not to bundle these packages.
      external: ['@wordpress/api-fetch', '@wordpress/element'],
    }
  },
})