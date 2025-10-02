<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'customer_id',
        'sales_status_id',
        'subtotal',
        'total_amount',
        'discount_amount',
        'vat_amount',
        'net_amount',
        'invoice_number',
        'transaction_date',
        'receipt_number',
        'notes',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'transaction_date' => 'datetime',
    ];

    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function salesStatus()
    {
        return $this->belongsTo(SalesStatus::class);
    }

    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'sale_id');
    }

    public function paymentTransactions()
    {
        return $this->hasMany(PaymentTransaction::class);
    }

    public function receipts()
    {
        return $this->hasMany(Receipt::class, 'sale_id');
    }

    public function refunds()
    {
        return $this->hasMany(Refund::class, 'sale_id');
    }

    // Business Logic Methods
    public function getTotalPaidAttribute()
    {
        return $this->paymentTransactions()->where('status', 'completed')->sum('amount');
    }

    public function getTotalChangeAttribute()
    {
        return $this->paymentTransactions()->where('status', 'completed')->sum('change_amount');
    }

    public function isFullyPaid(): bool
    {
        return $this->total_paid >= $this->net_amount;
    }

    public function getPaymentMethodsAttribute()
    {
        return $this->paymentTransactions()
            ->with('paymentMethod')
            ->where('status', 'completed')
            ->get()
            ->pluck('paymentMethod.name')
            ->unique()
            ->implode(', ');
    }

}
