<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class UtangTracking extends Model
{
    use HasFactory;

    protected $table = 'utang_trackings';

    protected $fillable = [
        'sale_id',
        'customer_id',
        'total_amount',
        'paid_amount',
        'interest_rate',
        'status',
        'due_date',
        'notes',
    ];

    protected $casts = [
        'total_amount' => 'float',
        'paid_amount' => 'float',
        'interest_rate' => 'float',
        'due_date' => 'date',
    ];

    protected $appends = [
        'remaining_amount',
    ];

    // Relationships
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(UtangPayment::class);
    }

    // Computed Properties
    public function getRemainingAmountAttribute(): float
    {
        return max(0, $this->total_amount - $this->paid_amount);
    }

    // Helper methods for business logic
    public function isFullyPaid(): bool
    {
        return $this->getRemainingAmountAttribute() <= 0;
    }

    public function isOverdue(): bool
    {
        return $this->due_date < now() && ! $this->isFullyPaid();
    }

    public function getDaysOverdue(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return now()->diffInDays($this->due_date);
    }
}
