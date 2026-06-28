import { defineConfig } from 'vitest/config'
import { fileURLToPath, URL } from 'node:url'
import laravel from 'laravel-vite-plugin'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

const buildPlugins = process.env.VITEST
  ? []
  : [laravel({ input: ['resources/css/app.css', 'resources/js/app.tsx'], refresh: true })]

export default defineConfig({
  plugins: [...buildPlugins, react(), tailwindcss()],
  resolve: {
    alias: {
      '@': fileURLToPath(new URL('./resources/js', import.meta.url)),
    },
  },
  server: {
    watch: {
      ignored: ['**/storage/framework/views/**'],
    },
  },
  test: {
    environment: 'jsdom',
    globals: true,
    setupFiles: ['./resources/js/test/setup.ts'],
    include: ['resources/js/**/*.{test,spec}.{ts,tsx}'],
  },
})
