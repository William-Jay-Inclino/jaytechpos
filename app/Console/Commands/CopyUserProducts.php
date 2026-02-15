<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use Illuminate\Console\Command;

class CopyUserProducts extends Command
{
    protected $signature = 'products:copy {from : The ID of the source user} {to : The ID of the target user}';

    protected $description = 'Copy all products from one user to another user';

    public function handle(): int
    {
        $fromUser = User::find($this->argument('from'));
        $toUser = User::find($this->argument('to'));

        if (! $fromUser) {
            $this->error("Source user with ID {$this->argument('from')} not found.");

            return self::FAILURE;
        }

        if (! $toUser) {
            $this->error("Target user with ID {$this->argument('to')} not found.");

            return self::FAILURE;
        }

        if ($fromUser->id === $toUser->id) {
            $this->error('Source and target user cannot be the same.');

            return self::FAILURE;
        }

        $products = Product::where('user_id', $fromUser->id)->get();

        if ($products->isEmpty()) {
            $this->warn("No products found for source user {$fromUser->name} (ID: {$fromUser->id}).");

            return self::SUCCESS;
        }

        $existingProductNames = Product::where('user_id', $toUser->id)
            ->pluck('product_name')
            ->map(fn (string $name) => strtolower($name))
            ->all();

        $copied = 0;
        $skipped = 0;

        foreach ($products as $product) {
            if (in_array(strtolower($product->product_name), $existingProductNames, true)) {
                $this->warn("Skipped: {$product->product_name} (already exists for target user).");
                $skipped++;

                continue;
            }

            Product::create([
                'user_id' => $toUser->id,
                'unit_id' => $product->unit_id,
                'product_name' => $product->product_name,
                'description' => $product->description,
                'unit_price' => $product->unit_price,
                'cost_price' => $product->cost_price,
                'status' => $product->status,
            ]);

            $copied++;
        }

        $this->info("Copied {$copied} product(s) from {$fromUser->name} to {$toUser->name}.");

        if ($skipped > 0) {
            $this->info("Skipped {$skipped} product(s) that already existed.");
        }

        return self::SUCCESS;
    }
}
