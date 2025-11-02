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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_categories', 'id')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units', 'id')->restrictOnDelete();
            $table->string('product_name')->unique();
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'status']); // For filtering active products by user (availableForSale scope)
            $table->index(['user_id', 'category_id']); // For filtering products by category per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
