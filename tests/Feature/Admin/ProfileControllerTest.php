<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->admin = User::factory()->create([
        'role' => UserRole::Admin,
        'status' => 'active',
        'password' => Hash::make('password123'),
    ]);
    $this->user = User::factory()->create([
        'role' => UserRole::User,
        'status' => 'active',
        'password' => Hash::make('password123'),
    ]);
});

describe('show', function () {
    it('displays profile page for admin', function () {
        actingAs($this->admin);

        $response = get('/admin/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Profile')
            ->has('user')
            ->where('user.id', $this->admin->id)
            ->where('user.name', $this->admin->name)
            ->where('user.email', $this->admin->email)
            ->where('user.role', $this->admin->role->value)
        );
    });

    it('displays profile page for regular user', function () {
        actingAs($this->user);

        $response = get('/admin/profile');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('admin/Profile')
            ->has('user')
            ->where('user.id', $this->user->id)
            ->where('user.name', $this->user->name)
            ->where('user.email', $this->user->email)
            ->where('user.role', $this->user->role->value)
        );
    });
});

describe('update', function () {
    it('updates profile name successfully', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => 'Updated Name',
            'email' => $this->user->email,
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'Profile updated successfully.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => $this->user->email,
        ]);
    });

    it('updates profile email successfully', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => $this->user->name,
            'email' => 'newemail@example.com',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'Profile updated successfully.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'email' => 'newemail@example.com',
        ]);
    });

    it('updates both name and email successfully', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'New Name',
            'email' => 'newemail@example.com',
        ]);
    });

    it('validates required name field', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'email' => $this->user->email,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates name max length', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => str_repeat('a', 256),
            'email' => $this->user->email,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['name']);
    });

    it('validates required email field', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => $this->user->name,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates email format', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => $this->user->name,
            'email' => 'invalid-email',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates email max length', function () {
        actingAs($this->user);

        $longEmail = str_repeat('a', 246).'@example.com'; // 256 chars total

        $response = $this->patchJson('/admin/profile', [
            'name' => $this->user->name,
            'email' => $longEmail,
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('validates unique email', function () {
        $otherUser = User::factory()->create(['email' => 'other@example.com']);

        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => $this->user->name,
            'email' => 'other@example.com',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['email']);
    });

    it('allows keeping the same email', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile', [
            'name' => 'Updated Name',
            'email' => $this->user->email,
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Updated Name',
            'email' => $this->user->email,
        ]);
    });
});

describe('updatePassword', function () {
    it('updates password successfully', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'msg' => 'Password updated successfully.',
        ]);

        $this->user->refresh();
        expect(Hash::check('newpassword123', $this->user->password))->toBeTrue();
    });

    it('validates required current_password field', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile/password', [
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);
    });

    it('validates current_password is correct', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['current_password']);
    });

    it('validates required password field', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile/password', [
            'current_password' => 'password123',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('validates password minimum length', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile/password', [
            'current_password' => 'password123',
            'password' => 'short',
            'password_confirmation' => 'short',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('validates password confirmation', function () {
        actingAs($this->user);

        $response = $this->patchJson('/admin/profile/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'different',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['password']);
    });

    it('does not update password if current password is wrong', function () {
        $originalPassword = $this->user->password;

        actingAs($this->user);

        $response = $this->patchJson('/admin/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertStatus(422);

        $this->user->refresh();
        expect($this->user->password)->toBe($originalPassword);
    });
});
