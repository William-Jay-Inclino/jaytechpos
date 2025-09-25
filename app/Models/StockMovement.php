<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockMovement extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'product_id',
        'quantity',
        'movement_type',
        'reference',
        'batch_number',
        'expiry_date',
        'remarks',
    ];

    /**
     * Cast attributes to correct types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'expiry_date' => 'date',
    ];

    /**
     * A stock movement belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
