<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\Setting;
use App\Services\CustomerService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
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
    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create([
            'user_id' => auth()->id(),
            'name' => $request->validated('name'),
            'mobile_number' => $request->validated('mobile_number'),
            'remarks' => $request->validated('remarks'),
            'interest_rate' => $request->validated('interest_rate'),
        ]);

        return redirect()->route('customers.index')
            ->with('message', 'Customer created successfully!');
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
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $this->authorize('update', $customer);

        $customer->update([
            'name' => $request->validated('name'),
            'mobile_number' => $request->validated('mobile_number'),
            'remarks' => $request->validated('remarks'),
            'interest_rate' => $request->validated('interest_rate'),
        ]);

        return redirect()->route('customers.index')
            ->with('message', 'Customer updated successfully!');
    }

    /**
     * Remove the specified customer from storage.
     */
    public function destroy(Customer $customer): RedirectResponse
    {
        $this->authorize('delete', $customer);

        // Check if customer has any related records
        if ($customer->sales()->exists() ||
            $customer->utangTrackings()->exists() ||
            $customer->utangPayments()->exists()) {
            return redirect()->route('customers.index')
                ->with('error', 'Cannot delete customer with existing sales, utang trackings, or payments.');
        }

        $customer->delete();

        return redirect()->route('customers.index')
            ->with('message', 'Customer deleted successfully!');
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
