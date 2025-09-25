<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unit extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'unit_name',
        'abbreviation',
    ];

    /**
     * A unit can be used for many products.
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
