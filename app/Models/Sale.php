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
        'total_amount',
        'vat_amount',
        'net_amount',
        'invoice_number',
        'transaction_date',
        'receipt_number',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
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

}
