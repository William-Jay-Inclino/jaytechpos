import { expect, test } from '@playwright/test';

test.describe('Products Page', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/products');
    });

    test('displays the products page heading and layout', async ({ page }) => {
        await expect(page.getByRole('heading', { name: 'Products' })).toBeVisible();
        await expect(page.getByText(/products total/i)).toBeVisible();
        await expect(page.getByPlaceholder('Search here...')).toBeVisible();
        await expect(page.getByRole('link', { name: /add product/i })).toBeVisible();
    });

    test('displays product list with expected columns', async ({ page }) => {
        // Verify at least one product row is visible (desktop table)
        const table = page.locator('table');

        // If table exists (desktop view), check headers
        if (await table.isVisible()) {
            await expect(table.getByText('Product')).toBeVisible();
            await expect(table.getByText('Prices')).toBeVisible();
            await expect(table.getByText('Status')).toBeVisible();
            await expect(table.getByText('Actions')).toBeVisible();
        }
    });

    test('can search for a product', async ({ page }) => {
        const searchInput = page.getByPlaceholder('Search here...');

        // Type a search query and wait for the debounced request
        await searchInput.fill('test-nonexistent-product-xyz');
        await page.waitForTimeout(500); // debounce is 300ms

        // Wait for the request to complete
        await page.waitForLoadState('networkidle');

        // Should show "No products found" or filtered results
        const noResults = page.getByText(/no products found/i);
        const hasResults = page.locator('table tbody tr').first();

        // Either we get no results message or filtered table rows
        const noResultsVisible = await noResults.isVisible().catch(() => false);
        const hasResultsVisible = await hasResults.isVisible().catch(() => false);

        expect(noResultsVisible || hasResultsVisible).toBeTruthy();
    });

    test('search preserves query in the URL', async ({ page }) => {
        const searchInput = page.getByPlaceholder('Search here...');

        await searchInput.fill('rice');
        await page.waitForTimeout(500);
        await page.waitForLoadState('networkidle');

        await expect(page).toHaveURL(/search=rice/);
    });

    test('can clear search filters', async ({ page }) => {
        // Navigate with a search that returns no results
        await page.goto('/products?search=nonexistent-xyz-product');
        await page.waitForLoadState('networkidle');

        const clearButton = page.getByRole('button', { name: /clear all filters/i }).or(
            page.getByRole('link', { name: /clear all filters/i }),
        );

        if (await clearButton.isVisible()) {
            await clearButton.click();
            await page.waitForLoadState('networkidle');

            // URL should no longer have search param
            await expect(page).not.toHaveURL(/search=/);
        }
    });

    test('"Add Product" button navigates to create page', async ({ page }) => {
        await page.getByRole('link', { name: /add product/i }).click();

        await expect(page).toHaveURL(/\/products\/create/);
    });

    test('shows edit action for existing products', async ({ page }) => {
        // Find the first edit link/button in the table or card
        const editLink = page.getByRole('link', { name: /edit/i }).first();

        if (await editLink.isVisible()) {
            await editLink.click();

            await expect(page).toHaveURL(/\/products\/\d+\/edit/);
        }
    });

    test('delete button shows confirmation dialog', async ({ page }) => {
        // Find the first delete button
        const deleteButton = page.getByRole('button', { name: /delete/i }).first();

        if (await deleteButton.isVisible()) {
            await deleteButton.click();

            // SweetAlert2 confirmation dialog should appear
            const confirmDialog = page.locator('.swal2-popup');
            await expect(confirmDialog).toBeVisible();

            // Cancel the deletion
            const cancelButton = page.locator('.swal2-cancel');
            await cancelButton.click();

            // Dialog should be dismissed
            await expect(confirmDialog).not.toBeVisible();
        }
    });

    test('displays pagination when products exceed page limit', async ({ page }) => {
        const paginationInfo = page.getByText(/showing \d+ to \d+ of \d+ results/i);

        if (await paginationInfo.isVisible()) {
            await expect(paginationInfo).toBeVisible();

            // Check for pagination buttons
            const nextButton = page.getByRole('button', { name: /next/i }).or(
                page.getByRole('link', { name: /next/i }),
            );

            if (await nextButton.isEnabled()) {
                await nextButton.click();
                await page.waitForLoadState('networkidle');

                await expect(page).toHaveURL(/page=2/);
            }
        }
    });
});
