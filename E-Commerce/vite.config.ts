import react from '@vitejs/plugin-react';

export default {
  plugins: [react()],
  server: {
    host: '0.0.0.0',
    port: 5179,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8888',
        changeOrigin: true,
      },
    },
  },
  preview: {
    host: '0.0.0.0',
    port: 5181,
    proxy: {
      '/api': {
        target: 'http://127.0.0.1:8888',
        changeOrigin: true,
      },
    },
  },
};
