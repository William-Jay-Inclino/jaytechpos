<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'unit_id',
        'product_name',
        'description',
        'unit_price',
        'cost_price',
        'status',
    ];

    /**
     * Cast attributes to correct types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    /**
     * Scope: Filter only active products (status = 'active')
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Scope: Filter products owned by a specific user
     */
    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter products that are available for sale (active + owned by user)
     * Use this for POS/sales functionality to ensure only active, owned products are selectable
     */
    public function scopeAvailableForSale($query, $userId = null)
    {
        return $query->active()->ownedBy($userId);
    }

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class, 'category_id');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class);
    }

    public function salesItems()
    {
        return $this->hasMany(SalesItem::class, 'product_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
