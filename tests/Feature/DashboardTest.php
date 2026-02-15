<?php

use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns cash and utang sales breakdown in daily stats', function () {
    $user = User::factory()->create();

    // Create cash sales
    Sale::factory()->count(2)->create([
        'user_id' => $user->id,
        'payment_type' => 'cash',
        'total_amount' => 100.00,
        'transaction_date' => now(),
    ]);

    // Create utang sales
    Sale::factory()->count(3)->create([
        'user_id' => $user->id,
        'payment_type' => 'utang',
        'total_amount' => 50.00,
        'transaction_date' => now(),
    ]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('Dashboard')
        ->has('dailyStats', fn ($stats) => $stats
            ->where('total_sales_today', fn ($val) => (float) $val === 350.00)
            ->where('cash_sales_today', fn ($val) => (float) $val === 200.00)
            ->where('utang_sales_today', fn ($val) => (float) $val === 150.00)
        )
    );
});
