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
        Schema::create('utang_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->decimal('beginning_balance', 15, 2)->default(0);
            $table->timestamp('computation_date'); // every 1st of the month
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->timestamps();

            // Performance indexes
            $table->index(['user_id', 'computation_date']); // For monthly tracking queries per user
            $table->index(['user_id', 'customer_id']); // For customer-specific tracking lookup
            $table->index(['customer_id', 'computation_date']); // For finding latest tracking per customer
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('utang_trackings');
    }
};
