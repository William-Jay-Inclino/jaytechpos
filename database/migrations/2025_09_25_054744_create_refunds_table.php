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
        Schema::create('refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained('sales', 'id')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('manager_id')->constrained('users', 'id')->restrictOnDelete();
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('refunds');
    }
};
