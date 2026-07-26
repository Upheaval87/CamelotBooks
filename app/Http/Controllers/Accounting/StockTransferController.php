<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InventoryTransfer;
use App\Models\Product;
use App\Models\Branch;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
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
            ->inventoryTracked()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.stock-transfers.create', compact('products', 'branches'));
    }

    public function store(Request $request, InventoryService $inventoryService, JournalPostingEngine $postingEngine)
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

        if (!$product->tracked_as_inventory && $product->type !== 'inventory') {
            return back()->withErrors(['product_id' => 'Product is not tracked as inventory.']);
        }

        DB::beginTransaction();

        try {
            $result = $inventoryService->transferStock(
                $companyId,
                $validated['product_id'],
                $validated['from_branch_id'],
                $validated['to_branch_id'],
                $validated['quantity'],
                $validated['memo'] ?? null,
                $userId,
                $validated['date']
            );

            $transfer = $result['transfer'];
            $totalCost = $result['total_cost'];

            $invAssetAccount = Account::where('company_id', $companyId)
                ->where('code', '1200')
                ->first();

            if ($invAssetAccount && $totalCost > 0) {
                $journalEntry = $postingEngine->post([
                    'company_id' => $companyId,
                    'created_by' => $userId,
                    'date' => $validated['date'],
                    'source_module' => 'inventory_transfer',
                    'reference' => $transfer->transfer_number,
                    'memo' => "Inventory transfer {$transfer->transfer_number}",
                    'lines' => [
                        [
                            'account_id' => $invAssetAccount->id,
                            'branch_id' => $validated['to_branch_id'],
                            'debit' => $totalCost,
                            'credit' => 0,
                            'memo' => "Transfer in - {$product->name}",
                        ],
                        [
                            'account_id' => $invAssetAccount->id,
                            'branch_id' => $validated['from_branch_id'],
                            'debit' => 0,
                            'credit' => $totalCost,
                            'memo' => "Transfer out - {$product->name}",
                        ],
                    ],
                ]);

                $transfer->update(['journal_entry_id' => $journalEntry->id]);
            }

            DB::commit();

            return redirect()->route('accounting.stock-transfers.show', $transfer)
                ->with('success', "Transfer {$transfer->transfer_number} completed successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(InventoryTransfer $transfer)
    {
        $companyId = session('current_company_id');

        if ($transfer->company_id !== $companyId) {
            abort(404);
        }

        $transfer->load(['product', 'fromBranch', 'toBranch', 'creator', 'journalEntry']);

        return view('accounting.stock-transfers.show', compact('transfer'));
    }
}
