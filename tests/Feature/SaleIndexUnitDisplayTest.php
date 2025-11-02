<?php

use App\Models\Product;
use App\Models\Unit;
use App\Models\User;

test('sales index loads products with unit relationship', function () {
    $user = User::factory()->create();
    
    // Create a unit
    $unit = Unit::create([
        'unit_name' => 'Pieces',
        'abbreviation' => 'pcs'
    ]);
    
    // Create a product with the unit
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'unit_id' => $unit->id,
        'status' => 'active'
    ]);
    
    $response = $this->actingAs($user)
        ->get('/sales');
    
    $response->assertStatus(200)
        ->assertInertia(function ($page) use ($product, $unit) {
            $page->component('sales/Index')
                ->has('products')
                ->where('products.0.id', $product->id)
                ->where('products.0.unit.unit_name', $unit->unit_name)
                ->where('products.0.unit.abbreviation', $unit->abbreviation);
        });
});

test('product unit name displays correctly in cart items', function () {
    $user = User::factory()->create();
    
    // Create a unit
    $unit = Unit::create([
        'unit_name' => 'Kilograms',
        'abbreviation' => 'kg'
    ]);
    
    // Create a product with the unit
    $product = Product::factory()->create([
        'user_id' => $user->id,
        'unit_id' => $unit->id,
        'status' => 'active',
        'product_name' => 'Test Product',
        'unit_price' => 50.00
    ]);
    
    $response = $this->actingAs($user)
        ->get('/sales');
    
    $response->assertStatus(200);
    
    // The product should have the unit relationship loaded
    $products = $response->viewData('page')['props']['products'];
    $testProduct = collect($products)->firstWhere('id', $product->id);
    
    expect($testProduct)->not->toBeNull();
    expect($testProduct['unit'])->not->toBeNull();
    expect($testProduct['unit']['unit_name'])->toBe('Kilograms');
});