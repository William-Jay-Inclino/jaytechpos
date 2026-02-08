import { expect, test as setup } from '@playwright/test';

const authFile = 'e2e/.auth/user.json';

setup('authenticate', async ({ page }) => {
    await page.goto('/login');

    await page.getByLabel('Email').fill(process.env.E2E_USER_EMAIL || 'maria.santos@demo.com');
    await page.getByLabel('Password').fill(process.env.E2E_USER_PASSWORD || 'password');
    await page.getByRole('button', { name: /log in/i }).click();

    // Wait until redirected away from login page
    await expect(page).not.toHaveURL(/\/login/);

    // Save authenticated state for reuse across tests
    await page.context().storageState({ path: authFile });
});
