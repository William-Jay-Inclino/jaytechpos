<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUtangPaymentRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Traits\HandlesTimezone;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
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
        $customers = Customer::ownedBy()
            ->select(['id', 'name', 'interest_rate'])
            ->orderBy('name')
            ->get();

        return Inertia::render('utang-payments/Index', [
            'customers' => $customers,
        ]);
    }

    /**
     * Store a new utang payment as a customer transaction
     */
    public function storeUtangPayment(StoreUtangPaymentRequest $request): RedirectResponse
    {
        $customer = Customer::findOrFail($request->customer_id);
        $currentBalance = $customer->running_utang_balance;

        $payment = DB::transaction(function () use ($request, $customer, $currentBalance) {
            $newBalance = $currentBalance - $request->payment_amount;

            // Create payment record
            $transaction = CustomerTransaction::create([
                'user_id' => $request->user()->id,
                'customer_id' => $request->customer_id,
                'transaction_type' => 'utang_payment',
                'transaction_date' => $request->payment_date,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'transaction_desc' => ($request->notes ?? '---'),
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
