<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Facades\Auth;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Product extends Model
{
    use HasFactory, LogsActivity, LogsActivityWithRequest;

    protected $fillable = [
        'user_id',
        'unit_id',
        'product_name',
        'barcode',
        'description',
        'unit_price',
        'cost_price',
        'status',
    ];

    protected $casts = [
        'unit_price' => 'decimal:2',
        'cost_price' => 'decimal:2',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? Auth::id();

        return $query->where('user_id', $userId);
    }

    public function scopeAvailableForSale($query, $userId = null)
    {
        return $query->active()->ownedBy($userId);
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

    public function inventory(): HasOne
    {
        return $this->hasOne(Inventory::class);
    }

    public function stockMovements()
    {
        return $this->hasMany(StockMovement::class, 'product_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['product_name', 'barcode', 'description', 'unit_price', 'cost_price', 'status', 'unit_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
