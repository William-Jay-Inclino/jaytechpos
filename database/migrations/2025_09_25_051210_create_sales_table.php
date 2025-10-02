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
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('sales_status_id')->constrained('sales_statuses', 'id')->restrictOnDelete();
            $table->decimal('subtotal', 15, 2); // Before discounts and VAT
            $table->decimal('total_amount', 15, 2); // Final total after all calculations
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('vat_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2); // Amount to be paid
            $table->string('invoice_number')->unique();
            $table->timestamp('transaction_date')->useCurrent(); // Separate from created_at for reporting
            $table->string('receipt_number')->nullable(); // For receipt printing
            $table->text('notes')->nullable(); // Additional transaction notes
            $table->timestamps();
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
