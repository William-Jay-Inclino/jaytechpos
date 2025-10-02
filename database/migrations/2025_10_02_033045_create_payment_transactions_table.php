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

        // Create payment_methods table only if it doesn't exist
        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payment_method_id')->constrained()->restrictOnDelete();
            $table->decimal('amount', 15, 2); // Amount paid via this method
            $table->decimal('amount_tendered', 15, 2)->nullable(); // Only for cash payments
            $table->decimal('change_amount', 15, 2)->nullable(); // Only for cash payments
            $table->string('reference_number')->nullable(); // Card auth codes, check numbers, etc.
            $table->string('card_last_four')->nullable(); // Last 4 digits of card for reference
            $table->string('authorization_code')->nullable(); // Payment processor auth code
            $table->enum('status', ['pending', 'completed', 'failed', 'refunded'])->default('completed');
            $table->timestamp('processed_at')->useCurrent(); // When payment was processed
            $table->text('processor_response')->nullable(); // Full response from payment processor
            $table->timestamps();
            
            // Indexes for reporting and lookups
            $table->index(['sale_id', 'payment_method_id']);
            $table->index('processed_at');
            $table->index('reference_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
        // Only drop payment_methods if this migration created it
        // Note: Be careful in production - this table might be used by other parts of the system
    }
};
