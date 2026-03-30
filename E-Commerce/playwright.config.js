const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: 'D:\\Ngo_van_dai\\tests\\frontend\\E2E',
  timeout: 60000,
  retries: 0,
  reporter: [
    ['html', { open: 'never' }],
    ['list']
  ],
  use: {
    baseURL: 'http://127.0.0.1:5181',
    headless: false,
    video: 'on',
    screenshot: 'only-on-failure',
  },
  outputDir: '../tests/.playwright-raw-output',
  webServer: {
    command: 'npx vite preview --port 5181 --host 127.0.0.1',
    url: 'http://127.0.0.1:5181',
    reuseExistingServer: true,
    timeout: 120000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
