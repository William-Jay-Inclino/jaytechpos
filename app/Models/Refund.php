<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Refund extends Model
{
    use HasFactory;

    protected $fillable = [
        'sale_id',
        'user_id',
        'manager_id',
        'amount',
        'reason',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    /**
     * Refund belongs to a Sale.
     */
    public function sale()
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Refund is processed by a User (cashier/staff).
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Refund must be approved by a Manager (also from users table).
     */
    public function manager()
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
