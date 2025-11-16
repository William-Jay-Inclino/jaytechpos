<?php

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->customer = Customer::factory()->create(['user_id' => $this->user->id]);
    $this->otherCustomer = Customer::factory()->create(['user_id' => $this->otherUser->id]);
});

it('allows users to edit their own customers', function () {
    $response = $this->actingAs($this->user)->get("/customers/{$this->customer->id}/edit");

    $response->assertOk();
});

it('denies users from editing other users customers', function () {
    $response = $this->actingAs($this->user)->get("/customers/{$this->otherCustomer->id}/edit");

    $response->assertForbidden();
});

it('allows users to update their own customers', function () {
    $updateData = [
        'name' => 'Updated Customer Name',
        'mobile_number' => '09876543210',
        'remarks' => 'Updated remarks',
        'interest_rate' => 2.5,
    ];

    $response = $this->actingAs($this->user)->put("/customers/{$this->customer->id}", $updateData);

    $response->assertRedirect('/customers');
});

it('denies users from updating other users customers', function () {
    $updateData = [
        'name' => 'Hacked Customer Name',
        'mobile_number' => '09876543210',
    ];

    $response = $this->actingAs($this->user)->put("/customers/{$this->otherCustomer->id}", $updateData);

    $response->assertForbidden();
});

it('allows users to delete their own customers without related records', function () {
    $response = $this->actingAs($this->user)->delete("/customers/{$this->customer->id}");

    $response->assertRedirect('/customers');
});

it('denies users from deleting other users customers', function () {
    $response = $this->actingAs($this->user)->delete("/customers/{$this->otherCustomer->id}");

    $response->assertForbidden();
});

it('allows users to view transaction history of their own customers', function () {
    $response = $this->actingAs($this->user)->get("/customers/{$this->customer->id}/transactions");

    $response->assertOk();
});

it('denies users from viewing transaction history of other users customers', function () {
    $response = $this->actingAs($this->user)->get("/customers/{$this->otherCustomer->id}/transactions");

    $response->assertForbidden();
});

it('allows users to get transaction data of their own customers via API', function () {
    $response = $this->actingAs($this->user)->get("/api/customers/{$this->customer->id}/transactions");

    $response->assertOk();
});

it('denies users from getting transaction data of other users customers via API', function () {
    $response = $this->actingAs($this->user)->get("/api/customers/{$this->otherCustomer->id}/transactions");

    $response->assertForbidden();
});
