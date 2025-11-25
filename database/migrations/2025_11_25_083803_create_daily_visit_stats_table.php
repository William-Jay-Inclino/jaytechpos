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
        Schema::create('daily_visit_stats', function (Blueprint $table) {
            $table->id();
            $table->timestampTz('date');
            $table->integer('total_visits')->default(0);
            $table->integer('unique_visits')->default(0);
            $table->integer('page_views')->default(0);
            $table->string('top_page')->nullable();
            $table->string('top_referer')->nullable();
            $table->integer('mobile_visits')->default(0);
            $table->integer('desktop_visits')->default(0);
            $table->integer('tablet_visits')->default(0);
            $table->index('date');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_visit_stats');
    }
};
