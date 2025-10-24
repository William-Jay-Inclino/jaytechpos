<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'mobile_number',
        'has_utang',
        'interest_rate',
    ];

    protected $casts = [
        'mobile_number' => 'string',
        'has_utang' => 'boolean',
        'interest_rate' => 'float',
    ];

    public function sales()
    {
        return $this->hasMany(Sale::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function utangTrackings()
    {
        return $this->hasMany(UtangTracking::class);
    }

    public function utangPayments()
    {
        return $this->hasMany(UtangPayment::class);
    }

    // Computed Properties

    /**
     * Get the running balance of utang for the current month
     */
    protected function runningUtangBalance(): Attribute
    {
        return Attribute::make(
            get: function () {
                $currentMonth = now()->month;
                $currentYear = now()->year;

                // Get the most recent UtangTracking record for the current month
                $utangTracking = $this->utangTrackings()
                    ->whereMonth('computation_date', $currentMonth)
                    ->whereYear('computation_date', $currentYear)
                    ->latest('computation_date')
                    ->first();

                return $utangTracking ? $utangTracking->beginning_balance : 0;
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
}
