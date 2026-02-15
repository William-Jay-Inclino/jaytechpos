<?php

namespace App\Http\Controllers\Inventory;

use App\Enums\StockMovementType;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustmentRequest;
use App\Http\Requests\StockInRequest;
use App\Http\Requests\StockOutRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\Inventory;
use App\Models\StockMovement;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class StockMovementController extends Controller
{
    /**
     * Handle stock in operation.
     */
    public function stockIn(StockInRequest $request): JsonResponse
    {
        try {
            $inventory = DB::transaction(function () use ($request) {
                StockMovement::create([
                    'product_id' => $request->product_id,
                    'type' => StockMovementType::IN,
                    'quantity' => $request->quantity,
                    'reference' => $request->reference,
                    'remarks' => $request->remarks,
                ]);

                $inventory = Inventory::firstOrCreate(
                    ['product_id' => $request->product_id],
                    ['quantity' => 0, 'low_stock_threshold' => 1]
                );

                $inventory->stockIn($request->quantity);

                return $inventory->refresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock added successfully.',
                'product' => [
                    'id' => $inventory->product_id,
                    'product_name' => $inventory->product->product_name,
                    'quantity' => number_format((float) $inventory->quantity, 2, '.', ''),
                    'low_stock_threshold' => number_format((float) $inventory->low_stock_threshold, 2, '.', ''),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to add stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle stock out operation.
     */
    public function stockOut(StockOutRequest $request): JsonResponse
    {
        try {
            $inventory = Inventory::where('product_id', $request->product_id)->first();

            if (! $inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product inventory not found.',
                ], 404);
            }

            if ($inventory->quantity < $request->quantity) {
                return response()->json([
                    'success' => false,
                    'message' => 'Insufficient stock. Available: '.$inventory->quantity,
                ], 422);
            }

            $inventory = DB::transaction(function () use ($request, $inventory) {
                StockMovement::create([
                    'product_id' => $request->product_id,
                    'type' => StockMovementType::OUT,
                    'quantity' => $request->quantity,
                    'reference' => $request->reference,
                    'remarks' => $request->remarks,
                ]);

                $inventory->stockOut($request->quantity);

                return $inventory->refresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock removed successfully.',
                'product' => [
                    'id' => $inventory->product_id,
                    'product_name' => $inventory->product->product_name,
                    'quantity' => number_format((float) $inventory->quantity, 2, '.', ''),
                    'low_stock_threshold' => number_format((float) $inventory->low_stock_threshold, 2, '.', ''),
                ],
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to remove stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Handle stock adjustment operation.
     */
    public function stockAdjustment(StockAdjustmentRequest $request): JsonResponse
    {
        try {
            $inventory = Inventory::where('product_id', $request->product_id)->first();

            if (! $inventory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Product inventory not found. Please create inventory record first.',
                ], 404);
            }

            $newQuantity = $request->quantity;

            $inventory = DB::transaction(function () use ($request, $inventory, $newQuantity) {
                StockMovement::create([
                    'product_id' => $request->product_id,
                    'type' => StockMovementType::ADJUSTMENT,
                    'quantity' => $newQuantity,
                    'reference' => $request->reference,
                    'remarks' => $request->remarks,
                ]);

                $inventory->update(['quantity' => $newQuantity]);

                return $inventory->refresh();
            });

            return response()->json([
                'success' => true,
                'message' => 'Stock adjusted successfully.',
                'product' => [
                    'id' => $inventory->product_id,
                    'product_name' => $inventory->product->product_name,
                    'quantity' => number_format((float) $inventory->quantity, 2, '.', ''),
                    'low_stock_threshold' => number_format((float) $inventory->low_stock_threshold, 2, '.', ''),
                ],
            ], 200);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to adjust stock.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get stock movements for a specific product.
     */
    public function getProductStockMovements(int $productId): JsonResponse
    {
        try {
            $movements = StockMovement::where('product_id', $productId)
                ->with('product')
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate(20);

            return response()->json([
                'success' => true,
                'data' => StockMovementResource::collection($movements->items()),
                'pagination' => [
                    'current_page' => $movements->currentPage(),
                    'last_page' => $movements->lastPage(),
                    'per_page' => $movements->perPage(),
                    'total' => $movements->total(),
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve stock movements.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
