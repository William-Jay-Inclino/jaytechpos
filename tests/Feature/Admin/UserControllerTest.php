<?php

use App\Enums\UserRole;
use App\Models\Customer;
use App\Models\CustomerTransaction;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\put;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::Admin,
        'status' => 'active',
    ]);
    $this->user = User::factory()->create([
        'role' => UserRole::User,
        'status' => 'active',
    ]);
});

describe('index', function () {
    it('displays users index page for admin', function () {
        actingAs($this->admin);

        $response = get('/users');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/Index')
            ->has('users')
        );
    });

    it('only shows non-admin users', function () {
        User::factory()->count(3)->create([
            'role' => UserRole::User,
            'status' => 'active',
        ]);

        User::factory()->count(2)->create([
            'role' => UserRole::Admin,
            'status' => 'active',
        ]);

        actingAs($this->admin);

        $response = get('/users');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('users.data', 4) // 3 new users + 1 from beforeEach
        );
    });

    it('orders users by latest', function () {
        // Delete the user from beforeEach to have clean test
        $this->user->delete();

        $user1 = User::factory()->create([
            'role' => UserRole::User,
            'created_at' => now()->subDays(2),
        ]);

        $user2 = User::factory()->create([
            'role' => UserRole::User,
            'created_at' => now()->subDay(),
        ]);

        $user3 = User::factory()->create([
            'role' => UserRole::User,
            'created_at' => now(),
        ]);

        actingAs($this->admin);

        $response = get('/users');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('users.data.0.id', $user3->id)
            ->where('users.data.1.id', $user2->id)
            ->where('users.data.2.id', $user1->id)
        );
    });

    it('paginates users with 15 per page', function () {
        User::factory()->count(20)->create([
            'role' => UserRole::User,
            'status' => 'active',
        ]);

        actingAs($this->admin);

        $response = get('/users');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->has('users.data', 15)
            ->has('users.links')
        );
    });

    it('forbids non-admin users from viewing users index', function () {
        actingAs($this->user);

        $response = get('/users');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = get('/users');

        $response->assertForbidden();
    });
});

describe('create', function () {
    it('displays create user page for admin', function () {
        actingAs($this->admin);

        $response = get('/users/create');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/Create')
        );
    });

    it('forbids non-admin users from viewing create page', function () {
        actingAs($this->user);

        $response = get('/users/create');

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = get('/users/create');

        $response->assertForbidden();
    });
});

describe('store', function () {
    it('creates a user successfully', function () {
        actingAs($this->admin);

        $userData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = post('/users', $userData);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'User created successfully.',
        ]);

        $this->assertDatabaseHas('users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'role' => UserRole::User->value,
            'status' => 'active',
        ]);
    });

    it('validates required name field', function () {
        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates name max length', function () {
        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'name' => str_repeat('a', 256),
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates required email field', function () {
        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates email format', function () {
        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'invalid-email',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates unique email', function () {
        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates email max length', function () {
        actingAs($this->admin);

        $longEmail = str_repeat('a', 246).'@example.com'; // 256 chars total

        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => $longEmail,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates required password field', function () {
        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('validates password minimum length', function () {
        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('validates password confirmation', function () {
        actingAs($this->admin);

        $response = $this->postJson('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('forbids non-admin users from creating users', function () {
        actingAs($this->user);

        $response = post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = post('/users', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertForbidden();
    });
});

describe('edit', function () {
    it('displays edit user page for admin', function () {
        actingAs($this->admin);

        $response = get("/users/{$this->user->id}/edit");

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/users/Edit')
            ->has('user')
            ->where('user.id', $this->user->id)
            ->where('user.name', $this->user->name)
            ->where('user.email', $this->user->email)
            ->where('user.role', $this->user->role->value)
            ->where('user.status', $this->user->status)
        );
    });

    it('forbids non-admin users from viewing edit page', function () {
        $otherUser = User::factory()->create(['role' => UserRole::User]);

        actingAs($this->user);

        $response = get("/users/{$otherUser->id}/edit");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = get("/users/{$this->user->id}/edit");

        $response->assertForbidden();
    });
});

describe('update', function () {
    it('updates user name successfully', function () {
        actingAs($this->admin);

        $response = put("/users/{$this->user->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'User updated successfully.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
        ]);
    });

    it('updates user email successfully', function () {
        actingAs($this->admin);

        $response = put("/users/{$this->user->id}", [
            'email' => 'newemail@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'User updated successfully.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'newemail@example.com',
        ]);
    });

    it('updates user password successfully', function () {
        actingAs($this->admin);

        $response = put("/users/{$this->user->id}", [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'User updated successfully.',
        ]);

        $this->user->refresh();
        expect(\Illuminate\Support\Facades\Hash::check('newpassword123', $this->user->password))->toBeTrue();
    });

    it('updates user status successfully', function () {
        actingAs($this->admin);

        $response = put("/users/{$this->user->id}", [
            'status' => 'inactive',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'User updated successfully.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'status' => 'inactive',
        ]);
    });

    it('updates multiple fields at once', function () {
        actingAs($this->admin);

        $response = put("/users/{$this->user->id}", [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'status' => 'inactive',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'New Name',
            'email' => 'newemail@example.com',
            'status' => 'inactive',
        ]);
    });

    it('validates name max length', function () {
        actingAs($this->admin);

        $response = $this->putJson("/users/{$this->user->id}", [
            'name' => str_repeat('a', 256),
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates email format', function () {
        actingAs($this->admin);

        $response = $this->putJson("/users/{$this->user->id}", [
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates unique email excluding current user', function () {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        actingAs($this->admin);

        $response = $this->putJson("/users/{$this->user->id}", [
            'email' => 'other@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('allows keeping the same email', function () {
        actingAs($this->admin);

        $response = put("/users/{$this->user->id}", [
            'email' => $this->user->email,
        ]);

        $response->assertOk();
    });

    it('validates password minimum length', function () {
        actingAs($this->admin);

        $response = $this->putJson("/users/{$this->user->id}", [
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('validates password confirmation', function () {
        actingAs($this->admin);

        $response = $this->putJson("/users/{$this->user->id}", [
            'password' => 'password123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('validates status values', function () {
        actingAs($this->admin);

        $response = $this->putJson("/users/{$this->user->id}", [
            'status' => 'invalid-status',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['status']);
    });

    it('forbids non-admin users from updating users', function () {
        $otherUser = User::factory()->create(['role' => UserRole::User]);

        actingAs($this->user);

        $response = put("/users/{$otherUser->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $response = put("/users/{$this->user->id}", [
            'name' => 'Updated Name',
        ]);

        $response->assertForbidden();
    });
});

describe('destroy', function () {
    it('deletes inactive user without associations successfully', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        actingAs($this->admin);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'User deleted successfully.',
        ]);

        $this->assertDatabaseMissing('users', ['id' => $inactiveUser->id]);
    });

    it('prevents deleting yourself', function () {
        $this->admin->update(['status' => 'inactive']);

        actingAs($this->admin);

        $response = delete("/users/{$this->admin->id}");

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'msg' => 'You cannot delete yourself.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $this->admin->id]);
    });

    it('prevents deleting active user', function () {
        actingAs($this->admin);

        $response = delete("/users/{$this->user->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete an active user. Please set the user to inactive first.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $this->user->id]);
    });

    it('prevents deleting user with customer transactions', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        CustomerTransaction::factory()->create(['user_id' => $inactiveUser->id]);

        actingAs($this->admin);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete user. This user has associated customer transactions.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $inactiveUser->id]);
    });

    it('prevents deleting user with sales', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        Sale::factory()->create(['user_id' => $inactiveUser->id]);

        actingAs($this->admin);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete user. This user has associated sales.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $inactiveUser->id]);
    });

    it('prevents deleting user with customers', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        Customer::factory()->create(['user_id' => $inactiveUser->id]);

        actingAs($this->admin);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete user. This user has associated customers.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $inactiveUser->id]);
    });

    it('prevents deleting user with products', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        Product::factory()->create(['user_id' => $inactiveUser->id]);

        actingAs($this->admin);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete user. This user has associated products.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $inactiveUser->id]);
    });

    it('prevents deleting user with expenses', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        Expense::factory()->create(['user_id' => $inactiveUser->id]);

        actingAs($this->admin);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
            'msg' => 'Cannot delete user. This user has associated expenses.',
        ]);

        $this->assertDatabaseHas('users', ['id' => $inactiveUser->id]);
    });

    it('prevents deleting user with multiple associations', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        Customer::factory()->create(['user_id' => $inactiveUser->id]);
        Sale::factory()->create(['user_id' => $inactiveUser->id]);
        Product::factory()->create(['user_id' => $inactiveUser->id]);

        actingAs($this->admin);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertStatus(409);
        $response->assertJson([
            'success' => false,
        ]);

        $this->assertDatabaseHas('users', ['id' => $inactiveUser->id]);
    });

    it('forbids non-admin users from deleting users', function () {
        $otherUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        actingAs($this->user);

        $response = delete("/users/{$otherUser->id}");

        $response->assertForbidden();
    });

    it('requires authentication', function () {
        $inactiveUser = User::factory()->create([
            'role' => UserRole::User,
            'status' => 'inactive',
        ]);

        $response = delete("/users/{$inactiveUser->id}");

        $response->assertForbidden();
    });
});
