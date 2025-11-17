<?php

use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\Customer;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::Admin,
        'status' => 'active',
    ]);
});

it('displays activity logs page for admin users', function () {
    $response = $this->actingAs($this->admin)->get('/admin/activity-logs');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/ActivityLogs')
        ->has('activities.data')
    );
});

it('shows activity logs with pagination', function () {
    // Create some activities
    User::factory()->count(20)->create();

    $response = $this->actingAs($this->admin)->get('/admin/activity-logs');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/ActivityLogs')
        ->has('activities.data', 15) // Default pagination is 15
        ->has('activities.meta')
        ->has('activities.links')
    );
});

it('filters activities by event type', function () {
    // Create product (created event)
    $product = Product::factory()->create();

    // Update product (updated event)
    $product->update(['product_name' => 'Updated Name']);

    Activity::query()->delete();

    // Create new activities
    $user = User::factory()->create();
    $user->update(['name' => 'Updated User']);

    $response = $this->actingAs($this->admin)->get('/admin/activity-logs?event=updated');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.event', 'updated')
    );
});

it('filters activities by search query', function () {
    $user = User::factory()->create(['name' => 'John Doe']);
    $customer = Customer::factory()->create([
        'user_id' => $user->id,
        'name' => 'Special Customer',
    ]);

    $response = $this->actingAs($this->admin)->get('/admin/activity-logs?search=John');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.search', 'John')
    );
});

it('filters activities by subject type', function () {
    Product::factory()->create();
    Customer::factory()->create();

    $response = $this->actingAs($this->admin)->get('/admin/activity-logs?subject_type=Product');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.subject_type', 'Product')
    );
});

it('requires authentication', function () {
    $response = $this->get('/admin/activity-logs');

    $response->assertRedirect('/login');
});

it('shows empty state when no activities exist', function () {
    Activity::query()->delete();

    $response = $this->actingAs($this->admin)->get('/admin/activity-logs');

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->has('activities.data', 0)
    );
});
