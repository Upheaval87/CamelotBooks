<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\InventoryCostLayer;
use App\Models\Product;
use App\Services\Accounting\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryValuationController extends Controller
{
    public function index(Request $request, InventoryService $inventoryService)
    {
        $companyId = session('current_company_id');

        $valuation = $inventoryService->getValuationByProduct($companyId);

        $totalValue = array_sum(array_column($valuation, 'total_value'));

        return view('accounting.inventory-valuation.index', compact('valuation', 'totalValue'));
    }

    public function exportCsv(Request $request, InventoryService $inventoryService)
    {
        $companyId = session('current_company_id');

        $valuation = $inventoryService->getValuationByProduct($companyId);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="inventory-valuation-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($valuation) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['SKU', 'Product', 'Quantity', 'Avg Unit Cost', 'Total Value']);

            foreach ($valuation as $row) {
                fputcsv($file, [
                    $row['sku'],
                    $row['product_name'],
                    $row['total_quantity'],
                    number_format((float) $row['avg_cost'], 4),
                    number_format((float) $row['total_value'], 2),
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request, InventoryService $inventoryService)
    {
        $companyId = session('current_company_id');

        $valuation = $inventoryService->getValuationByProduct($companyId);
        $totalValue = array_sum(array_column($valuation, 'total_value'));

        return response()->view('accounting.print-export', [
            'title' => 'Inventory Valuation Report',
            'subtitle' => 'As of ' . now()->format('M d, Y'),
            'headers' => ['SKU', 'Product', 'Quantity', 'Avg Cost', 'Total Value'],
            'rows' => array_map(function ($row) {
                return [
                    $row['sku'],
                    $row['product_name'],
                    $row['total_quantity'],
                    '$' . number_format((float) $row['avg_cost'], 4),
                    '$' . number_format((float) $row['total_value'], 2),
                ];
            }, $valuation),
            'totals' => ['Total Inventory Value', '', '', '', '$' . number_format($totalValue, 2)],
        ], 200)->header('Content-Type', 'text/html');
    }

    public function byCategory(Request $request)
    {
        $companyId = session('current_company_id');

        $categories = \App\Models\ItemCategory::where('company_id', $companyId)
            ->with(['products' => function ($q) use ($companyId) {
                $q->where('tracked_as_inventory', true);
            }])
            ->orderBy('name')
            ->get();

        $categoryData = [];
        $grandTotal = 0;

        foreach ($categories as $category) {
            $totalQty = 0;
            $totalValue = 0;
            $products = [];

            foreach ($category->products as $product) {
                $layers = InventoryCostLayer::where('company_id', $companyId)
                    ->where('product_id', $product->id)
                    ->available()
                    ->get();

                $qty = (float) $layers->sum('quantity_remaining');
                $value = (float) $layers->sum(fn($l) => $l->quantity_remaining * $l->unit_cost);

                if ($qty > 0) {
                    $products[] = [
                        'product_id' => $product->id,
                        'sku' => $product->sku,
                        'name' => $product->name,
                        'quantity' => $qty,
                        'value' => $value,
                    ];
                    $totalQty += $qty;
                    $totalValue += $value;
                }
            }

            $categoryData[] = [
                'category_id' => $category->id,
                'code' => $category->code,
                'name' => $category->name,
                'products' => $products,
                'total_quantity' => $totalQty,
                'total_value' => $totalValue,
            ];

            $grandTotal += $totalValue;
        }

        $uncategorizedProducts = Product::where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->whereNull('category_id')
            ->get();

        $uncategorizedData = [];
        foreach ($uncategorizedProducts as $product) {
            $layers = InventoryCostLayer::where('company_id', $companyId)
                ->where('product_id', $product->id)
                ->available()
                ->get();

            $qty = (float) $layers->sum('quantity_remaining');
            $value = (float) $layers->sum(fn($l) => $l->quantity_remaining * $l->unit_cost);

            if ($qty > 0) {
                $uncategorizedData[] = [
                    'product_id' => $product->id,
                    'sku' => $product->sku,
                    'name' => $product->name,
                    'quantity' => $qty,
                    'value' => $value,
                ];
                $grandTotal += $value;
            }
        }

        return view('accounting.inventory-valuation.by-category', compact('categoryData', 'uncategorizedData', 'grandTotal'));
    }
}
