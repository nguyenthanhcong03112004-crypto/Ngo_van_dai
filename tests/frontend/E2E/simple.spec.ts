import { test, expect } from '@playwright/test';

test('simple check', async ({ page }) => {
  await page.goto('/');
  await page.screenshot({ path: '../tests/simple-check.png' });
  await expect(page).toHaveTitle(/ElectroHub/i);
});
