<?php

use App\Models\ExpenseCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates default expense categories on registration', function () {
    $response = $this->post('/register', [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertRedirect(route('dashboard'));

    $user = User::where('email', 'test@example.com')->first();
    expect($user)->not->toBeNull();

    $categories = ExpenseCategory::where('user_id', $user->id)->pluck('name')->toArray();

    // Expect the same number of default categories as in the seeder
    expect(count($categories))->toBe(10);
    expect($categories)->toContain('Rent');
});
