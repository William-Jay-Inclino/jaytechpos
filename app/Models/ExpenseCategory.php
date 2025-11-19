<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExpenseCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
    ];

    public function scopeOwnedBy($query, $userId = null)
    {
        $userId = $userId ?? auth()->id();

        return $query->where('user_id', $userId);
    }

    /**
     * Default expense categories used for new users and seeding.
     * Keep this as the single source of truth to avoid duplication.
     */
    public static function defaultCategories(): array
    {
        // Return an array of ['name' => string, 'color' => string]
        $names = config('expense_categories.default', [
            'Rent',
            'Utilities',
            'Staff Wages',
            'Supplies',
            'Transportation',
            'Equipment & Maintenance',
            'Government Fees & Permits',
            'Insurance',
            'Marketing & Advertising',
            'Miscellaneous',
        ]);

        $colors = config('expense_categories.category_colors', []);
        $fallback = config('expense_categories.default_color', '#6B7280');

        $result = [];
        foreach ($names as $name) {
            $result[] = [
                'name' => $name,
                'color' => $colors[$name] ?? $fallback,
            ];
        }

        return $result;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(Expense::class, 'category_id');
    }
}
