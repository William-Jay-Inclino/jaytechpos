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

        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->restrictOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers', 'id')->restrictOnDelete();
            $table->decimal('total_amount', 15, 2);
            $table->decimal('paid_amount', 15, 2)->default(0);
            $table->decimal('amount_tendered', 15, 2)->nullable();
            $table->decimal('deduct_from_balance', 15, 2)->nullable();
            $table->string('invoice_number');
            $table->enum('payment_type', ['cash', 'utang'])->default('cash');
            $table->timestamp('transaction_date')->useCurrent();
            $table->text('notes')->nullable();
            $table->timestamps();

            // Performance indexes
            $table->unique(['user_id', 'invoice_number']);
            $table->index(['user_id', 'transaction_date']); // For date-range sales queries per user
            $table->index(['user_id', 'payment_type']); // For filtering by payment type per user
            $table->index(['user_id', 'customer_id']); // For customer-specific sales lookup
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
        Schema::dropIfExists('sales_statuses');
    }
};
