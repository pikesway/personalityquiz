import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

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
      // Externalize all WordPress dependencies and React
      external: [
        'react',
        'react-dom',
        '@wordpress/api-fetch',
        '@wordpress/element'
      ],
      output: {
        format: 'iife',
        globals: {
          'react': 'React',
          'react-dom': 'ReactDOM',
          '@wordpress/api-fetch': 'wp.apiFetch',
          '@wordpress/element': 'wp.element'
        },
        entryFileNames: 'assets/[name]-[hash].js'
      }
    }
  }
})