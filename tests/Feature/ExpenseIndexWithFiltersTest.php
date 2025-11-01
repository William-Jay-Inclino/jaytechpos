<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('can view expenses index with default month and year', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/expenses');

    $response->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('expenses/Index')
            ->has('expenses')
            ->has('categories')
            ->has('chartData')
            ->has('selectedMonth')
            ->has('selectedYear')
            ->where('selectedMonth', (int) now()->format('n'))
            ->where('selectedYear', (int) now()->format('Y'))
        );
});
