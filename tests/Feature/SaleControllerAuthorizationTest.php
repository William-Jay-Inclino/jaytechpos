<?php

use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->customer = Customer::factory()->create(['user_id' => $this->user->id]);
    $this->otherCustomer = Customer::factory()->create(['user_id' => $this->otherUser->id]);
    $this->product = Product::factory()->create(['user_id' => $this->user->id]);
    $this->sale = Sale::factory()->create(['user_id' => $this->user->id]);
    $this->otherSale = Sale::factory()->create(['user_id' => $this->otherUser->id]);
});

describe('SaleController → index authorization', function () {
    it('allows authenticated users to view sales index', function () {
        $response = $this->actingAs($this->user)->get('/sales');

        $response->assertSuccessful();
    });

    it('redirects unauthenticated users to login', function () {
        $response = $this->get('/sales');

        $response->assertRedirect('/login');
    });
});

describe('SaleController → store authorization', function () {
    it('allows authenticated users to create sales', function () {
        $saleData = [
            'customer_id' => $this->customer->id,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'payment_type' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/sales', $saleData);

        $response->assertSuccessful();
    });

    it('prevents unauthenticated users from creating sales', function () {
        $saleData = [
            'customer_id' => $this->customer->id,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'payment_type' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->post('/sales', $saleData);

        $response->assertRedirect('/login');
    });

    it('allows users to create sales without a customer', function () {
        $saleData = [
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'payment_type' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/sales', $saleData);

        $response->assertSuccessful();
    });

    it('prevents users from creating sales for other users customers', function () {
        $saleData = [
            'customer_id' => $this->otherCustomer->id,
            'total_amount' => 100.00,
            'paid_amount' => 100.00,
            'payment_type' => 'cash',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'unit_price' => 50.00,
                ],
            ],
        ];

        $response = $this->actingAs($this->user)->post('/sales', $saleData);

        $response->assertForbidden();
    });
});
