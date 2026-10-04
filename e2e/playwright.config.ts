import { defineConfig, devices } from '@playwright/test';

/**
 * End-to-end tests run against the Dockerized app with a freshly seeded
 * database (see global-setup.ts). The suite shares one database, so tests
 * run serially.
 */
export default defineConfig({
  testDir: './tests',
  globalSetup: './global-setup.ts',
  fullyParallel: false,
  workers: 1,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  timeout: 60_000,
  expect: { timeout: 10_000 },
  reporter: process.env.CI ? [['html', { open: 'never' }], ['github']] : [['html', { open: 'never' }], ['list']],
  use: {
    baseURL: process.env.E2E_BASE_URL ?? 'http://localhost:8090',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    timezoneId: 'Asia/Manila',
    locale: 'en-PH',
  },
  projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'] } }],
});
