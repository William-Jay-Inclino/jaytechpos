<?php

use App\Models\Customer;
use App\Models\User;
use App\Models\UtangPayment;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->utangPayment = UtangPayment::factory()->create(['user_id' => $this->user->id]);
    $this->otherUtangPayment = UtangPayment::factory()->create(['user_id' => $this->otherUser->id]);
});

describe('UtangPaymentPolicy → viewAny', function () {
    it('allows authenticated users to view utang payments list', function () {
        $this->actingAs($this->user);

        expect($this->user->can('viewAny', UtangPayment::class))->toBeTrue();
    });
});

describe('UtangPaymentPolicy → view', function () {
    it('allows users to view their own utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('view', $this->utangPayment))->toBeTrue();
    });

    it('denies users from viewing other users utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('view', $this->otherUtangPayment))->toBeFalse();
    });
});

describe('UtangPaymentPolicy → create', function () {
    it('allows authenticated users to create utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('create', UtangPayment::class))->toBeTrue();
    });
});

describe('UtangPaymentPolicy → createForCustomer', function () {
    it('allows users to create payments for their own customers', function () {
        $ownCustomer = Customer::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        expect($this->user->can('createForCustomer', [UtangPayment::class, $ownCustomer->id]))->toBeTrue();
    });

    it('denies users from creating payments for other users customers', function () {
        $otherCustomer = Customer::factory()->create(['user_id' => $this->otherUser->id]);
        $this->actingAs($this->user);

        expect($this->user->can('createForCustomer', [UtangPayment::class, $otherCustomer->id]))->toBeFalse();
    });

    it('denies creating payments for non-existent customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('createForCustomer', [UtangPayment::class, 99999]))->toBeFalse();
    });
});

describe('UtangPaymentPolicy → update', function () {
    it('allows users to update their own utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('update', $this->utangPayment))->toBeTrue();
    });

    it('denies users from updating other users utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('update', $this->otherUtangPayment))->toBeFalse();
    });
});

describe('UtangPaymentPolicy → delete', function () {
    it('allows users to delete their own utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('delete', $this->utangPayment))->toBeTrue();
    });

    it('denies users from deleting other users utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('delete', $this->otherUtangPayment))->toBeFalse();
    });
});

describe('UtangPaymentPolicy → restore', function () {
    it('allows users to restore their own utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('restore', $this->utangPayment))->toBeTrue();
    });

    it('denies users from restoring other users utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('restore', $this->otherUtangPayment))->toBeFalse();
    });
});

describe('UtangPaymentPolicy → forceDelete', function () {
    it('allows users to force delete their own utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('forceDelete', $this->utangPayment))->toBeTrue();
    });

    it('denies users from force deleting other users utang payments', function () {
        $this->actingAs($this->user);

        expect($this->user->can('forceDelete', $this->otherUtangPayment))->toBeFalse();
    });
});
