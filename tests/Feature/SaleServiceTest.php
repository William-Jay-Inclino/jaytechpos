<?php

use App\Models\Sale;
use App\Models\User;
use App\Services\SaleService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->saleService = new SaleService;
});

it('generates unique invoice numbers per user', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    // Generate first invoice for user 1
    $invoice1 = $this->saleService->generateInvoiceNumber($user1->id);
    expect($invoice1)->toBe('INV-'.date('Y').'-0001');

    // Create a sale with this invoice number for user 1
    Sale::factory()->create([
        'user_id' => $user1->id,
        'invoice_number' => $invoice1,
    ]);

    // Generate first invoice for user 2 - should also start from 0001
    $invoice2 = $this->saleService->generateInvoiceNumber($user2->id);
    expect($invoice2)->toBe('INV-'.date('Y').'-0001');

    // Generate second invoice for user 1 - should be 0002
    $invoice3 = $this->saleService->generateInvoiceNumber($user1->id);
    expect($invoice3)->toBe('INV-'.date('Y').'-0002');
});

it('increments invoice numbers correctly for the same user', function () {
    $user = User::factory()->create();
    $currentYear = date('Y');

    // Create some existing sales for this user
    Sale::factory()->create([
        'user_id' => $user->id,
        'invoice_number' => "INV-{$currentYear}-0001",
    ]);

    Sale::factory()->create([
        'user_id' => $user->id,
        'invoice_number' => "INV-{$currentYear}-0003",
    ]);

    // Generate next invoice number - should be 0004 (max + 1)
    $nextInvoice = $this->saleService->generateInvoiceNumber($user->id);
    expect($nextInvoice)->toBe("INV-{$currentYear}-0004");
});

it('handles year rollover correctly', function () {
    $user = User::factory()->create();
    $lastYear = date('Y') - 1;
    $currentYear = date('Y');

    // Create sales from last year
    Sale::factory()->create([
        'user_id' => $user->id,
        'invoice_number' => "INV-{$lastYear}-0005",
    ]);

    // Generate invoice for current year - should start from 0001
    $currentYearInvoice = $this->saleService->generateInvoiceNumber($user->id);
    expect($currentYearInvoice)->toBe("INV-{$currentYear}-0001");
});
