<?php

use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Create a partial unique index for PostgreSQL
        // This ensures only one default category per user
        \DB::statement('
            CREATE UNIQUE INDEX unique_default_per_user 
            ON product_categories (user_id) 
            WHERE is_default = true
        ');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \DB::statement('DROP INDEX IF EXISTS unique_default_per_user');
    }
};
