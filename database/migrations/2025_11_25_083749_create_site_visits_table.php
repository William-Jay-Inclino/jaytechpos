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
        Schema::create('site_visits', function (Blueprint $table) {
            $table->id();
            $table->string('session_id');
            $table->string('ip_address');
            $table->string('user_agent');
            $table->string('referer')->nullable();
            $table->string('page_url');
            $table->string('country')->nullable();
            $table->string('region')->nullable();
            $table->string('city')->nullable();
            $table->string('device_type')->nullable();
            $table->string('browser')->nullable();
            $table->string('os')->nullable();
            $table->boolean('is_bot')->default(false);
            $table->boolean('is_unique')->default(false);
            $table->integer('page_views')->default(1);
            $table->timestampTz('visited_at')->useCurrent();
            $table->index('visited_at');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('site_visits');
    }
};
