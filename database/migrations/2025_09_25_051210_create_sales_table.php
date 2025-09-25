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

        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sales_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('payment_method_id')->constrained('payment_methods', 'id')->restrictOnDelete();
            $table->foreignId('sales_status_id')->constrained('sales_statuses', 'id')->restrictOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('discount_amount', 15, 2);
            $table->decimal('vat_amount', 15, 2);
            $table->decimal('net_amount', 15, 2);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('sales_statuses');
    }
};
