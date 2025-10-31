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

describe('CustomerPolicy → viewAny', function () {
    it('allows authenticated users to view customers list', function () {
        $this->actingAs($this->user);

        expect($this->user->can('viewAny', Customer::class))->toBeTrue();
    });
});

describe('CustomerPolicy → view', function () {
    it('allows users to view their own customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('view', $this->customer))->toBeTrue();
    });

    it('denies users from viewing other users customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('view', $this->otherCustomer))->toBeFalse();
    });
});

describe('CustomerPolicy → create', function () {
    it('allows authenticated users to create customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('create', Customer::class))->toBeTrue();
    });
});

describe('CustomerPolicy → update', function () {
    it('allows users to update their own customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('update', $this->customer))->toBeTrue();
    });

    it('denies users from updating other users customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('update', $this->otherCustomer))->toBeFalse();
    });
});

describe('CustomerPolicy → delete', function () {
    it('allows users to delete their own customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('delete', $this->customer))->toBeTrue();
    });

    it('denies users from deleting other users customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('delete', $this->otherCustomer))->toBeFalse();
    });
});

describe('CustomerPolicy → restore', function () {
    it('allows users to restore their own customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('restore', $this->customer))->toBeTrue();
    });

    it('denies users from restoring other users customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('restore', $this->otherCustomer))->toBeFalse();
    });
});

describe('CustomerPolicy → forceDelete', function () {
    it('allows users to force delete their own customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('forceDelete', $this->customer))->toBeTrue();
    });

    it('denies users from force deleting other users customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('forceDelete', $this->otherCustomer))->toBeFalse();
    });
});
