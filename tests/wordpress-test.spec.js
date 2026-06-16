// @ts-check
import { test, expect } from '@playwright/test';

test('WordPress homepage shows site title and navigates to Shop', async ({ page }) => {
  await page.goto('/');

  await expect(page).toHaveTitle(/configurator/i);

  const siteBranding = page.locator('.ast-site-identity');
  await expect(siteBranding).toBeVisible();
  await expect(siteBranding.locator('.custom-logo')).toBeVisible();

  const shopLink = page
    .getByRole('navigation', { name: /primary site navigation/i })
    .getByRole('link', { name: 'Shop' });
  await expect(shopLink).toBeVisible();
  await shopLink.click();

  await expect(page).toHaveURL(/\/shop\/?$/);
  await expect(page).toHaveTitle(/Shop/i);
});
