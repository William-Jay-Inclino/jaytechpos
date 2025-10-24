<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UtangTracking extends Model
{
    use HasFactory;

    protected $table = 'utang_trackings';

    protected $fillable = [
        'user_id',
        'customer_id',
        'beginning_balance',
        'computation_date',
        'interest_rate',
    ];

    protected $casts = [
        'beginning_balance' => 'float',
        'computation_date' => 'date',
        'interest_rate' => 'float',
    ];

    // Relationships

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }
}
