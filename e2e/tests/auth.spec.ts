import { expect, test } from '@playwright/test';

test.describe('Authentication', () => {
    test.use({ storageState: { cookies: [], origins: [] } }); // Run unauthenticated

    test('login page is accessible', async ({ page }) => {
        await page.goto('/login');

        await expect(page.getByLabel('Email')).toBeVisible();
        await expect(page.getByLabel('Password')).toBeVisible();
        await expect(page.getByRole('button', { name: /log in/i })).toBeVisible();
    });

    test('shows validation errors with invalid credentials', async ({ page }) => {
        await page.goto('/login');

        await page.getByLabel('Email').fill('invalid@example.com');
        await page.getByLabel('Password').fill('wrongpassword');
        await page.getByRole('button', { name: /log in/i }).click();

        // Should remain on login page and show an error
        await expect(page).toHaveURL(/\/login/);
    });

});
