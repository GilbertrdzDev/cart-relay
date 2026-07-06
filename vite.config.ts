import { v4wp } from '@kucrut/vite-for-wp';

export default {
  plugins: [
    v4wp({
      input: {
        admin: 'resources/assets/admin/js/app-admin.js',
        front: 'resources/assets/front/js/app-front.js',
      },
      outDir: 'dist',
    }),
  ],
};
