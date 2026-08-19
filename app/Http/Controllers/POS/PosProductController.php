<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\InventoryStock;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PosProductController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $q = $request->input('q');

        $query = Product::where('company_id', $companyId)
            ->with('category')
            ->orderBy('name');

        if ($q) {
            $query->where(function ($w) use ($q) {
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('sku', 'like', "%{$q}%")
                  ->orWhere('barcode', 'like', "%{$q}%");
            });
        }

        $products = $query->paginate(25)->withQueryString();

        $branchId = session('pos_terminal_branch_id');
        $stockByProduct = [];
        if ($branchId) {
            $stockRows = InventoryStock::where('company_id', $companyId)
                ->where('branch_id', $branchId)
                ->whereIn('product_id', $products->pluck('id')->filter()->values())
                ->select('product_id', DB::raw('SUM(quantity_on_hand) as qty'))
                ->groupBy('product_id')
                ->get();
            foreach ($stockRows as $row) {
                $stockByProduct[$row->product_id] = (float) $row->qty;
            }
        } else {
            $stockRows = InventoryStock::where('company_id', $companyId)
                ->whereIn('product_id', $products->pluck('id')->filter()->values())
                ->select('product_id', DB::raw('SUM(quantity_on_hand) as qty'))
                ->groupBy('product_id')
                ->get();
            foreach ($stockRows as $row) {
                $stockByProduct[$row->product_id] = (float) $row->qty;
            }
        }

        $products->each(function ($product) use ($stockByProduct) {
            $product->current_stock = $product->tracked_as_inventory
                ? ($stockByProduct[$product->id] ?? 0)
                : null;
        });

        $stats = [
            'total_products' => Product::where('company_id', $companyId)->count(),
            'active_products' => Product::where('company_id', $companyId)->where('is_active', true)->count(),
            'low_stock' => $products->where('current_stock', '!==', null)->filter(fn ($p) => $p->reorder_point > 0 && $p->current_stock <= $p->reorder_point)->count(),
        ];

        return view('pos.products.index', compact('products', 'stats', 'q'));
    }
}
