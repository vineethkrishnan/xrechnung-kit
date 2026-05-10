import { defineConfig, devices } from '@playwright/test'

/**
 * Playwright config for capturing UI screenshots of the Laravel demo
 * (examples/laravel-demo/) for the documentation walkthrough.
 *
 * The demo does not exist yet; this config is in place so the wiring
 * is ready when the demo lands. See the matching spec file for the
 * placeholder test.
 */
export default defineConfig({
  testDir: './specs',
  outputDir: '../../docs-site/public/walkthrough/ui',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: 0,
  workers: 1,
  reporter: 'list',
  use: {
    baseURL: process.env.DEMO_URL ?? 'http://127.0.0.1:8000',
    viewport: { width: 1280, height: 800 },
    deviceScaleFactor: 2,
    colorScheme: 'light',
    locale: 'de-DE',
    timezoneId: 'Europe/Berlin',
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
  webServer: process.env.NO_WEBSERVER
    ? undefined
    : {
        command: 'php -S 127.0.0.1:8000 -t examples/laravel-demo/public',
        url: 'http://127.0.0.1:8000',
        reuseExistingServer: !process.env.CI,
        timeout: 30_000,
      },
})
