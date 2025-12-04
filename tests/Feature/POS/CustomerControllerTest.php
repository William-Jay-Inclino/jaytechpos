<?php

use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create(['status' => 'active']);
    $this->otherUser = User::factory()->create(['status' => 'active']);
});

describe('index', function () {
    it('displays customers index page', function () {
        $response = $this->actingAs($this->user)->get('/customers');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('customers/Index')
            ->has('customers')
        );
    });

    it('only shows customers owned by authenticated user', function () {
        Customer::factory()->count(3)->create(['user_id' => $this->user->id]);
        Customer::factory()->count(2)->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get('/customers');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->has('customers', 3)
        );
    });

    it('orders customers by name', function () {
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Zara']);
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Alice']);
        Customer::factory()->create(['user_id' => $this->user->id, 'name' => 'Mike']);

        $response = $this->actingAs($this->user)->get('/customers');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->where('customers.0.name', 'Alice')
            ->where('customers.1.name', 'Mike')
            ->where('customers.2.name', 'Zara')
        );
    });

    it('requires authentication', function () {
        $response = $this->get('/customers');

        $response->assertRedirect('/login');
    });
});

describe('create', function () {
    it('displays create customer page', function () {
        $response = $this->actingAs($this->user)->get('/customers/create');

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('customers/Create')
            ->has('defaultInterestRate')
        );
    });

    it('requires authentication', function () {
        $response = $this->get('/customers/create');

        $response->assertRedirect('/login');
    });
});

describe('store', function () {
    it('creates a customer successfully', function () {
        $data = [
            'name' => 'John Doe',
            'mobile_number' => '09123456789',
            'starting_balance' => 0,
            'remarks' => 'Regular customer',
            'interest_rate' => 5.0,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'msg' => 'Customer created successfully!',
        ]);

        $this->assertDatabaseHas('customers', [
            'user_id' => $this->user->id,
            'name' => 'John Doe',
            'mobile_number' => '09123456789',
            'has_utang' => false,
        ]);
    });

    it('creates customer with starting balance', function () {
        $data = [
            'name' => 'Jane Smith',
            'mobile_number' => '09987654321',
            'starting_balance' => 1000.50,
            'remarks' => null,
            'interest_rate' => 3.5,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertSuccessful();

        $customer = Customer::where('name', 'Jane Smith')->first();

        expect($customer)->not->toBeNull();
        expect($customer->has_utang)->toBeTrue();

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'transaction_type' => 'starting_balance',
            'previous_balance' => 0,
            'new_balance' => 1000.50,
            'transaction_amount' => 1000.50,
        ]);
    });

    it('validates required name field', function () {
        $data = [
            'name' => '',
            'mobile_number' => '09123456789',
            'starting_balance' => 0,
            'interest_rate' => 5.0,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates name max length', function () {
        $data = [
            'name' => str_repeat('a', 256),
            'mobile_number' => '09123456789',
            'starting_balance' => 0,
            'interest_rate' => 5.0,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates mobile number max length', function () {
        $data = [
            'name' => 'John Doe',
            'mobile_number' => str_repeat('1', 21),
            'starting_balance' => 0,
            'interest_rate' => 5.0,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['mobile_number']);
    });

    it('validates starting balance is numeric', function () {
        $data = [
            'name' => 'John Doe',
            'mobile_number' => '09123456789',
            'starting_balance' => 'invalid',
            'interest_rate' => 5.0,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['starting_balance']);
    });

    it('validates starting balance minimum value', function () {
        $data = [
            'name' => 'John Doe',
            'mobile_number' => '09123456789',
            'starting_balance' => -100,
            'interest_rate' => 5.0,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['starting_balance']);
    });

    it('validates interest rate is numeric', function () {
        $data = [
            'name' => 'John Doe',
            'mobile_number' => '09123456789',
            'starting_balance' => 0,
            'interest_rate' => 'invalid',
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['interest_rate']);
    });

    it('validates interest rate range', function () {
        $data = [
            'name' => 'John Doe',
            'mobile_number' => '09123456789',
            'starting_balance' => 0,
            'interest_rate' => 150,
        ];

        $response = $this->actingAs($this->user)->postJson('/customers', $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['interest_rate']);
    });

    it('requires authentication', function () {
        $response = $this->postJson('/customers', ['name' => 'John Doe']);

        $response->assertUnauthorized();
    });
});

describe('edit', function () {
    it('displays edit customer page', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/customers/{$customer->id}/edit");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('customers/Edit')
            ->has('customer')
            ->has('defaultInterestRate')
            ->where('customer.id', $customer->id)
        );
    });

    it('prevents viewing other users customer', function () {
        $customer = Customer::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)->get("/customers/{$customer->id}/edit");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $customer = Customer::factory()->create();

        $response = $this->get("/customers/{$customer->id}/edit");

        $response->assertRedirect('/login');
    });
});

describe('update', function () {
    it('updates customer successfully', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'name' => 'Updated Name',
            'mobile_number' => '09111222333',
            'remarks' => 'Updated remarks',
            'interest_rate' => 7.5,
        ];

        $response = $this->actingAs($this->user)->putJson("/customers/{$customer->id}", $data);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'msg' => 'Customer updated successfully!',
        ]);

        $this->assertDatabaseHas('customers', [
            'id' => $customer->id,
            'name' => 'Updated Name',
            'mobile_number' => '09111222333',
            'remarks' => 'Updated remarks',
            'interest_rate' => 7.5,
        ]);
    });

    it('prevents updating other users customer', function () {
        $customer = Customer::factory()->create(['user_id' => $this->otherUser->id]);

        $data = [
            'name' => 'Updated Name',
            'mobile_number' => '09111222333',
            'interest_rate' => 7.5,
        ];

        $response = $this->actingAs($this->user)->putJson("/customers/{$customer->id}", $data);

        $response->assertForbidden();
    });

    it('validates required name field', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'name' => '',
            'mobile_number' => '09111222333',
            'interest_rate' => 7.5,
        ];

        $response = $this->actingAs($this->user)->putJson("/customers/{$customer->id}", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('requires authentication', function () {
        $customer = Customer::factory()->create();

        $response = $this->putJson("/customers/{$customer->id}", ['name' => 'Updated']);

        $response->assertUnauthorized();
    });
});

describe('updateBalance', function () {
    it('updates customer balance successfully', function () {
        $customer = Customer::factory()->create([
            'user_id' => $this->user->id,
            'has_utang' => false,
        ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 500,
        ]);

        $data = [
            'balance' => 1000,
            'note' => 'Balance adjustment',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/customers/{$customer->id}/balance", $data);

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'msg' => 'Customer balance updated successfully!',
        ]);

        $this->assertDatabaseHas('customer_transactions', [
            'customer_id' => $customer->id,
            'transaction_type' => 'balance_update',
            'previous_balance' => 500,
            'new_balance' => 1000,
            'transaction_amount' => 1000,
            'transaction_desc' => 'Balance adjustment',
        ]);

        expect($customer->fresh()->has_utang)->toBeTrue();
    });

    it('marks customer as no utang when balance is zero', function () {
        $customer = Customer::factory()->withUtang()->create([
            'user_id' => $this->user->id,
        ]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 500,
        ]);

        $data = [
            'balance' => 0,
            'note' => 'Balance cleared',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/customers/{$customer->id}/balance", $data);

        $response->assertSuccessful();

        expect($customer->fresh()->has_utang)->toBeFalse();
    });

    it('prevents updating with same balance', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
            'new_balance' => 500,
        ]);

        $data = [
            'balance' => 500,
            'note' => 'Same balance',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/customers/{$customer->id}/balance", $data);

        $response->assertStatus(422);
        $response->assertJson([
            'success' => false,
            'msg' => 'New balance must be different from current balance.',
        ]);
    });

    it('prevents updating other users customer balance', function () {
        $customer = Customer::factory()->create(['user_id' => $this->otherUser->id]);

        $data = [
            'balance' => 1000,
            'note' => 'Balance adjustment',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/customers/{$customer->id}/balance", $data);

        $response->assertForbidden();
    });

    it('validates required balance field', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $data = [
            'note' => 'Missing balance',
        ];

        $response = $this->actingAs($this->user)
            ->patchJson("/customers/{$customer->id}/balance", $data);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['balance']);
    });

    it('requires authentication', function () {
        $customer = Customer::factory()->create();

        $response = $this->patchJson("/customers/{$customer->id}/balance", ['balance' => 1000]);

        $response->assertUnauthorized();
    });
});

describe('destroy', function () {
    it('deletes customer without relations successfully', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/customers/{$customer->id}");

        $response->assertSuccessful();
        $response->assertJson([
            'success' => true,
            'msg' => 'Customer deleted successfully.',
        ]);

        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    });

    it('prevents deleting customer with sales', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);
        Sale::factory()->create(['customer_id' => $customer->id, 'user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/customers/{$customer->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete customer with existing sales or transaction history.',
        ]);

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    });

    it('prevents deleting customer with transactions', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);
        CustomerTransaction::factory()->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/customers/{$customer->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete customer with existing sales or transaction history.',
        ]);

        $this->assertDatabaseHas('customers', ['id' => $customer->id]);
    });

    it('prevents deleting other users customer', function () {
        $customer = Customer::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/customers/{$customer->id}");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $customer = Customer::factory()->create();

        $response = $this->deleteJson("/customers/{$customer->id}");

        $response->assertUnauthorized();
    });
});

describe('transactions', function () {
    it('displays customer transactions page', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->get("/customers/{$customer->id}/transactions");

        $response->assertSuccessful();
        $response->assertInertia(fn ($page) => $page
            ->component('customers/Transactions')
            ->has('customer')
            ->where('customer.id', $customer->id)
        );
    });

    it('prevents viewing other users customer transactions', function () {
        $customer = Customer::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->get("/customers/{$customer->id}/transactions");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $customer = Customer::factory()->create();

        $response = $this->get("/customers/{$customer->id}/transactions");

        $response->assertRedirect('/login');
    });
});

describe('getTransactions', function () {
    it('returns customer transactions data', function () {
        $customer = Customer::factory()->create(['user_id' => $this->user->id]);

        CustomerTransaction::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/customers/{$customer->id}/transactions");

        $response->assertSuccessful();
        $response->assertJsonStructure([
            'transactions',
        ]);
    });

    it('prevents viewing other users customer transactions data', function () {
        $customer = Customer::factory()->create(['user_id' => $this->otherUser->id]);

        $response = $this->actingAs($this->user)
            ->getJson("/api/customers/{$customer->id}/transactions");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $customer = Customer::factory()->create();

        $response = $this->getJson("/api/customers/{$customer->id}/transactions");

        $response->assertUnauthorized();
    });
});
