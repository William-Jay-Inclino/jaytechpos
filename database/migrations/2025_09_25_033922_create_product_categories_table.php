<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->string('name');
            $table->string('description')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->boolean('is_default')->default(false);
            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'status']); // For filtering active categories by user
            $table->unique(['user_id', 'name']); // Category names unique per user
        });

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
        Schema::dropIfExists('product_categories');
    }
};
