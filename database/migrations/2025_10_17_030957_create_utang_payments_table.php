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
        Schema::create('utang_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->decimal('payment_amount', 15, 2);
            $table->timestamp('payment_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'payment_date']); // For date-range payment queries per user
            $table->index(['user_id', 'customer_id']); // For customer-specific payment lookup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utang_payments');
    }
};
