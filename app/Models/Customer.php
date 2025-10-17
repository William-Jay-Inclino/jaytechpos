<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'mobile_number',
    ];

    protected $casts = [
        'mobile_number' => 'string',
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

    // Helper methods for utang functionality
    public function hasActiveUtang(): bool
    {
        return $this->utangTrackings()->active()->exists();
    }

    public function getTotalUtangBalance(): float
    {
        return $this->utangTrackings()
            ->active()
            ->sum('remaining_amount');
    }

    public function getOverdueUtangCount(): int
    {
        return $this->utangTrackings()
            ->overdue()
            ->count();
    }

    public function getTotalPaidAmount(): float
    {
        return $this->utangPayments()
            ->sum('payment_amount');
    }

    public function getPaymentHistory()
    {
        return $this->utangPayments()
            ->with('utangTracking')
            ->orderBy('payment_date', 'desc');
    }
}
