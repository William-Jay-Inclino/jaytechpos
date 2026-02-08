import { expect, test } from '@playwright/test';

test.describe('Dashboard', () => {
    test('authenticated user can access dashboard', async ({ page }) => {
        await page.goto('/');

        // Should not be redirected to login
        await expect(page).not.toHaveURL(/\/login/);
    });
});
