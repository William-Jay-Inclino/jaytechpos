<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
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

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully.');
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

        return back()->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself.');
        }

        // Check if user is active
        if ($user->status === 'active') {
            return back()->with('error', 'Cannot delete an active user. Please set the user to inactive first.');
        }

        // Check for associated records
        $hasCustomerTransactions = $user->customerTransactions()->exists();
        $hasSales = $user->sales()->exists();
        $hasCustomers = $user->customers()->exists();
        $hasProducts = $user->products()->exists();
        $hasExpenses = $user->expenses()->exists();
        $hasExpenseCategories = $user->expenseCategories()->exists();

        if ($hasCustomerTransactions || $hasSales || $hasCustomers || $hasProducts || $hasExpenses || $hasExpenseCategories) {
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
            if ($hasExpenseCategories) {
                $associations[] = 'expense categories';
            }

            $message = 'Cannot delete user. This user has associated '.implode(', ', $associations).'.';

            return back()->with('error', $message);
        }

        $user->delete();

        return back()->with('success', 'User deleted successfully.');
    }
}
