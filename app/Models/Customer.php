<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Customer extends Model
{
    use HasFactory, LogsActivity, LogsActivityWithRequest;

    protected $fillable = [
        'user_id',
        'name',
        'mobile_number',
        'has_utang',
        'interest_rate',
        'remarks',
    ];

    protected $casts = [
        'mobile_number' => 'string',
        'has_utang' => 'boolean',
        'interest_rate' => 'float',
    ];

    /**
     * Scope: Filter customers owned by a specific user
     * Use this to ensure users only see their own customers
     */
    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter customers with utang (debt)
     * Useful for collecting outstanding balances
     */
    public function scopeWithUtang($query)
    {
        return $query->where('has_utang', true);
    }

    /**
     * Scope: Filter customers without utang (no debt)
     * Useful for finding customers with clear balances
     */
    public function scopeWithoutUtang($query)
    {
        return $query->where('has_utang', false);
    }

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customerTransactions()
    {
        return $this->hasMany(CustomerTransaction::class);
    }

    // Computed Properties

    /**
     * Get the running balance of utang (most recent balance from customer transactions)
     */
    protected function runningUtangBalance(): Attribute
    {
        return Attribute::make(
            get: function () {
                // Get the most recently created transaction to get the current balance
                // Using created_at ensures we get the balance from the last recorded transaction
                $lastTransaction = $this->customerTransactions()
                    ->latest('created_at')
                    ->latest('id') // In case of same timestamp
                    ->first();

                return $lastTransaction ? (float) $lastTransaction->new_balance : 0.0;
            }
        );
    }

    /**
     * Get the effective interest rate for this customer
     * Returns customer-specific rate if set, otherwise default rate
     */
    public function getEffectiveInterestRate(): float
    {
        if ($this->interest_rate !== null) {
            return $this->interest_rate;
        }

        return \App\Models\Setting::getDefaultUtangInterestRate();
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'mobile_number', 'has_utang', 'interest_rate', 'remarks'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
