<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\InventoryCostLayer;
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
}
