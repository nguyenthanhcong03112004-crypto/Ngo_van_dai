import { defineConfig } from 'vitest/config';

export default defineConfig({
  test: {
    environment: 'node',
    include: ['../tests/frontend/Unit/**/*.test.{ts,tsx}'],
    globals: true,
    reporters: ['verbose'],
  },
});
