<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\InventoryService;
use Illuminate\Http\Request;

class LowStockController extends Controller
{
    public function index(Request $request, InventoryService $inventoryService)
    {
        $companyId = session('current_company_id');

        $items = $inventoryService->getLowStockItems($companyId);

        return view('accounting.low-stock.index', compact('items'));
    }

    public function exportCsv(Request $request, InventoryService $inventoryService)
    {
        $companyId = session('current_company_id');

        $items = $inventoryService->getLowStockItems($companyId);

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="low-stock-' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($items) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['SKU', 'Product', 'Quantity on Hand', 'Reorder Point', 'Shortage']);

            foreach ($items as $item) {
                fputcsv($file, [
                    $item['sku'],
                    $item['product_name'],
                    $item['quantity_on_hand'],
                    $item['reorder_point'],
                    $item['shortage'],
                ]);
            }

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
