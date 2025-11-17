<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class CustomerTransaction extends Model
{
    use HasFactory, LogsActivity, LogsActivityWithRequest;

    protected $fillable = [
        'user_id',
        'customer_id',
        'transaction_type',
        'reference_id',
        'previous_balance',
        'new_balance',
        'transaction_desc',
        'transaction_date',
        'transaction_amount',
    ];

    protected function casts(): array
    {
        return [
            'previous_balance' => 'decimal:2',
            'new_balance' => 'decimal:2',
            'transaction_amount' => 'decimal:2',
            'transaction_date' => 'datetime',
        ];
    }

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // Scopes
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByType($query, string $transactionType)
    {
        return $query->where('transaction_type', $transactionType);
    }

    public function scopeOrderedByDate($query, string $direction = 'desc')
    {
        return $query->orderBy('transaction_date', $direction);
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['transaction_type', 'transaction_amount', 'previous_balance', 'new_balance', 'transaction_desc', 'transaction_date', 'reference_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
