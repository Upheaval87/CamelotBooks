<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\InventoryAdjustment;
use App\Models\InventoryStock;
use App\Models\ItemCategory;
use App\Models\Product;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockAdjustmentController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $adjustments = InventoryAdjustment::where('company_id', $companyId)
            ->with(['product', 'branch', 'creator'])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20);

        return view('accounting.stock-adjustments.index', compact('adjustments'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $products = Product::where('company_id', $companyId)
            ->inventoryTracked()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $branches = auth()->user()->branches()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $itemCategories = ItemCategory::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.stock-adjustments.create', compact('products', 'branches', 'itemCategories'));
    }

    public function store(Request $request, InventoryService $inventoryService, JournalPostingEngine $postingEngine)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'nullable|exists:branches,id',
            'date' => 'required|date',
            'type' => 'required|in:increase,decrease',
            'quantity' => 'required|numeric|min:0.0001',
            'reason_code' => 'required|in:found_in_count,damage,shrinkage,correction,other',
            'memo' => 'nullable|string|max:500',
            'unit_cost' => 'nullable|numeric|min:0',
        ]);

        $product = Product::where('id', $validated['product_id'])
            ->where('company_id', $companyId)
            ->firstOrFail();

        if (!$product->tracked_as_inventory && $product->type !== 'inventory') {
            return back()->withErrors(['product_id' => 'Product is not tracked as inventory.']);
        }

        $validated['company_id'] = $companyId;

        DB::beginTransaction();

        try {
            $result = $inventoryService->adjustStock(
                $companyId,
                $validated['product_id'],
                $validated['branch_id'] ?? null,
                $validated['type'],
                $validated['quantity'],
                $validated['reason_code'],
                $validated['memo'] ?? null,
                $validated['unit_cost'] ?? null,
                $userId,
                $validated['date']
            );

            $adjustment = InventoryAdjustment::create([
                'company_id' => $companyId,
                'product_id' => $validated['product_id'],
                'branch_id' => $validated['branch_id'] ?? null,
                'adjustment_number' => $result['adjustment_number'],
                'date' => $validated['date'],
                'type' => $validated['type'],
                'quantity' => $validated['quantity'],
                'reason_code' => $validated['reason_code'],
                'memo' => $validated['memo'] ?? null,
                'unit_cost' => $result['unit_cost'],
                'total_cost' => $result['total_cost'],
                'created_by' => $userId,
                'status' => 'posted',
            ]);

            if ($validated['type'] === 'increase') {
                $inventoryService->receiveStock(
                    $companyId,
                    $validated['product_id'],
                    $validated['branch_id'] ?? null,
                    $validated['quantity'],
                    $result['unit_cost'],
                    'adjustment',
                    $adjustment->id,
                    $validated['date']
                );
            }

            $invAdjAccount = Account::where('company_id', $companyId)->where('code', '6700')->first();
            $invAssetAccount = $product->inventoryAssetAccount;

            if ($invAdjAccount && $invAssetAccount) {
                $totalCost = $result['total_cost'];
                $branchId = $validated['branch_id'] ?? null;

                if ($validated['type'] === 'increase') {
                    $jeLines = [
                        [
                            'account_id' => $invAssetAccount->id,
                            'debit' => $totalCost,
                            'credit' => 0,
                            'memo' => "Stock adjustment {$result['adjustment_number']} - {$product->name}",
                            'branch_id' => $branchId,
                        ],
                        [
                            'account_id' => $invAdjAccount->id,
                            'debit' => 0,
                            'credit' => $totalCost,
                            'memo' => "Stock adjustment {$result['adjustment_number']} - {$product->name}",
                            'branch_id' => $branchId,
                        ],
                    ];
                } else {
                    $jeLines = [
                        [
                            'account_id' => $invAdjAccount->id,
                            'debit' => $totalCost,
                            'credit' => 0,
                            'memo' => "Stock adjustment {$result['adjustment_number']} - {$product->name}",
                            'branch_id' => $branchId,
                        ],
                        [
                            'account_id' => $invAssetAccount->id,
                            'debit' => 0,
                            'credit' => $totalCost,
                            'memo' => "Stock adjustment {$result['adjustment_number']} - {$product->name}",
                            'branch_id' => $branchId,
                        ],
                    ];
                }

                $journalEntry = $postingEngine->post([
                    'company_id' => $companyId,
                    'created_by' => $userId,
                    'date' => $validated['date'],
                    'source_module' => 'inventory_adjustment',
                    'reference' => $result['adjustment_number'],
                    'memo' => "Stock adjustment {$result['adjustment_number']}",
                    'branch_id' => $branchId,
                    'lines' => $jeLines,
                ]);

                $adjustment->update(['journal_entry_id' => $journalEntry->id]);
            }

            DB::commit();

            return redirect()->route('accounting.stock-adjustments.show', $adjustment)
                ->with('success', "Stock adjustment {$result['adjustment_number']} posted successfully.");
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(InventoryAdjustment $adjustment)
    {
        $companyId = session('current_company_id');

        if ($adjustment->company_id !== $companyId) {
            abort(404);
        }

        $adjustment->load(['product', 'branch', 'creator', 'journalEntry']);

        return view('accounting.stock-adjustments.show', compact('adjustment'));
    }
}
