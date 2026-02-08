import { expect, test } from '@playwright/test';

test.describe('Sales POS Page', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/sales');
    });

    test('displays the sales page layout', async ({ page }) => {
        await expect(page.getByLabel('breadcrumb').getByText('Sale')).toBeVisible();

        // Should show the product dropdown trigger
        await expect(page.getByTestId('add-item-btn')).toBeVisible();

        // Should show the customer dropdown
        await expect(page.getByTestId('customer-dropdown-trigger')).toBeVisible();

        // Should show payment type buttons
        await expect(page.getByTestId('payment-cash-btn')).toBeVisible();
        await expect(page.getByTestId('payment-utang-btn')).toBeVisible();

        // Should show date and time inputs
        await expect(page.locator('#transactionDate')).toBeVisible();
        await expect(page.locator('#transactionTime')).toBeVisible();
    });

    test('shows empty cart state initially', async ({ page }) => {
        const emptyState = page.getByTestId('cart-empty-state');
        await expect(emptyState).toBeVisible();
        await expect(emptyState).toContainText('No items in cart');
    });

    test('can open product dropdown and search products', async ({ page }) => {
        await page.getByTestId('add-item-btn').click();

        // Product search input should be visible
        const searchInput = page.getByTestId('product-search-input');
        await expect(searchInput).toBeVisible();

        // Wait for products to load (spinner disappears)
        await expect(page.getByText('Searching...')).not.toBeVisible({ timeout: 5000 });

        // Try searching for a product
        await searchInput.fill('test');
        await page.waitForTimeout(400); // debounce is 300ms

        // Wait for search results to finish loading
        await expect(page.getByText('Searching...')).not.toBeVisible({ timeout: 5000 });

        // Either products are shown or "No products found" message appears
        const productOption = page.locator('[data-testid^="product-option-"]').first();
        const noResults = page.getByText('No products found.');
        const hasProducts = await productOption.isVisible().catch(() => false);
        const hasNoResults = await noResults.isVisible().catch(() => false);

        expect(hasProducts || hasNoResults).toBeTruthy();
    });

    test('can add a product to the cart', async ({ page }) => {
        // Open product dropdown
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        // Click the first available product
        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            const productName = await firstProduct.locator('.font-medium.truncate').textContent();
            await firstProduct.click();

            // Cart should no longer be empty
            await expect(page.getByTestId('cart-empty-state')).not.toBeVisible();

            // Product name should appear in the cart (desktop table)
            if (productName) {
                await expect(page.getByTestId('cart-table')).toContainText(productName.trim());
            }

            // Payment section should now be visible
            await expect(page.getByTestId('total-amount')).toBeVisible();
            await expect(page.getByTestId('checkout-btn')).toBeVisible();
        }
    });

    test('can remove a product from the cart', async ({ page }) => {
        // Add a product first
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Cart should have items
            await expect(page.getByTestId('cart-empty-state')).not.toBeVisible();

            // Find and click the remove button (X icon) in the cart table
            const removeButton = page.getByTestId('cart-table').getByRole('button').first();
            await removeButton.click();

            // Cart should be empty again
            await expect(page.getByTestId('cart-empty-state')).toBeVisible();
        }
    });

    test('can increment and decrement cart item quantity', async ({ page }) => {
        // Add a product
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            const cartTable = page.getByTestId('cart-table');
            await expect(cartTable).toBeVisible();

            // Get the quantity input in the cart table
            const qtyInput = cartTable.locator('input[type="number"]').first();
            await expect(qtyInput).toHaveValue('1');

            // Click the increment button (Plus icon)
            const incrementBtn = cartTable.locator('button').filter({ has: page.locator('svg') }).nth(1);
            await incrementBtn.click();
            await expect(qtyInput).toHaveValue('2');

            // Click the decrement button (Minus icon)
            const decrementBtn = cartTable.locator('button').filter({ has: page.locator('svg') }).first();
            await decrementBtn.click();
            await expect(qtyInput).toHaveValue('1');
        }
    });

    test('can open customer dropdown and search customers', async ({ page }) => {
        await page.getByTestId('customer-dropdown-trigger').click();

        const searchInput = page.getByTestId('customer-search-input');
        await expect(searchInput).toBeVisible();

        // Customer options should be listed
        const customerOption = page.locator('[data-testid^="customer-option-"]').first();
        const noCustomers = page.getByText('No customer found.');
        const hasCustomers = await customerOption.isVisible().catch(() => false);
        const hasNone = await noCustomers.isVisible().catch(() => false);

        expect(hasCustomers || hasNone).toBeTruthy();
    });

    test('can select a customer and view balance', async ({ page }) => {
        await page.getByTestId('customer-dropdown-trigger').click();

        const firstCustomer = page.locator('[data-testid^="customer-option-"]').first();

        if (await firstCustomer.isVisible()) {
            const customerName = await firstCustomer.textContent();
            await firstCustomer.click();

            // Customer name should be shown in the dropdown trigger
            if (customerName) {
                await expect(page.getByTestId('customer-dropdown-trigger')).toContainText(customerName.trim());
            }

            // Customer balance should be displayed
            const balanceDisplay = page.getByTestId('customer-balance-display');
            await expect(balanceDisplay).toBeVisible();
            await expect(balanceDisplay).toContainText('Outstanding Balance:');
        }
    });

    test('can clear selected customer', async ({ page }) => {
        // Select a customer first
        await page.getByTestId('customer-dropdown-trigger').click();
        const firstCustomer = page.locator('[data-testid^="customer-option-"]').first();

        if (await firstCustomer.isVisible()) {
            await firstCustomer.click();

            // Customer should be selected
            await expect(page.getByTestId('customer-balance-display')).toBeVisible();

            // Click the clear button (X icon inside the dropdown trigger)
            const clearBtn = page.getByTestId('customer-dropdown-trigger').getByTitle('Clear customer');
            await clearBtn.click();

            // Balance display should disappear
            await expect(page.getByTestId('customer-balance-display')).not.toBeVisible();
        }
    });

    test('can switch between cash and utang payment types', async ({ page }) => {
        // Default should be cash
        const cashBtn = page.getByTestId('payment-cash-btn');
        const utangBtn = page.getByTestId('payment-utang-btn');

        await expect(cashBtn).toHaveClass(/text-blue-600/);

        // Switch to utang
        await utangBtn.click();
        await expect(utangBtn).toHaveClass(/text-orange-600/);

        // Switch back to cash
        await cashBtn.click();
        await expect(cashBtn).toHaveClass(/text-blue-600/);
    });

    test('shows cash amount tendered and change fields', async ({ page }) => {
        // Add a product to reveal the payment section
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Cash payment section should show total, tendered, and change
            await expect(page.getByTestId('total-amount')).toBeVisible();
            await expect(page.getByTestId('amount-tendered-input')).toBeVisible();
            await expect(page.getByTestId('change-amount')).toBeVisible();
        }
    });

    test('shows utang payment fields when utang is selected', async ({ page }) => {
        // Add a product first
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Switch to utang
            await page.getByTestId('payment-utang-btn').click();

            // Should show paid amount input
            await expect(page.getByTestId('paid-amount-input')).toBeVisible();

            // Amount tendered (cash-only) should not be visible
            await expect(page.getByTestId('amount-tendered-input')).not.toBeVisible();
        }
    });

    test('checkout button is disabled without sufficient payment', async ({ page }) => {
        // Add a product
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Checkout button should be disabled (no amount tendered yet)
            const checkoutBtn = page.getByTestId('checkout-btn');
            await expect(checkoutBtn).toBeDisabled();

            // Should show a reason why it's disabled
            const reason = page.getByTestId('checkout-disabled-reason');
            await expect(reason).toBeVisible();
            await expect(reason).toContainText('Amount tendered');
        }
    });

    test('checkout button enables when sufficient cash is tendered', async ({ page }) => {
        // Add a product
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Get the total amount text to know how much to tender
            const totalText = await page.getByTestId('total-amount').textContent();
            // Parse the currency amount (remove non-numeric except dots)
            const totalAmount = parseFloat(totalText?.replace(/[^0-9.]/g, '') || '0');

            // Enter a sufficient amount tendered
            const tenderedInput = page.getByTestId('amount-tendered-input');
            await tenderedInput.click();
            await tenderedInput.fill('');
            await tenderedInput.type(String(Math.ceil(totalAmount)));

            // Checkout button should now be enabled
            const checkoutBtn = page.getByTestId('checkout-btn');
            await expect(checkoutBtn).toBeEnabled();
        }
    });

    test('utang checkout requires a customer to be selected', async ({ page }) => {
        // Add a product
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Switch to utang
            await page.getByTestId('payment-utang-btn').click();

            // Without customer, checkout should be disabled
            const checkoutBtn = page.getByTestId('checkout-btn');
            await expect(checkoutBtn).toBeDisabled();

            const reason = page.getByTestId('checkout-disabled-reason');
            await expect(reason).toContainText('Select a customer');
        }
    });

    test('can complete a cash sale', async ({ page }) => {
        // Add a product
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Get total amount
            const totalText = await page.getByTestId('total-amount').textContent();
            const totalAmount = parseFloat(totalText?.replace(/[^0-9.]/g, '') || '0');

            // Enter sufficient amount tendered
            const tenderedInput = page.getByTestId('amount-tendered-input');
            await tenderedInput.click();
            await tenderedInput.fill('');
            await tenderedInput.type(String(Math.ceil(totalAmount + 100)));

            // Checkout button should be enabled
            await expect(page.getByTestId('checkout-btn')).toBeEnabled();

            // Click checkout
            await page.getByTestId('checkout-btn').click();

            // Wait for the sale to process
            await page.waitForTimeout(2000);

            // Sale receipt modal should appear
            const receiptModal = page.getByTestId('sale-receipt-modal');
            await expect(receiptModal).toBeVisible();
            await expect(receiptModal).toContainText('Sale Completed!');

            // Close the modal
            await page.getByTestId('receipt-close-btn').click();
            await expect(receiptModal).not.toBeVisible();

            // Cart should be reset to empty
            await expect(page.getByTestId('cart-empty-state')).toBeVisible();
        }
    });

    test('change is calculated correctly', async ({ page }) => {
        // Add a product
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            await firstProduct.click();

            // Get the total amount
            const totalText = await page.getByTestId('total-amount').textContent();
            const totalAmount = parseFloat(totalText?.replace(/[^0-9.]/g, '') || '0');

            // Enter an amount higher than total
            const overpay = totalAmount + 500;
            const tenderedInput = page.getByTestId('amount-tendered-input');
            await tenderedInput.click();
            await tenderedInput.fill('');
            await tenderedInput.type(String(overpay));

            // Verify change shows the expected amount
            const changeText = await page.getByTestId('change-amount').textContent();
            const changeAmount = parseFloat(changeText?.replace(/[^0-9.]/g, '') || '0');

            // Change should equal overpay - totalAmount
            expect(changeAmount).toBeCloseTo(overpay - totalAmount, 0);
        }
    });

    test('adding same product again increases quantity', async ({ page }) => {
        await page.getByTestId('add-item-btn').click();
        await page.waitForTimeout(500);

        const firstProduct = page.locator('[data-testid^="product-option-"]').first();

        if (await firstProduct.isVisible()) {
            // Click the same product twice
            await firstProduct.click();

            // Open dropdown again and click same product
            await page.getByTestId('add-item-btn').click();
            await page.waitForTimeout(500);

            const sameProduct = page.locator('[data-testid^="product-option-"]').first();
            if (await sameProduct.isVisible()) {
                await sameProduct.click();

                // Quantity should be 2
                const cartTable = page.getByTestId('cart-table');
                const qtyInput = cartTable.locator('input[type="number"]').first();
                await expect(qtyInput).toHaveValue('2');
            }
        }
    });
});
