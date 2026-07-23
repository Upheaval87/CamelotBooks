<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Product;
use Illuminate\Http\Request;

class InventoryItemsController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->with(['stock' => function ($q) {
                $q->with('branch');
            }])
            ->orderBy('name')
            ->paginate(20);

        return view('accounting.inventory-items.index', compact('products'));
    }

    public function show(Product $product)
    {
        $companyId = session('current_company_id');

        if ($product->company_id !== $companyId) {
            abort(404);
        }

        $product->load(['stock.branch', 'costLayers' => function ($q) {
            $q->available()->orderBy('date', 'asc');
        }, 'incomeAccount', 'expenseAccount', 'inventoryAssetAccount']);

        $totalOnHand = $product->stock->sum('quantity_on_hand');
        $totalValue = $product->costLayers->where('quantity_remaining', '>', 0)
            ->sum(fn($layer) => $layer->quantity_remaining * $layer->unit_cost);

        return view('accounting.inventory-items.show', compact('product', 'totalOnHand', 'totalValue'));
    }
}
