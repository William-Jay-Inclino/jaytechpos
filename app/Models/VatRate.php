<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VatRate extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'rate_name',
        'rate_percentage',
        'effective_date',
        'is_active',
    ];

    /**
     * Cast attributes to correct types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'rate_percentage' => 'decimal:2',
        'effective_date' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * A VAT rate can apply to many products.
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'vat_id');
    }
}
