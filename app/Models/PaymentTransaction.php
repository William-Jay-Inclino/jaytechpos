<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'payment_method_id',
        'amount',
        'amount_tendered',
        'change_amount',
        'reference_number',
        'card_last_four',
        'authorization_code',
        'status',
        'processed_at',
        'processor_response',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'amount_tendered' => 'decimal:2',
        'change_amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'processor_response' => 'array',
    ];

    // Relationships
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeCash($query)
    {
        return $query->whereHas('paymentMethod', function ($q) {
            $q->where('name', 'Cash');
        });
    }

    public function scopeCard($query)
    {
        return $query->whereHas('paymentMethod', function ($q) {
            $q->whereIn('name', ['Credit Card', 'Debit Card', 'Card']);
        });
    }

    // Business Logic Methods
    public function isCashPayment(): bool
    {
        return $this->paymentMethod->name === 'Cash';
    }

    public function isCardPayment(): bool
    {
        return in_array($this->paymentMethod->name, ['Credit Card', 'Debit Card', 'Card']);
    }

    public function getMaskedCardNumber(): ?string
    {
        return $this->card_last_four ? '**** **** **** ' . $this->card_last_four : null;
    }
}
