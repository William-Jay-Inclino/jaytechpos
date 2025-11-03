<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Traits\HandlesTimezone;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerTransactionController extends Controller
{
    use AuthorizesRequests, HandlesTimezone;

    /**
     * Display the utang payments page (for compatibility with frontend)
     */
    public function utangPayments(): Response
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

    /**
     * Store a new utang payment as a customer transaction
     */
    public function storeUtangPayment(Request $request): RedirectResponse
    {
        $request->validate([
            'customer_id' => [
                'required',
                'integer',
                'exists:customers,id,user_id,'.auth()->id(),
            ],
            'payment_amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $payment = DB::transaction(function () use ($request) {
            // Get customer and calculate balance from the most recent transaction
            $customer = Customer::findOrFail($request->customer_id);
            
            // Get current balance from the most recently created transaction
            // Since we use created_at for balance calculation, we should use the current balance
            $currentBalance = $customer->running_utang_balance;
            
            $newBalance = $currentBalance - $request->payment_amount;

            // Create payment record
            $transaction = CustomerTransaction::create([
                'user_id' => $request->user()->id,
                'customer_id' => $request->customer_id,
                'transaction_type' => 'utang_payment',
                'transaction_date' => $request->payment_date,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'transaction_desc' => 'Payment - '.($request->notes ?? 'No notes'),
                'reference_id' => null,
                'transaction_amount' => $request->payment_amount,
            ]);

            // Update has_utang status if balance is now zero or negative
            if ($newBalance <= 0) {
                $customer->update(['has_utang' => false]);
            }

            return $transaction;
        });

        return redirect()->route('utang-payments')
            ->with('message', 'Payment recorded successfully!');
    }
}
