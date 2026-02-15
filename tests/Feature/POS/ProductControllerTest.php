<?php

use App\Models\Product;
use App\Models\SalesItem;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->unit = Unit::factory()->create();
});

describe('index', function () {
    it('displays products index page', function () {
        actingAs($this->user);

        $response = get('/products');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data')
            ->has('products.meta')
            ->has('filters')
        );
    });

    it('only shows products owned by authenticated user', function () {
        $otherUser = User::factory()->create();

        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        Product::factory()->create([
            'user_id' => $otherUser->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = get('/products');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data', 1)
        );
    });

    it('orders products by name', function () {
        $product1 = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Zebra Product',
        ]);

        $product2 = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Alpha Product',
        ]);

        $product3 = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Beta Product',
        ]);

        actingAs($this->user);

        $response = get('/products');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->where('products.data.0.product_name', 'Alpha Product')
            ->where('products.data.1.product_name', 'Beta Product')
            ->where('products.data.2.product_name', 'Zebra Product')
        );
    });

    it('requires authentication', function () {
        $response = get('/products');

        $response->assertRedirect(route('login'));
    });

    it('filters products by search query', function () {
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

        $response = get('/products?search=Apple');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.product_name', 'Apple Juice')
            ->where('filters.search', 'Apple')
        );
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

        $response = get('/products?search=apple');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.product_name', 'Apple Juice')
        );
    });

    it('filters products by exact barcode match', function () {
        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Apple Juice',
            'barcode' => '4901234567890',
        ]);

        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Banana Shake',
            'barcode' => '5901234567890',
        ]);

        actingAs($this->user);

        $response = get('/products?search=4901234567890');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data', 1)
            ->where('products.data.0.product_name', 'Apple Juice')
            ->where('products.data.0.barcode', '4901234567890')
        );
    });

    it('filters products by status', function () {
        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ]);

        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'status' => 'inactive',
        ]);

        actingAs($this->user);

        $response = get('/products?status=active');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data', 1)
            ->where('filters.status', 'active')
        );
    });

    it('paginates products with 15 per page', function () {
        Product::factory(20)->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = get('/products');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data', 15)
            ->where('products.meta.current_page', 1)
            ->where('products.meta.last_page', 2)
            ->where('products.meta.per_page', 15)
            ->where('products.meta.total', 20)
        );
    });

    it('navigates to second page of products', function () {
        Product::factory(20)->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = get('/products?page=2');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Index')
            ->has('products.data', 5)
            ->where('products.meta.current_page', 2)
        );
    });
});

describe('create', function () {
    it('displays create product page', function () {
        actingAs($this->user);

        $response = get('/products/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Create')
            ->has('units')
        );
    });

    it('requires authentication', function () {
        $response = get('/products/create');

        $response->assertRedirect(route('login'));
    });
});

describe('store', function () {
    it('creates a product successfully', function () {
        actingAs($this->user);

        $productData = [
            'product_name' => 'Test Product',
            'description' => 'Test Description',
            'unit_id' => $this->unit->id,
            'unit_price' => 100.50,
            'cost_price' => 50.25,
            'status' => 'active',
        ];

        $response = post('/products', $productData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Product created successfully!',
        ]);

        $this->assertDatabaseHas('products', [
            'user_id' => $this->user->id,
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100.50,
            'cost_price' => 50.25,
            'status' => 'active',
        ]);
    });

    it('validates required product_name field', function () {
        actingAs($this->user);

        $response = post('/products', [
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_name']);
    });

    it('validates product_name max length', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => str_repeat('a', 256),
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_name']);
    });

    it('validates product_name uniqueness per user', function () {
        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Duplicate Product',
        ]);

        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Duplicate Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_name']);
    });

    it('allows duplicate product names for different users', function () {
        $otherUser = User::factory()->create();

        Product::factory()->create([
            'user_id' => $otherUser->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Shared Name',
        ]);

        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Shared Name',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertOk();
    });

    it('validates description max length', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'description' => str_repeat('a', 1001),
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['description']);
    });

    it('validates required unit_id field', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['unit_id']);
    });

    it('validates unit_id exists', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => 99999,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['unit_id']);
    });

    it('validates required unit_price field', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['unit_price']);
    });

    it('validates unit_price is numeric', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 'not-a-number',
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['unit_price']);
    });

    it('validates unit_price minimum value', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => -1,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['unit_price']);
    });

    it('validates cost_price is numeric', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'cost_price' => 'not-a-number',
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cost_price']);
    });

    it('validates cost_price minimum value', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'cost_price' => -1,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['cost_price']);
    });

    it('validates required status field', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    });

    it('validates status is in allowed values', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    });

    it('validates barcode max length', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Test Product',
            'barcode' => str_repeat('1', 256),
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['barcode']);
    });

    it('creates a product with barcode successfully', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Barcode Product',
            'barcode' => '4901234567890',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'user_id' => $this->user->id,
            'product_name' => 'Barcode Product',
            'barcode' => '4901234567890',
        ]);
    });

    it('creates a product without barcode', function () {
        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'No Barcode Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'user_id' => $this->user->id,
            'product_name' => 'No Barcode Product',
            'barcode' => null,
        ]);
    });

    it('validates barcode uniqueness per user on store', function () {
        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'barcode' => '4901234567890',
        ]);

        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'Another Product',
            'barcode' => '4901234567890',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['barcode']);
    });

    it('allows duplicate barcode for different users on store', function () {
        $otherUser = User::factory()->create();

        Product::factory()->create([
            'user_id' => $otherUser->id,
            'unit_id' => $this->unit->id,
            'barcode' => '4901234567890',
        ]);

        actingAs($this->user);

        $response = post('/products', [
            'product_name' => 'My Product',
            'barcode' => '4901234567890',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertOk();
    });

    it('requires authentication', function () {
        $response = post('/products', [
            'product_name' => 'Test Product',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('edit', function () {
    it('displays edit product page', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = get("/products/{$product->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('products/Edit')
            ->has('product')
            ->has('units')
            ->where('product.id', $product->id)
        );
    });

    it('prevents viewing other users product', function () {
        $otherUser = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $otherUser->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = get("/products/{$product->id}/edit");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = get("/products/{$product->id}/edit");

        $response->assertRedirect(route('login'));
    });
});

describe('update', function () {
    it('updates product successfully', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Old Name',
            'unit_price' => 100,
        ]);

        actingAs($this->user);

        $response = put("/products/{$product->id}", [
            'product_name' => 'New Name',
            'unit_id' => $this->unit->id,
            'unit_price' => 200,
            'status' => 'active',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'message' => 'Product updated successfully!',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'product_name' => 'New Name',
            'unit_price' => 200,
        ]);
    });

    it('updates product barcode successfully', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'barcode' => null,
        ]);

        actingAs($this->user);

        $response = put("/products/{$product->id}", [
            'product_name' => $product->product_name,
            'barcode' => '4901234567890',
            'unit_id' => $this->unit->id,
            'unit_price' => $product->unit_price,
            'status' => $product->status,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'barcode' => '4901234567890',
        ]);
    });

    it('validates barcode uniqueness on update excludes current product', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'barcode' => '4901234567890',
        ]);

        actingAs($this->user);

        $response = put("/products/{$product->id}", [
            'product_name' => $product->product_name,
            'barcode' => '4901234567890',
            'unit_id' => $this->unit->id,
            'unit_price' => $product->unit_price,
            'status' => $product->status,
        ]);

        $response->assertOk();
    });

    it('validates barcode uniqueness with other products on update', function () {
        Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'barcode' => '4901234567890',
        ]);

        $product2 = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'barcode' => '5901234567890',
        ]);

        actingAs($this->user);

        $response = put("/products/{$product2->id}", [
            'product_name' => $product2->product_name,
            'barcode' => '4901234567890',
            'unit_id' => $this->unit->id,
            'unit_price' => $product2->unit_price,
            'status' => $product2->status,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['barcode']);
    });

    it('prevents updating other users product', function () {
        $otherUser = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $otherUser->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = put("/products/{$product->id}", [
            'product_name' => 'Updated Name',
            'unit_id' => $this->unit->id,
            'unit_price' => 150,
            'status' => 'active',
        ]);

        $response->assertForbidden();
    });

    it('validates unique product name excludes current product', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Original Name',
        ]);

        actingAs($this->user);

        $response = put("/products/{$product->id}", [
            'product_name' => 'Original Name',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertOk();
    });

    it('validates product name uniqueness with other products', function () {
        $product1 = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Product 1',
        ]);

        $product2 = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
            'product_name' => 'Product 2',
        ]);

        actingAs($this->user);

        $response = put("/products/{$product2->id}", [
            'product_name' => 'Product 1',
            'unit_id' => $this->unit->id,
            'unit_price' => 100,
            'status' => 'active',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_name']);
    });

    it('validates required fields', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = put("/products/{$product->id}", []);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['product_name', 'unit_id', 'unit_price', 'status']);
    });

    it('requires authentication', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = put("/products/{$product->id}", [
            'product_name' => 'Updated Name',
            'unit_id' => $this->unit->id,
            'unit_price' => 150,
            'status' => 'active',
        ]);

        $response->assertRedirect(route('login'));
    });
});

describe('destroy', function () {
    it('deletes product successfully', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = delete("/products/{$product->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
        ]);

        $this->assertDatabaseMissing('products', [
            'id' => $product->id,
        ]);
    });

    it('prevents deleting product with sales items', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        SalesItem::factory()->create([
            'product_id' => $product->id,
        ]);

        actingAs($this->user);

        $response = delete("/products/{$product->id}");

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'message' => 'Cannot delete this product because it has been used in sales transactions.',
        ]);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
        ]);
    });

    it('prevents deleting other users product', function () {
        $otherUser = User::factory()->create();
        $product = Product::factory()->create([
            'user_id' => $otherUser->id,
            'unit_id' => $this->unit->id,
        ]);

        actingAs($this->user);

        $response = delete("/products/{$product->id}");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $product = Product::factory()->create([
            'user_id' => $this->user->id,
            'unit_id' => $this->unit->id,
        ]);

        $response = delete("/products/{$product->id}");

        $response->assertRedirect(route('login'));
    });
});
