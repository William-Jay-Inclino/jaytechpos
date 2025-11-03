<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\User;
use App\Services\SaleService;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class SaleSeeder extends Seeder
{
    protected SaleService $saleService;

    public function __construct()
    {
        $this->saleService = new SaleService;
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::all();

        foreach ($users as $user) {
            $this->createSalesForUser($user);
        }
    }

    private function createSalesForUser(User $user): void
    {
        $customers = Customer::where('user_id', $user->id)->get();
        $products = Product::where('user_id', $user->id)->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            $this->command->warn("No customers or products found for user: {$user->name}");

            return;
        }

        // Generate sales from January 2025 to present (November 2025)
        $startDate = Carbon::create(2025, 1, 1);
        $endDate = Carbon::now();
        $totalDays = $startDate->diffInDays($endDate);

        // Adjust sales volume based on business type
        $targetSales = $this->getSalesTargetByBusinessType($user);
        $generatedSales = 0;

        // Generate sales randomly distributed across the entire period
        while ($generatedSales < $targetSales) {
            // Pick a random date within the period
            $randomDay = rand(0, $totalDays);
            $saleDate = $startDate->copy()->addDays($randomDay);

            // Skip future dates (shouldn't happen but safety check)
            if ($saleDate->isFuture()) {
                continue;
            }

            $this->createSingleSale($user, $customers, $products, $saleDate);
            $generatedSales++;
        }

        $this->command->info("Created {$generatedSales} sales for user: {$user->name}");
    }

    private function getSalesCountForDate(Carbon $date): int
    {
        // Weekend and end of month have higher sales
        if ($date->isWeekend() || $date->day >= 28) {
            return rand(2, 5);
        }

        // Regular weekdays
        return rand(0, 3);
    }

    private function createSingleSale(User $user, $customers, $products, Carbon $date): void
    {
        // 70% chance to have a customer, 30% walk-in (no customer)
        $customer = rand(1, 100) <= 70 ? $customers->random() : null;

        // 60% cash, 40% utang (only if customer exists)
        $paymentType = ($customer && rand(1, 100) <= 40) ? 'utang' : 'cash';

        // Generate random time during business hours (7 AM to 8 PM)
        $transactionTime = $date->copy()->addHours(rand(7, 20))->addMinutes(rand(0, 59));

        // Select 1-4 random products for this sale
        $saleProducts = $products->random(rand(1, 4));

        $totalAmount = 0;
        $salesItems = [];

        foreach ($saleProducts as $product) {
            $quantity = rand(1, 5); // 1-5 items
            $unitPrice = $product->unit_price;
            $itemTotal = $quantity * $unitPrice;
            $totalAmount += $itemTotal;

            $salesItems[] = [
                'product_id' => $product->id,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
            ];
        }

        // Calculate paid amount
        $paidAmount = $paymentType === 'cash' ? $totalAmount : rand(0, (int) ($totalAmount * 0.8));

        // Create the sale
        $sale = Sale::create([
            'user_id' => $user->id,
            'customer_id' => $customer?->id,
            'total_amount' => $totalAmount,
            'paid_amount' => $paidAmount,
            'invoice_number' => $this->saleService->generateInvoiceNumber(),
            'payment_type' => $paymentType,
            'transaction_date' => $transactionTime,
            'notes' => $this->getRandomSaleNote(),
        ]);

        // Create sales items
        foreach ($salesItems as $item) {
            SalesItem::create(array_merge($item, ['sale_id' => $sale->id]));
        }

        // Customer transactions will be created by CustomerTransactionRebuilderSeeder
    }

    private function getSalesTargetByBusinessType(User $user): int
    {
        return match ($user->email) {
            'roberto.cruz@demo.com' => rand(200, 250), // Mini Grocery - Highest volume
            'maria.santos@demo.com' => rand(150, 200), // Fruits/Vegetables/Rice - Medium volume
            'luz.reyes@demo.com' => rand(100, 150),    // Sari-Sari - Lowest volume
            default => rand(100, 150), // Default for Jay and others
        };
    }

    private function getRandomSaleNote(): ?string
    {
        $notes = [
            null, null, null, null, null, // 50% chance of no notes
            'Regular customer',
            'Birthday celebration',
            'Bulk purchase',
            'Special request',
            'Discounted item',
            'Customer referral',
            'Repeat order',
        ];

        return $notes[array_rand($notes)];
    }
}
