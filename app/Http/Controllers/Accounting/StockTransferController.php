<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\Branch;
use App\Services\Accounting\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockTransferController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $transfers = InventoryTransfer::where('company_id', $companyId)
            ->with(['product', 'fromBranch', 'toBranch', 'creator'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('accounting.stock-transfers.index', compact('transfers'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->where('tracked_as_inventory', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.stock-transfers.create', compact('products', 'branches'));
    }

    public function store(Request $request, InventoryService $inventoryService)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'from_branch_id' => 'required|exists:branches,id',
            'to_branch_id' => 'required|exists:branches,id|different:from_branch_id',
            'date' => 'required|date',
            'quantity' => 'required|numeric|min:0.0001',
            'memo' => 'nullable|string|max:500',
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$product->tracked_as_inventory) {
            return back()->withErrors(['product_id' => 'Product is not tracked as inventory.']);
        }

        try {
            $transfer = $inventoryService->transferStock(
                $companyId,
                $validated['product_id'],
                $validated['from_branch_id'],
                $validated['to_branch_id'],
                $validated['quantity'],
                $validated['memo'] ?? null,
                $userId,
                $validated['date']
            );

            return redirect()->route('accounting.stock-transfers.show', $transfer)
                ->with('success', "Transfer {$transfer->transfer_number} completed successfully.");
        } catch (\Exception $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(InventoryTransfer $transfer)
    {
        $companyId = session('current_company_id');

        if ($transfer->company_id !== $companyId) {
            abort(404);
        }

        $transfer->load(['product', 'fromBranch', 'toBranch', 'creator']);

        return view('accounting.stock-transfers.show', compact('transfer'));
    }
}
