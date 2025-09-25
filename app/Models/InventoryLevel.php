<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InventoryLevel extends Model
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
        'reorder_level',
    ];

    /**
     * Cast attributes to correct types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'quantity' => 'decimal:2',
        'reorder_level' => 'decimal:2',
    ];

    /**
     * An inventory level belongs to a product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
