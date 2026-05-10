import { test, expect } from '@playwright/test'

/**
 * Placeholder spec. Activate once examples/laravel-demo/ exists.
 *
 * Each scenario from the demo's dropdown should produce one screenshot
 * suitable for embedding in /docs-site/walkthrough/<scenario>.md. Naming
 * convention: ui/<scenario-slug>-<step>.png.
 */

test.describe.skip('xrechnung-demo walkthrough captures', () => {
  test('home page', async ({ page }) => {
    await page.goto('/')
    await expect(page).toHaveTitle(/xrechnung-demo/i)
    await page.screenshot({
      path: 'ui/home.png',
      fullPage: true,
    })
  })

  test('standard invoice scenario', async ({ page }) => {
    await page.goto('/scenarios/standard-invoice')
    await page.screenshot({
      path: 'ui/standard-invoice-form.png',
      fullPage: true,
    })

    await page.getByRole('button', { name: /generate/i }).click()
    await page.waitForSelector('[data-test="xml-output"]')
    await page.screenshot({
      path: 'ui/standard-invoice-output.png',
      fullPage: true,
    })
  })
})
