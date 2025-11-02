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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->string('name');
            $table->string('mobile_number')->unique();
            $table->text('remarks')->nullable();
            $table->boolean('has_utang')->default(false);
            $table->decimal('interest_rate', 5, 2)->nullable();
            $table->timestamps();

            // Performance indexes
            $table->index('user_id'); // For user-specific customer queries
            $table->index(['user_id', 'has_utang']); // For filtering customers with/without utang
            $table->index(['user_id', 'name']); // For user customer search by name
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
