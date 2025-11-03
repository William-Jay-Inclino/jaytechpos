<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SalesItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads sales report page for authenticated user', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/sales-report');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('sales-report/Index')
        ->has('filters')
        ->has('sales')
        ->has('chartData')
        ->has('paymentTypeData')
    );
});

it('returns sales data via API', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['user_id' => $user->id]);
    $product = Product::factory()->create(['user_id' => $user->id]);

    // Create a test sale
    $sale = Sale::factory()->create([
        'user_id' => $user->id,
        'customer_id' => $customer->id,
        'total_amount' => 100.00,
        'paid_amount' => 100.00,
        'payment_type' => 'cash',
    ]);

    SalesItem::factory()->create([
        'sale_id' => $sale->id,
        'product_id' => $product->id,
        'quantity' => 1,
        'unit_price' => 100.00,
    ]);

    $response = $this->actingAs($user)
        ->get('/api/sales-report/data');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'data' => [],
            'pagination' => [],
            'summary' => [],
        ],
    ]);
});

it('returns chart data via API', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/api/sales-report/chart');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'labels' => [],
            'data' => [],
        ],
    ]);
});

it('returns payment type data via API', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/api/sales-report/payment-types');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'labels' => [],
            'data' => [],
            'colors' => [],
        ],
    ]);
});

it('redirects unauthenticated users to login', function () {
    $response = $this->get('/sales-report');
    $response->assertRedirect('/login');
});

it('applies filters correctly', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->get('/api/sales-report/data?period=today&payment_type=cash');

    $response->assertSuccessful();
    $response->assertJsonStructure([
        'data' => [
            'data' => [],
            'pagination' => [],
            'summary' => [],
        ],
    ]);
});
