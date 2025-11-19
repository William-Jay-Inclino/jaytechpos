<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Expense Categories
    |--------------------------------------------------------------------------
    |
    | This list is used when creating default ExpenseCategory records for new
    | users and by the database seeder. You can customize it without changing
    | application code by editing this config file.
    |
    */
    'default' => [
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
    ],
    /* Default color used when creating new categories. */
    'default_color' => '#6B7280',
    /* Per-category default colors. Keys should match the category names above. */
    'category_colors' => [
        'Rent' => '#EF4444',               // red
        'Utilities' => '#F59E0B',          // amber
        'Staff Wages' => '#10B981',        // green
        'Supplies' => '#3B82F6',           // blue
        'Transportation' => '#8B5CF6',     // violet
        'Equipment & Maintenance' => '#06B6D4', // cyan
        'Government Fees & Permits' => '#F97316', // orange
        'Insurance' => '#6366F1',          // indigo
        'Marketing & Advertising' => '#EC4899', // pink
        'Miscellaneous' => '#6B7280',      // gray (fallback)
    ],
];
