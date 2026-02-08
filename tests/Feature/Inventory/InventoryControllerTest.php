<?php

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->unit = Unit::factory()->create();
});

describe('index', function () {
    it('displays inventory index page', function () {
        actingAs($this->user);

        $response = get('/inventory');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->has('products.data')
            ->has('products.current_page')
            ->has('filters')
        );
    });

    it('requires authentication', function () {
        $response = get('/inventory');

        $response->assertRedirect(route('login'));
    });

    it('filters products by search query case insensitively', function () {
        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Apple Juice',
        ]);

        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Banana Shake',
        ]);

        actingAs($this->user);

        $response = get('/inventory?search=apple');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->has('products.data', 1)
            ->where('products.data.0.product_name', 'Apple Juice')
            ->where('filters.search', 'apple')
        );
    });

    it('paginates products with 15 per page', function () {
        Product::factory(20)->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = get('/inventory');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->has('products.data', 15)
            ->where('products.current_page', 1)
            ->where('products.last_page', 2)
            ->where('products.per_page', 15)
            ->where('products.total', 20)
        );
    });

    it('navigates to second page of products', function () {
        Product::factory(20)->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = get('/inventory?page=2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('inventory/Index')
            ->has('products.data', 5)
            ->where('products.current_page', 2)
        );
    });
});
