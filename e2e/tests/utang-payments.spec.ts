import { expect, test } from '@playwright/test';

test.describe('Utang Payments Page', () => {
    test.beforeEach(async ({ page }) => {
        await page.goto('/utang-payments');
        await page.waitForLoadState('networkidle');
    });

    test('displays the utang payments page layout', async ({ page }) => {
        // Verify page title
        await expect(page).toHaveTitle(/Utang Payment/);

        // Payment form card should be visible
        await expect(page.getByTestId('payment-form-card')).toBeVisible();

        // Customer dropdown should be present
        await expect(page.getByTestId('customer-dropdown-trigger')).toBeVisible();

        // Submit button should be visible but disabled (no customer selected)
        const submitBtn = page.getByTestId('submit-payment-btn');
        await expect(submitBtn).toBeVisible();
        await expect(submitBtn).toBeDisabled();
    });

    test('customer dropdown opens and shows search input', async ({ page }) => {
        await page.getByTestId('customer-dropdown-trigger').click();

        const dropdownList = page.getByTestId('customer-dropdown-list');
        await expect(dropdownList).toBeVisible();

        const searchInput = page.getByTestId('customer-search-input');
        await expect(searchInput).toBeVisible();
        await expect(searchInput).toHaveAttribute('placeholder', 'Enter name of customer...');
    });

    test('customer dropdown filters customers by search', async ({ page }) => {
        await page.getByTestId('customer-dropdown-trigger').click();

        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('nonexistent-customer-xyz-123');

        // Should show "No customer found" message
        await expect(page.getByText('No customer found.')).toBeVisible();
    });

    test('can select a customer from the dropdown', async ({ page }) => {
        await page.getByTestId('customer-dropdown-trigger').click();

        // Search for a known customer with utang
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);

        // Click the first matching customer option
        const customerOption = page.locator('[data-testid^="customer-option-"]').first();
        await expect(customerOption).toBeVisible();
        await customerOption.click();

        // Dropdown should close
        await expect(page.getByTestId('customer-dropdown-list')).not.toBeVisible();

        // Selected customer name should appear in the trigger
        await expect(page.getByTestId('selected-customer-name')).not.toHaveText('---');

        // Balance display should appear (may show loading first)
        await expect(page.getByTestId('balance-display')).toBeVisible();

        // Wait for balance to finish loading
        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });
    });

    test('shows outstanding balance and interest rate after selecting customer', async ({ page }) => {
        // Select a customer with utang
        await page.getByTestId('customer-dropdown-trigger').click();
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        // Wait for balance to load
        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });

        // Balance should show a currency value
        const balanceText = await page.getByTestId('balance-value').textContent();
        expect(balanceText?.trim()).toBeTruthy();

        // Interest rate should be visible
        await expect(page.getByTestId('interest-rate')).toBeVisible();
        await expect(page.getByTestId('interest-rate')).toContainText('Interest Rate:');
        await expect(page.getByTestId('interest-rate')).toContainText('%');
    });

    test('can clear selected customer', async ({ page }) => {
        // Select a customer first
        await page.getByTestId('customer-dropdown-trigger').click();
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        // Customer should be selected
        await expect(page.getByTestId('selected-customer-name')).not.toHaveText('---');

        // Clear the customer
        await page.getByTestId('clear-customer-btn').click();

        // Should reset to placeholder
        await expect(page.getByTestId('selected-customer-name')).toHaveText('---');

        // Balance display should be hidden
        await expect(page.getByTestId('balance-display')).not.toBeVisible();
    });

    test('view history button shows transaction history panel', async ({ page }) => {
        // Select a customer with utang
        await page.getByTestId('customer-dropdown-trigger').click();
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        // Wait for balance to load
        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });

        // Click View History button
        await page.getByTestId('view-history-btn').click();

        // Transaction history panel should appear
        await expect(page.getByTestId('transaction-history-panel')).toBeVisible({ timeout: 10000 });

        // Wait for loading to complete
        await expect(page.getByTestId('transaction-history-loading')).not.toBeVisible({ timeout: 10000 });

        // Should show either transaction cards or no-transactions state
        const hasTransactions = await page.getByTestId('transaction-list').isVisible().catch(() => false);
        const noTransactions = await page.getByTestId('no-transactions').isVisible().catch(() => false);
        expect(hasTransactions || noTransactions).toBeTruthy();
    });

    test('transaction history shows transaction cards when customer has transactions', async ({ page }) => {
        // Select a customer known to have utang (and therefore transactions)
        await page.getByTestId('customer-dropdown-trigger').click();
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });

        // Click View History
        await page.getByTestId('view-history-btn').click();
        await expect(page.getByTestId('transaction-history-panel')).toBeVisible({ timeout: 10000 });

        // Wait for loading to complete
        await expect(page.getByTestId('transaction-history-loading')).not.toBeVisible({ timeout: 10000 });

        // If the customer has transactions, verify the cards
        const transactionList = page.getByTestId('transaction-list');
        if (await transactionList.isVisible()) {
            const cards = page.getByTestId('transaction-card');
            const count = await cards.count();
            expect(count).toBeGreaterThan(0);
        }
    });

    test('submit button is disabled without required fields', async ({ page }) => {
        const submitBtn = page.getByTestId('submit-payment-btn');

        // Initially disabled (no customer, no amount)
        await expect(submitBtn).toBeDisabled();
    });

    test('submit button remains disabled when payment amount exceeds balance', async ({ page }) => {
        // Select a customer with utang
        await page.getByTestId('customer-dropdown-trigger').click();
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        // Wait for balance to load
        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });

        // Enter an excessively large payment amount
        const amountInput = page.locator('#payment_amount');
        await amountInput.fill('9999999');

        // Error message should appear
        await expect(page.getByTestId('payment-amount-error')).toBeVisible();
        await expect(page.getByTestId('payment-amount-error')).toContainText('cannot exceed');

        // Submit should remain disabled
        await expect(page.getByTestId('submit-payment-btn')).toBeDisabled();
    });

    test('shows remaining balance when valid payment amount is entered', async ({ page }) => {
        // Select a customer with utang
        await page.getByTestId('customer-dropdown-trigger').click();
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        // Wait for balance to load
        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });

        // Enter a valid small payment amount
        const amountInput = page.locator('#payment_amount');
        await amountInput.fill('1');

        // Remaining balance should be shown
        await expect(page.getByTestId('remaining-balance')).toBeVisible();
        await expect(page.getByTestId('remaining-balance')).toContainText('Remaining balance:');
    });

    test('submit button enables with valid form data', async ({ page }) => {
        // Select a customer with utang
        await page.getByTestId('customer-dropdown-trigger').click();
        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        // Wait for balance to load
        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });

        // Enter a valid payment amount
        const amountInput = page.locator('#payment_amount');
        await amountInput.fill('1');

        // Date should already be populated with current date
        const dateInput = page.locator('#payment_date');
        await expect(dateInput).not.toHaveValue('');

        // Submit button should now be enabled
        await expect(page.getByTestId('submit-payment-btn')).toBeEnabled();
    });

    test('can fill optional notes field', async ({ page }) => {
        const notesField = page.locator('#notes');
        await expect(notesField).toBeVisible();

        await notesField.fill('Test payment note');
        await expect(notesField).toHaveValue('Test payment note');
    });

    test('payment date field is pre-populated', async ({ page }) => {
        const dateInput = page.locator('#payment_date');
        await expect(dateInput).toBeVisible();

        // Should have a pre-populated value (current datetime)
        const dateValue = await dateInput.inputValue();
        expect(dateValue).toBeTruthy();
        expect(dateValue.length).toBeGreaterThan(0);
    });

    test('no customer found shows add customer button', async ({ page }) => {
        await page.getByTestId('customer-dropdown-trigger').click();

        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('zzzz-nonexistent-customer-xyzabc');

        await expect(page.getByText('No customer found.')).toBeVisible();

        const addCustomerBtn = page.getByRole('button', { name: /add customer/i });
        await expect(addCustomerBtn).toBeVisible();
    });

    test('add customer button navigates to customer creation page', async ({ page }) => {
        await page.getByTestId('customer-dropdown-trigger').click();

        const searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('zzzz-nonexistent-customer-xyzabc');

        await expect(page.getByText('No customer found.')).toBeVisible();

        const addCustomerBtn = page.getByRole('button', { name: /add customer/i });
        await addCustomerBtn.click();

        await expect(page).toHaveURL(/\/customers\/create/);
    });

    test('dropdown closes when clicking outside', async ({ page }) => {
        // Open dropdown
        await page.getByTestId('customer-dropdown-trigger').click();
        await expect(page.getByTestId('customer-dropdown-list')).toBeVisible();

        // Click outside the dropdown
        await page.locator('body').click({ position: { x: 10, y: 10 } });

        // Dropdown should close
        await expect(page.getByTestId('customer-dropdown-list')).not.toBeVisible();
    });

    test('selecting a different customer hides transaction history', async ({ page }) => {
        // Select first customer
        await page.getByTestId('customer-dropdown-trigger').click();
        let searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Daniel');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        await expect(page.getByTestId('balance-value')).toBeVisible({ timeout: 10000 });

        // Open transaction history
        await page.getByTestId('view-history-btn').click();
        await expect(page.getByTestId('transaction-history-panel')).toBeVisible({ timeout: 10000 });

        // Select a different customer
        await page.getByTestId('customer-dropdown-trigger').click();
        searchInput = page.getByTestId('customer-search-input');
        await searchInput.fill('Francisco');
        await page.waitForTimeout(300);
        await page.locator('[data-testid^="customer-option-"]').first().click();

        // Transaction history should be hidden until explicitly opened again
        await expect(page.getByTestId('transaction-history-panel')).not.toBeVisible();
    });
});
