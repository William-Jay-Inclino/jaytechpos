<?php

use App\Models\Customer;
use App\Models\Sale;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->otherUser = User::factory()->create();
    $this->sale = Sale::factory()->create(['user_id' => $this->user->id]);
    $this->otherSale = Sale::factory()->create(['user_id' => $this->otherUser->id]);
});

describe('SalePolicy → viewAny', function () {
    it('allows authenticated users to view sales list', function () {
        $this->actingAs($this->user);

        expect($this->user->can('viewAny', Sale::class))->toBeTrue();
    });
});

describe('SalePolicy → view', function () {
    it('allows users to view their own sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('view', $this->sale))->toBeTrue();
    });

    it('denies users from viewing other users sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('view', $this->otherSale))->toBeFalse();
    });
});

describe('SalePolicy → create', function () {
    it('allows authenticated users to create sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('create', Sale::class))->toBeTrue();
    });
});

describe('SalePolicy → createForCustomer', function () {
    it('allows users to create sales without a customer', function () {
        $this->actingAs($this->user);

        expect($this->user->can('createForCustomer', [Sale::class, null]))->toBeTrue();
    });

    it('allows users to create sales for their own customers', function () {
        $ownCustomer = Customer::factory()->create(['user_id' => $this->user->id]);
        $this->actingAs($this->user);

        expect($this->user->can('createForCustomer', [Sale::class, $ownCustomer->id]))->toBeTrue();
    });

    it('denies users from creating sales for other users customers', function () {
        $otherCustomer = Customer::factory()->create(['user_id' => $this->otherUser->id]);
        $this->actingAs($this->user);

        expect($this->user->can('createForCustomer', [Sale::class, $otherCustomer->id]))->toBeFalse();
    });

    it('denies creating sales for non-existent customers', function () {
        $this->actingAs($this->user);

        expect($this->user->can('createForCustomer', [Sale::class, 99999]))->toBeFalse();
    });
});

describe('SalePolicy → update', function () {
    it('allows users to update their own sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('update', $this->sale))->toBeTrue();
    });

    it('denies users from updating other users sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('update', $this->otherSale))->toBeFalse();
    });
});

describe('SalePolicy → delete', function () {
    it('allows users to delete their own sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('delete', $this->sale))->toBeTrue();
    });

    it('denies users from deleting other users sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('delete', $this->otherSale))->toBeFalse();
    });
});

describe('SalePolicy → restore', function () {
    it('allows users to restore their own sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('restore', $this->sale))->toBeTrue();
    });

    it('denies users from restoring other users sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('restore', $this->otherSale))->toBeFalse();
    });
});

describe('SalePolicy → forceDelete', function () {
    it('allows users to force delete their own sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('forceDelete', $this->sale))->toBeTrue();
    });

    it('denies users from force deleting other users sales', function () {
        $this->actingAs($this->user);

        expect($this->user->can('forceDelete', $this->otherSale))->toBeFalse();
    });
});
