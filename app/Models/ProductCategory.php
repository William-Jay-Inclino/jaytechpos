<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'description',
        'status',
        'is_default',
    ];

    protected $casts = [
        'status' => 'string',
        'is_default' => 'boolean',
    ];

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($category) {
            // If this category is being set as default, unset other defaults for this user
            if ($category->is_default && $category->user_id) {
                static::where('user_id', $category->user_id)
                    ->where('id', '!=', $category->id ?? 0)
                    ->update(['is_default' => false]);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return $query->where('user_id', $userId);
    }

    public function scopeActiveOwned($query, $userId = null)
    {
        return $query->active()->ownedBy($userId);
    }

    public function scopeDefault($query, $userId = null)
    {
        return $query->ownedBy($userId)->where('is_default', true);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class, 'category_id');
    }
}
