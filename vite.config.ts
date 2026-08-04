import { v4wp } from '@kucrut/vite-for-wp';
import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig( {
  build: {
    sourcemap: false,
  },
  plugins: [
    tailwindcss(),
    v4wp({
      input: {
        admin: 'resources/assets/admin/js/app-admin.ts',
        front: 'resources/assets/front/js/app-front.ts',
      },
      outDir: 'dist',
    }),
  ],
  resolve: {
    alias: {
      '@front': fileURLToPath( new URL( './resources/assets/front/js', import.meta.url ) ),
      '@admin': fileURLToPath( new URL( './resources/assets/admin/js', import.meta.url ) ),
      '@helpers': fileURLToPath( new URL( './resources/helpers', import.meta.url ) ),
      '@styles': fileURLToPath( new URL( './resources/assets', import.meta.url ) ),
    },
  },
  server: {
    host: 'localhost',
    port: 5173,
    strictPort: true,
    cors: {
      origin: [
        'https://wptest.test',
        'http://wptest.test',
        'http://localhost',
        'http://127.0.0.1',
      ],
      credentials: true,
    },
    hmr: {
      host: 'localhost',
      protocol: 'ws',
    },
  },
} );
