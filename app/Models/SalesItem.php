<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'product_id',
        'quantity',
        'unit_price',
        'discount_amount',
        'vat_amount',
        'total_amount',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'vat_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * A sales item belongs to a sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * A sales item belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
