<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'low_stock_threshold',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'low_stock_threshold' => 'decimal:2',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'low_stock_threshold');
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('quantity', '<=', 0);
    }

    public function stockIn(float $amount): void
    {
        $this->increment('quantity', $amount);
    }

    public function stockOut(float $amount): void
    {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Stock out amount must be greater than zero.');
        }

        if ($this->quantity < $amount) {
            throw new \InvalidArgumentException('Insufficient stock.');
        }

        $this->decrement('quantity', $amount);
    }
}
