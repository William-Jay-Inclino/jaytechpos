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
        'supplier_id',
        'unit_id',
        'sku',
        'barcode',
        'product_name',
        'description',
        'unit_price',
        'cost_price',
        'vat_type',
        'image_path',
        'status',
    ];

    /**
     * Cast attributes to correct types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'unit_price' => 'float',
        'cost_price' => 'float',
    ];

    public function productCategory()
    {
        return $this->belongsTo(ProductCategory::class);
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
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
