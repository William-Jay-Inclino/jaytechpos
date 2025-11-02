<?php

use App\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('guests are redirected to the login page', function () {
    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);
});

test('authenticated users can fetch cash flow data via API', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/api/dashboard/cash-flow?year=2024');
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            '*' => [
                'month',
                'income',
                'expense',
                'cash_flow',
            ],
        ],
        'currentYear',
    ]);
});

test('authenticated users can fetch sales chart data via API', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/api/dashboard/sales-chart?year=2024');
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'labels' => [],
            'data' => [],
        ],
        'currentYear',
    ]);
});

test('authenticated users can fetch best selling products data via API', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get('/api/dashboard/best-selling-products?year=2024');
    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'today' => [],
            'week' => [],
            'month' => [],
            'year' => [],
        ],
        'currentYear',
    ]);
});

test('dashboard contains required data structure', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $response = $this->get(route('dashboard'));
    $response->assertStatus(200);

    // Test that the Inertia response contains the expected data structure
    $response->assertInertia(fn ($assert) => $assert
        ->component('Dashboard')
        ->has('dailyStats', fn ($stats) => $stats
            ->has('total_sales_today')
            ->has('total_cash_today')
            ->has('utang_payments_today')
        )
        ->has('utangStats', fn ($stats) => $stats
            ->has('total_amount_receivable')
        )
        ->has('bestSellingProducts')
        ->has('cashFlowData')
        ->has('salesChartData')
        ->has('currentYear')
    );
});
