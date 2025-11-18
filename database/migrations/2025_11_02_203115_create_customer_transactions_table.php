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
        Schema::create('customer_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->enum('transaction_type', ['sale', 'utang_payment', 'monthly_interest', 'starting_balance', 'balance_update']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->decimal('previous_balance', 15, 2)->default(0);
            $table->decimal('new_balance', 15, 2)->default(0);
            $table->text('transaction_desc')->nullable();
            $table->timestamp('transaction_date');
            $table->decimal('transaction_amount', 15, 2);
            $table->timestamps();

            $table->index(['customer_id', 'transaction_date']);
            $table->index(['user_id', 'transaction_date']);
            $table->index(['transaction_type', 'reference_id']);
            $table->index('transaction_date', 'customer_transactions_transaction_date_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};
