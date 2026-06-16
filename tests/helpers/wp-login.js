// @ts-check

/**
 * @param {import('@playwright/test').Page} page
 * @param {{ username: string, password: string }} credentials
 */
export async function loginToWordPress(page, { username, password }) {
  await page.goto('/wp-login.php');
  await page.locator('#user_login').fill(username);
  await page.locator('#user_pass').fill(password);
  await page.locator('#wp-submit').click();
  await page.waitForURL((url) => !url.pathname.endsWith('/wp-login.php'));
}
