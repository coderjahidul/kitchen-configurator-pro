// @ts-check
import { test } from '@playwright/test';

test('debug baseURL', async ({ page, baseURL }) => {
  console.log('baseURL fixture:', baseURL);
  await page.goto('/');
  console.log('page URL:', page.url());
  console.log('page title:', await page.title());
});
