<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUtangPaymentRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\UtangPayment;
use App\Services\UtangTrackingService;
use App\Traits\HandlesTimezone;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class UtangPaymentController extends Controller
{
    use AuthorizesRequests, HandlesTimezone;

    public function __construct(
        protected UtangTrackingService $utangTrackingService
    ) {}

    public function index(): Response
    {
        $this->authorize('viewAny', UtangPayment::class);

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
        $this->authorize('createForCustomer', [UtangPayment::class, $request->customer_id]);

        $payment = DB::transaction(function () use ($request) {
            // Get current balance before payment for customer transaction tracking
            $customer = Customer::findOrFail($request->customer_id);
            $currentBalance = $this->getCurrentBalance($customer);
            $newBalance = $currentBalance - $request->payment_amount;

            // Create the payment record
            $payment = UtangPayment::create([
                'user_id' => auth()->id(),
                'customer_id' => $request->customer_id,
                'payment_amount' => $request->payment_amount,
                'payment_date' => now(), // Use current timestamp for accurate ordering
                'notes' => $request->notes,
            ]);

            // Update the customer's utang tracking balance
            $this->utangTrackingService->deductFromRunningBalance($customer->id, $request->payment_amount);

            // Update has_utang status if balance is now zero
            if ($newBalance <= 0) {
                $customer->update(['has_utang' => false]);
            }

            // Create customer transaction record
            CustomerTransaction::create([
                'user_id' => auth()->id(),
                'customer_id' => $payment->customer_id,
                'transaction_type' => 'utang_payment',
                'reference_id' => $payment->id,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'transaction_desc' => $request->notes,
                'transaction_date' => $payment->payment_date,
                'transaction_amount' => $payment->payment_amount,
            ]);

            return $payment;
        });

        return redirect()->route('utang-payments')
            ->with('message', 'Payment recorded successfully!');
    }

    /**
     * Get the current balance for a customer
     */
    private function getCurrentBalance(Customer $customer): float
    {
        $utangTracking = $this->utangTrackingService->getActiveUtangTracking($customer->id);

        return $utangTracking ? $utangTracking->beginning_balance : 0.0;
    }
}
