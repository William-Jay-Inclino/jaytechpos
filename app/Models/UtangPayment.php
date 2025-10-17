<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtangPayment extends Model
{
    use HasFactory;

    protected $table = 'utang_payments';

    protected $fillable = [
        'utang_tracking_id',
        'customer_id',
        'user_id',
        'payment_amount',
        'payment_date',
        'notes',
    ];

    protected $casts = [
        'payment_amount' => 'float',
        'payment_date' => 'date',
    ];

    // Relationships
    public function utangTracking(): BelongsTo
    {
        return $this->belongsTo(UtangTracking::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Scopes
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByUtang($query, int $utangTrackingId)
    {
        return $query->where('utang_tracking_id', $utangTrackingId);
    }

    public function scopeThisMonth($query)
    {
        return $query->whereMonth('payment_date', now()->month)
            ->whereYear('payment_date', now()->year);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('payment_date', now()->toDateString());
    }

    // Attributes
    protected function formattedPaymentAmount(): Attribute
    {
        return Attribute::make(
            get: fn () => '₱'.number_format($this->payment_amount, 2)
        );
    }

    protected function formattedPaymentDate(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->payment_date?->format('M d, Y')
        );
    }
}
