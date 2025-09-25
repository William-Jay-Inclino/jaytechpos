<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'supplier_name',
        'contact_name',
        'address',
        'phone',
        'email',
    ];

    /**
     * Define relationships (if needed later).
     * Example: A supplier might supply many products.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
