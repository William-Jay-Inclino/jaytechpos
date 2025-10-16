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
        'amount_tendered',
        'change_amount',
        'invoice_number',
        'transaction_date',
        'receipt_number',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'vat_amount' => 'float',
        'net_amount' => 'float',
        'amount_tendered' => 'float',
        'change_amount' => 'float',
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
}
