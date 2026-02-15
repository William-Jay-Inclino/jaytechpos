<?php

use App\Enums\StockMovementType;
use App\Models\Inventory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\postJson;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    actingAs($this->user);
});

test('can stock in product', function () {
    $product = Product::factory()->create();

    $data = [
        'product_id' => $product->id,
        'quantity' => 100.50,
        'reference' => 'PO-12345',
        'remarks' => 'Initial stock',
    ];

    $response = postJson('/api/stock-movements/stock-in', $data);

    $response->assertCreated()
        ->assertJson([
            'success' => true,
            'message' => 'Stock added successfully.',
        ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => StockMovementType::IN->value,
        'quantity' => 100.50,
    ]);

    assertDatabaseHas('inventories', [
        'product_id' => $product->id,
        'quantity' => 100.50,
    ]);
});

test('can stock in existing inventory', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 50,
    ]);

    $data = [
        'product_id' => $product->id,
        'quantity' => 25.50,
    ];

    $response = postJson('/api/stock-movements/stock-in', $data);

    $response->assertCreated();

    $inventory = Inventory::where('product_id', $product->id)->first();
    expect($inventory->quantity)->toEqual(75.50);
});

test('stock in validation fails for invalid data', function () {
    $response = postJson('/api/stock-movements/stock-in', [
        'product_id' => 999999,
        'quantity' => -10,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);
});

test('can adjust stock upward', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 100,
    ]);

    $data = [
        'product_id' => $product->id,
        'quantity' => 150,
        'reference' => 'ADJ-001',
        'remarks' => 'Inventory count adjustment',
    ];

    $response = postJson('/api/stock-movements/adjustment', $data);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Stock adjusted successfully.',
        ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => StockMovementType::ADJUSTMENT->value,
        'quantity' => 150,
    ]);

    $inventory = Inventory::where('product_id', $product->id)->first();
    expect($inventory->quantity)->toEqual(150);
});

test('can adjust stock downward', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 100,
    ]);

    $data = [
        'product_id' => $product->id,
        'quantity' => 70,
        'remarks' => 'Damage write-off',
    ];

    $response = postJson('/api/stock-movements/adjustment', $data);

    $response->assertSuccessful();

    assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => StockMovementType::ADJUSTMENT->value,
        'quantity' => 70,
    ]);

    $inventory = Inventory::where('product_id', $product->id)->first();
    expect($inventory->quantity)->toEqual(70);
});

test('can adjust stock to zero', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 50,
    ]);

    $data = [
        'product_id' => $product->id,
        'quantity' => 0,
    ];

    $response = postJson('/api/stock-movements/adjustment', $data);

    $response->assertSuccessful();

    $inventory = Inventory::where('product_id', $product->id)->first();
    expect($inventory->quantity)->toEqual(0);
});

test('stock adjustment fails when inventory not found', function () {
    $product = Product::factory()->create();

    $data = [
        'product_id' => $product->id,
        'quantity' => 50,
    ];

    $response = postJson('/api/stock-movements/adjustment', $data);

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Product inventory not found. Please create inventory record first.',
        ]);
});

test('can stock out product', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 100,
    ]);

    $data = [
        'product_id' => $product->id,
        'quantity' => 25.50,
        'reference' => 'SALE-001',
        'remarks' => 'Customer purchase',
    ];

    $response = postJson('/api/stock-movements/stock-out', $data);

    $response->assertSuccessful()
        ->assertJson([
            'success' => true,
            'message' => 'Stock removed successfully.',
        ]);

    assertDatabaseHas('stock_movements', [
        'product_id' => $product->id,
        'type' => StockMovementType::OUT->value,
        'quantity' => 25.50,
    ]);

    $inventory = Inventory::where('product_id', $product->id)->first();
    expect($inventory->quantity)->toEqual(74.50);
});

test('stock out fails when inventory not found', function () {
    $product = Product::factory()->create();

    $data = [
        'product_id' => $product->id,
        'quantity' => 10,
    ];

    $response = postJson('/api/stock-movements/stock-out', $data);

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Product inventory not found.',
        ]);
});

test('stock out fails when insufficient stock', function () {
    $product = Product::factory()->create();
    Inventory::factory()->create([
        'product_id' => $product->id,
        'quantity' => 10,
    ]);

    $data = [
        'product_id' => $product->id,
        'quantity' => 50,
    ];

    $response = postJson('/api/stock-movements/stock-out', $data);

    $response->assertStatus(422)
        ->assertJsonFragment([
            'success' => false,
        ]);
});

test('stock out validation fails for invalid data', function () {
    $response = postJson('/api/stock-movements/stock-out', [
        'product_id' => 999999,
        'quantity' => -10,
    ]);

    $response->assertStatus(422)
        ->assertJson([
            'success' => false,
            'message' => 'Validation failed.',
        ]);
});
