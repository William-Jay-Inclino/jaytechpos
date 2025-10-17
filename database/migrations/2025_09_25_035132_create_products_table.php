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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('category_id')->constrained('product_categories', 'id')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers', 'id')->restrictOnDelete();
            $table->foreignId('unit_id')->constrained('units', 'id')->restrictOnDelete();
            $table->string('sku')->unique();
            $table->string('barcode')->unique()->nullable();
            $table->string('product_name');
            $table->text('description')->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('cost_price', 15, 2);
            $table->enum('vat_type', ['vat', 'vat_exempt', 'vat_zero_rated', 'non_vat'])->default('non_vat');
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
