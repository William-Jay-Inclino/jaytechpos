<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function __construct()
    {
        // Wire up policy authorization for resource methods using the UserPolicy
        $this->authorizeResource(User::class, 'user');
    }

    public function index()
    {
        $users = User::where('role', '!=', UserRole::Admin)
            ->latest()
            ->paginate(15)
            ->through(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role->label(),
                    'status' => $user->status,
                    'created_at' => $user->created_at->format('M d, Y'),
                ];
            });

        return Inertia::render('admin/users/Index', [
            'users' => $users,
        ]);
    }

    public function create()
    {
        return Inertia::render('admin/users/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'user',
            'status' => 'active',
        ]);

        return response()->json([
            'success' => true,
            'msg' => 'User created successfully.',
        ]);
    }

    public function edit(User $user)
    {
        return Inertia::render('admin/users/Edit', [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role->value,
                'status' => $user->status,
            ],
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|string|email|max:255|unique:users,email,'.$user->id,
            'password' => 'sometimes|required|string|min:8|confirmed',
            'status' => 'sometimes|required|string|in:active,inactive',
        ]);

        $data = [];

        if (isset($validated['name'])) {
            $data['name'] = $validated['name'];
        }

        if (isset($validated['email'])) {
            $data['email'] = $validated['email'];
        }

        if (isset($validated['password'])) {
            $data['password'] = Hash::make($validated['password']);
        }

        if (isset($validated['status'])) {
            $data['status'] = $validated['status'];
        }

        $user->update($data);

        return response()->json([
            'success' => true,
            'msg' => 'User updated successfully.',
        ]);
    }

    public function destroy(User $user)
    {
        // Authorize deletion first (follows ExpenseController pattern)
        $this->authorize('delete', $user);
        if ($user->id === auth()->id()) {
            return response()->json([
                'success' => false,
                'msg' => 'You cannot delete yourself.',
            ], 403);
        }

        // Check if user is active
        if ($user->status === 'active') {
            return response()->json([
                'success' => false,
                'msg' => 'Cannot delete an active user. Please set the user to inactive first.',
            ], 409);
        }

        // Check for associated records
        $hasCustomerTransactions = $user->customerTransactions()->exists();
        $hasSales = $user->sales()->exists();
        $hasCustomers = $user->customers()->exists();
        $hasProducts = $user->products()->exists();
        $hasExpenses = $user->expenses()->exists();

        if ($hasCustomerTransactions || $hasSales || $hasCustomers || $hasProducts || $hasExpenses) {
            $associations = [];
            if ($hasCustomerTransactions) {
                $associations[] = 'customer transactions';
            }
            if ($hasSales) {
                $associations[] = 'sales';
            }
            if ($hasCustomers) {
                $associations[] = 'customers';
            }
            if ($hasProducts) {
                $associations[] = 'products';
            }
            if ($hasExpenses) {
                $associations[] = 'expenses';
            }

            $message = 'Cannot delete user. This user has associated '.implode(', ', $associations).'.';

            return response()->json([
                'success' => false,
                'msg' => $message,
            ], 409);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'msg' => 'User deleted successfully.',
        ]);
    }
}
