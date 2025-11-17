<?php

namespace App\Models;

use App\Traits\LogsActivityWithRequest;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Expense extends Model
{
    use HasFactory, LogsActivity, LogsActivityWithRequest;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'amount',
        'expense_date',
    ];

    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return $query->where('user_id', $userId);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'amount', 'expense_date', 'category_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
