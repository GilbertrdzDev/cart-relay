import { v4wp } from '@kucrut/vite-for-wp';
import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';

export default defineConfig( {
  plugins: [
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
} );
