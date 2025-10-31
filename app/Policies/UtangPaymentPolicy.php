<?php

namespace App\Policies;

use App\Models\Customer;
use App\Models\User;
use App\Models\UtangPayment;

class UtangPaymentPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, UtangPayment $utangPayment): bool
    {
        return $utangPayment->user_id === $user->id;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can create a payment for the given customer.
     */
    public function createForCustomer(User $user, int $customerId): bool
    {
        // Verify that the customer belongs to the authenticated user
        return Customer::where('id', $customerId)
            ->where('user_id', $user->id)
            ->exists();
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, UtangPayment $utangPayment): bool
    {
        return $utangPayment->user_id === $user->id;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, UtangPayment $utangPayment): bool
    {
        return $utangPayment->user_id === $user->id;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, UtangPayment $utangPayment): bool
    {
        return $utangPayment->user_id === $user->id;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, UtangPayment $utangPayment): bool
    {
        return $utangPayment->user_id === $user->id;
    }
}
