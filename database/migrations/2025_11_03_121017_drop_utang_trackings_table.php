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
        Schema::dropIfExists('utang_trackings');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate the table if needed for rollback
        Schema::create('utang_trackings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->decimal('beginning_balance', 15, 2)->default(0);
            $table->timestamp('computation_date');
            $table->decimal('interest_rate', 5, 2)->default(0);
            $table->timestamps();

            $table->index(['user_id', 'computation_date']);
            $table->index(['user_id', 'customer_id']);
            $table->index(['customer_id', 'computation_date']);
        });
    }
};
