<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUtangPaymentRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\UtangPayment;
use App\Traits\HandlesTimezone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UtangPaymentController extends Controller
{
    use HandlesTimezone;

    public function index(): Response
    {
        $customers = CustomerResource::collection(
            Customer::ownedBy()
                ->orderBy('name')
                ->get()
        )->resolve();

        return Inertia::render('utang-payments/Index', [
            'customers' => $customers,
        ]);
    }

    public function store(StoreUtangPaymentRequest $request): RedirectResponse
    {
        $payment = DB::transaction(function () use ($request) {
            // Get current balance before payment
            $customer = Customer::findOrFail($request->customer_id);
            $currentBalance = $this->getCurrentBalance($customer);
            $newBalance = $currentBalance - $request->payment_amount;

            // Create the payment with balance information
            $payment = UtangPayment::create([
                'user_id' => auth()->id(),
                'customer_id' => $request->customer_id,
                'payment_amount' => $request->payment_amount,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'payment_date' => now(), // Use current timestamp for accurate ordering
                'notes' => $request->notes,
            ]);

            // Update the customer's utang tracking balance
            $this->updateUtangBalance($customer, $request->payment_amount);

            // Update has_utang status if balance is now zero
            if ($newBalance <= 0) {
                $customer->update(['has_utang' => false]);
            }

            return $payment;
        });

        return redirect()->route('utang-payments')
            ->with('message', 'Payment recorded successfully!');
    }

    public function getCustomerTransactions(Request $request, Customer $customer): JsonResponse
    {
        // Ensure customer belongs to authenticated user
        if ($customer->user_id !== auth()->id()) {
            abort(403, 'Unauthorized access to customer data.');
        }

        // Get utang payments
        $utangPayments = $customer->utangPayments()
            ->orderBy('payment_date', 'desc')
            ->get()
            ->map(function ($payment) {
                return [
                    'id' => $payment->id,
                    'type' => 'payment',
                    'date' => $payment->payment_date->format('Y-m-d H:i:s'), // Laravel handles timezone automatically
                    'sort_date' => $payment->payment_date->toISOString(), // For sorting
                    'amount' => $payment->payment_amount,
                    'formatted_amount' => $payment->formatted_payment_amount,
                    'description' => 'Payment: '.($payment->notes ? $payment->notes : 'No notes'),
                    'notes' => $payment->notes,
                    'previous_balance' => $payment->previous_balance,
                    'new_balance' => $payment->new_balance,
                    'formatted_previous_balance' => '₱'.number_format($payment->previous_balance, 2),
                    'formatted_new_balance' => '₱'.number_format($payment->new_balance, 2),
                ];
            });

        // Get utang trackings
        $utangTrackings = $customer->utangTrackings()
            ->orderBy('computation_date', 'desc')
            ->get()
            ->map(function ($tracking) use ($customer) {
                // Get previous month's balance (before interest was applied)
                $previousMonth = $tracking->computation_date->copy()->subMonth();
                $previousTracking = $customer->utangTrackings()
                    ->whereYear('computation_date', $previousMonth->year)
                    ->whereMonth('computation_date', $previousMonth->month)
                    ->first();

                $previousBalance = $previousTracking ? $previousTracking->beginning_balance : 0;
                $newBalance = $tracking->beginning_balance;

                return [
                    'id' => $tracking->id,
                    'type' => 'tracking',
                    'date' => $tracking->computation_date->format('Y-m-d H:i:s'), // Laravel handles timezone automatically
                    'sort_date' => $tracking->computation_date->toISOString(), // For sorting
                    'amount' => $tracking->beginning_balance,
                    'formatted_amount' => '₱'.number_format($tracking->beginning_balance, 2),
                    'description' => 'Balance Update (Interest Rate: '.number_format($tracking->interest_rate, 2).'%)',
                    'interest_rate' => $tracking->interest_rate,
                    'previous_balance' => $previousBalance,
                    'new_balance' => $newBalance,
                    'formatted_previous_balance' => '₱'.number_format($previousBalance, 2),
                    'formatted_new_balance' => '₱'.number_format($newBalance, 2),
                    'computation_date' => $tracking->computation_date->format('Y-m-d'),
                ];
            });

        // Get sales
        $sales = $customer->sales()
            ->with(['salesItems.product']) // Eager load sales items and products
            ->orderBy('transaction_date', 'desc')
            ->get()
            ->map(function ($sale) {
                return [
                    'id' => $sale->id,
                    'type' => 'sale',
                    'date' => $sale->transaction_date->format('Y-m-d H:i:s'), // Laravel handles timezone automatically
                    'sort_date' => $sale->transaction_date->toISOString(), // For sorting
                    'amount' => $sale->total_amount,
                    'formatted_amount' => '₱'.number_format($sale->total_amount, 2),
                    'description' => 'Sale #'.$sale->invoice_number.' ('.ucfirst($sale->payment_type).')',
                    'invoice_number' => $sale->invoice_number,
                    'payment_type' => $sale->payment_type,
                    'total_amount' => $sale->total_amount,
                    'paid_amount' => $sale->paid_amount,
                    'notes' => $sale->notes,
                    'previous_balance' => $sale->previous_balance,
                    'new_balance' => $sale->new_balance,
                    'formatted_previous_balance' => '₱'.number_format($sale->previous_balance, 2),
                    'formatted_new_balance' => '₱'.number_format($sale->new_balance, 2),
                    'sales_items' => $sale->salesItems->map(function ($item) {
                        return [
                            'id' => $item->id,
                            'product_name' => $item->product->product_name ?? 'Unknown Product',
                            'quantity' => $item->quantity,
                            'unit_price' => $item->unit_price,
                            'total_price' => $item->quantity * $item->unit_price,
                        ];
                    })->toArray(),
                ];
            });

        // Combine and sort all transactions by date (newest first)
        $allTransactions = collect()
            ->merge($utangPayments)
            ->merge($utangTrackings)
            ->merge($sales)
            ->sortByDesc('sort_date')
            ->map(function ($transaction) {
                // Remove sort_date from final output
                unset($transaction['sort_date']);

                return $transaction;
            })
            ->values();

        return response()->json([
            'transactions' => $allTransactions,
        ]);
    }

    /**
     * Get the current balance for a customer
     */
    private function getCurrentBalance(Customer $customer): float
    {
        // Get the most recent utang tracking record for the current month
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $utangTracking = $customer->utangTrackings()
            ->whereMonth('computation_date', $currentMonth)
            ->whereYear('computation_date', $currentYear)
            ->latest('computation_date')
            ->first();

        return $utangTracking ? $utangTracking->beginning_balance : 0.0;
    }

    /**
     * Update the customer's utang balance by deducting the payment amount
     */
    private function updateUtangBalance(Customer $customer, float $paymentAmount): void
    {
        // Get the most recent utang tracking record for the current month
        $currentMonth = now()->month;
        $currentYear = now()->year;

        $utangTracking = $customer->utangTrackings()
            ->whereMonth('computation_date', $currentMonth)
            ->whereYear('computation_date', $currentYear)
            ->latest('computation_date')
            ->first();

        if ($utangTracking) {
            // Deduct payment amount from beginning balance
            $newBalance = $utangTracking->beginning_balance - $paymentAmount;
            $utangTracking->update(['beginning_balance' => $newBalance]);
        }
    }
}
