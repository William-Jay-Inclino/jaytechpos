<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateBalanceRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Setting;
use App\Services\CustomerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        protected CustomerService $customerService
    ) {}

    /**
     * Display a listing of customers.
     */
    public function index(): Response
    {
        $customers = CustomerResource::collection(
            Customer::ownedBy()
                ->orderBy('name')
                ->get()
        )->resolve();

        return Inertia::render('customers/Index', [
            'customers' => $customers,
        ]);
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create(): Response
    {
        return Inertia::render('customers/Create', [
            'defaultInterestRate' => Setting::getDefaultUtangInterestRate(),
        ]);
    }

    /**
     * Store a newly created customer.
     */
    public function store(StoreCustomerRequest $request): JsonResponse
    {
        $customer = DB::transaction(function () use ($request) {
            $customer = Customer::create([
                'user_id' => auth()->id(),
                'name' => $request->validated('name'),
                'mobile_number' => $request->validated('mobile_number'),
                'remarks' => $request->validated('remarks'),
                'interest_rate' => $request->validated('interest_rate'),
            ]);

            // Create starting balance transaction if amount > 0
            $startingBalance = $request->validated('starting_balance');
            if ($startingBalance && $startingBalance > 0) {
                CustomerTransaction::create([
                    'user_id' => auth()->id(),
                    'customer_id' => $customer->id,
                    'transaction_type' => 'starting_balance',
                    'reference_id' => null,
                    'previous_balance' => 0,
                    'new_balance' => $startingBalance,
                    'transaction_desc' => '---',
                    'transaction_date' => now(),
                    'transaction_amount' => $startingBalance,
                ]);

                $customer->update(['has_utang' => true]);

            }

            return $customer;
        });

        return response()->json([
            'success' => true,
            'msg' => 'Customer created successfully!',
        ]);
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('customers/Edit', [
            'customer' => (new CustomerResource($customer))->resolve(),
            'defaultInterestRate' => Setting::getDefaultUtangInterestRate(),
        ]);
    }

    /**
     * Update the specified customer.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $customer->update([
            'name' => $request->validated('name'),
            'mobile_number' => $request->validated('mobile_number'),
            'remarks' => $request->validated('remarks'),
            'interest_rate' => $request->validated('interest_rate'),
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'Customer updated successfully!',
        ]);
    }

    /**
     * Update the customer's balance.
     */
    public function updateBalance(UpdateBalanceRequest $request, Customer $customer): JsonResponse
    {
        $this->authorize('update', $customer);

        $newBalance = $request->validated('balance');
        $note = $request->validated('note');
        $currentBalance = $customer->running_utang_balance;

        // Check if balance actually changed
        if ($newBalance == $currentBalance) {
            return response()->json([
                'success' => false,
                'msg' => 'New balance must be different from current balance.',
            ], 422);
        }

        DB::transaction(function () use ($customer, $newBalance, $note, $currentBalance) {
            // Create balance update transaction
            CustomerTransaction::create([
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'transaction_type' => 'balance_update',
                'reference_id' => null,
                'previous_balance' => $currentBalance,
                'new_balance' => $newBalance,
                'transaction_desc' => $note ?: '---',
                'transaction_date' => now(),
                'transaction_amount' => $newBalance,
            ]);

            if($newBalance > 0) {
                $customer->update(['has_utang' => true]);
            } else {
                $customer->update(['has_utang' => false]);
            }

        });

        return response()->json([
            'success' => true,
            'msg' => 'Customer balance updated successfully!',
        ]);
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer)
    {
        $this->authorize('delete', $customer);

        // Check if customer has any related records
        if ($customer->sales()->exists() ||
            $customer->customerTransactions()->exists()) {
            return response()->json([
                'success' => false,
                'msg' => 'Cannot delete customer with existing sales or transaction history.',
            ], 409);
        }

        $customer->delete();

        return response()->json([
            'success' => true,
            'msg' => 'Customer deleted successfully.',
        ]);
    }

    /**
     * Show the transaction history for a customer.
     */
    public function transactions(Customer $customer): Response
    {
        $this->authorize('view', $customer);

        return Inertia::render('customers/Transactions', [
            'customer' => (new CustomerResource($customer))->resolve(),
        ]);
    }

    /**
     * Get transaction data for a customer via API.
     */
    public function getTransactions(Customer $customer): JsonResponse
    {
        $this->authorize('view', $customer);

        $transactions = $this->customerService->getCustomerTransactions($customer);

        return response()->json([
            'transactions' => $transactions,
        ]);
    }
}
