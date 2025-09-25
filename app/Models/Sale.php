<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sale extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'payment_method_id',
        'sales_status_id',
        'total_amount',
        'discount_amount',
        'vat_amount',
        'net_amount',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'net_amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function salesStatus()
    {
        return $this->belongsTo(SalesStatus::class);
    }

    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'sale_id');
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
