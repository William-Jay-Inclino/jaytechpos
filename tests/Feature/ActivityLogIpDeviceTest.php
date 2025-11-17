<?php

use App\Models\Activity;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

it('captures ip address when logging activity', function () {
    // Create product via HTTP request to simulate real IP capture
    $this->postJson('/api/test', [], ['REMOTE_ADDR' => '192.168.1.100']);

    $product = Product::factory()->create();

    $activity = Activity::latest()->first();

    expect($activity->properties->get('ip_address'))->not->toBeEmpty();
});

it('captures user agent when logging activity', function () {
    $product = Product::factory()->create();

    $activity = Activity::latest()->first();

    expect($activity->properties->get('user_agent'))->not->toBeEmpty();
});

it('device info is included in activity properties', function () {
    $product = Product::factory()->create();

    $activity = Activity::latest()->first();

    // Verify that IP address and user agent are captured
    expect($activity->properties)->toHaveKey('ip_address');
    expect($activity->properties)->toHaveKey('user_agent');
});
