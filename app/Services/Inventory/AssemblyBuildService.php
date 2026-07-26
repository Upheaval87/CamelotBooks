<?php

namespace App\Services\Inventory;

use App\Models\Account;
use App\Models\AssemblyBuild;
use App\Models\BillOfMaterial;
use App\Models\InventoryCostLayer;
use App\Models\Product;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AssemblyBuildService
{
    protected JournalPostingEngine $postingEngine;
    protected InventoryService $inventoryService;

    public function __construct(JournalPostingEngine $postingEngine, InventoryService $inventoryService)
    {
        $this->postingEngine = $postingEngine;
        $this->inventoryService = $inventoryService;
    }

    public function build(array $data, int $userId): AssemblyBuild
    {
        $companyId = $data['company_id'];
        $productId = $data['assembly_product_id'];
        $quantity = $data['quantity'];
        $date = $data['date'];

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Build quantity must be positive.');
        }

        $product = Product::findOrFail($productId);
        if (!$product->is_assembly) {
            throw new InvalidArgumentException('Product is not marked as an assembly item.');
        }

        $bom = $data['bom_id']
            ? BillOfMaterial::where('company_id', $companyId)
                ->where('id', $data['bom_id'])
                ->where('assembly_product_id', $productId)
                ->active()
                ->firstOrFail()
            : BillOfMaterial::where('company_id', $companyId)
                ->where('assembly_product_id', $productId)
                ->active()
                ->first();

        if (!$bom) {
            throw new InvalidArgumentException('No active bill of materials found for this product.');
        }

        $bom->load('lines.componentProduct');

        $product = Product::findOrFail($productId);

        return DB::transaction(function () use ($companyId, $productId, $bom, $quantity, $date, $userId, $data, $product) {
            $totalComponentCost = 0;
            $componentDetails = [];

            foreach ($bom->lines as $line) {
                $requiredQty = round($line->quantity * $quantity, 4);

                $company = \App\Models\Company::findOrFail($companyId);
                if (!$company->allow_negative_stock) {
                    $onHand = $this->inventoryService->getQuantityOnHand($companyId, $line->component_product_id, $data['branch_id'] ?? null);
                    if ($onHand < $requiredQty) {
                        throw new InvalidArgumentException(
                            "Insufficient stock for component '{$line->componentProduct->name}'. " .
                            "On hand: {$onHand}, required: {$requiredQty}."
                        );
                    }
                }

                $consumedLayers = $this->inventoryService->consumeStock(
                    $companyId,
                    $line->component_product_id,
                    $data['branch_id'] ?? null,
                    $requiredQty,
                    $date
                );

                $lineCost = array_sum(array_column($consumedLayers, 'total_cost'));
                $totalComponentCost += $lineCost;

                $componentDetails[] = [
                    'product_id' => $line->component_product_id,
                    'product_name' => $line->componentProduct->name,
                    'quantity_consumed' => $requiredQty,
                    'unit_cost' => $requiredQty > 0 ? round($lineCost / $requiredQty, 4) : 0,
                    'total_cost' => $lineCost,
                ];
            }

            $unitCost = $quantity > 0 ? round($totalComponentCost / $quantity, 4) : 0;

            $this->inventoryService->receiveStock(
                $companyId,
                $productId,
                $data['branch_id'] ?? null,
                $quantity,
                $unitCost,
                'assembly_build',
                0,
                $date
            );

            $buildNumber = $this->generateBuildNumber($companyId);

            $assemblyAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();
            $componentsAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();

            $journalLines = [];
            if ($assemblyAccount && $componentsAccount && $totalComponentCost > 0) {
                $journalLines = [
                    [
                        'account_id' => $assemblyAccount->id,
                        'debit' => $totalComponentCost,
                        'credit' => 0,
                        'memo' => "Assembly build {$buildNumber} - {$product->name}",
                    ],
                ];

                $componentAccountTotals = [];
                foreach ($componentDetails as $detail) {
                    $compProduct = Product::find($detail['product_id']);
                    $compAccountId = $compProduct?->effective_inventory_asset_account_id ?? $componentsAccount->id;
                    $componentAccountTotals[$compAccountId] = ($componentAccountTotals[$compAccountId] ?? 0) + $detail['total_cost'];
                }

                foreach ($componentAccountTotals as $accountId => $amount) {
                    $journalLines[] = [
                        'account_id' => $accountId,
                        'debit' => 0,
                        'credit' => round($amount, 2),
                        'memo' => "Assembly build {$buildNumber} - component consumption",
                    ];
                }
            }

            $journalEntry = null;
            if (!empty($journalLines)) {
                $journalEntry = $this->postingEngine->post([
                    'company_id' => $companyId,
                    'created_by' => $userId,
                    'date' => $date,
                    'source_module' => 'assembly_build',
                    'reference' => $buildNumber,
                    'memo' => "Assembly build {$buildNumber} - {$product->name}",
                    'lines' => $journalLines,
                ]);
            }

            $build = AssemblyBuild::create([
                'company_id' => $companyId,
                'assembly_product_id' => $productId,
                'bom_id' => $bom->id,
                'build_number' => $buildNumber,
                'type' => 'build',
                'quantity' => $quantity,
                'total_component_cost' => $totalComponentCost,
                'unit_cost' => $unitCost,
                'status' => 'completed',
                'memo' => $data['memo'] ?? null,
                'journal_entry_id' => $journalEntry?->id,
                'created_by' => $userId,
                'date' => $date,
            ]);

            return $build;
        });
    }

    public function unbuild(array $data, int $userId): AssemblyBuild
    {
        $companyId = $data['company_id'];
        $productId = $data['assembly_product_id'];
        $quantity = $data['quantity'];
        $date = $data['date'];

        if ($quantity <= 0) {
            throw new InvalidArgumentException('Unbuild quantity must be positive.');
        }

        $product = Product::findOrFail($productId);
        if (!$product->is_assembly) {
            throw new InvalidArgumentException('Product is not marked as an assembly item.');
        }

        $onHand = $this->inventoryService->getQuantityOnHand($companyId, $productId, $data['branch_id'] ?? null);
        if ($onHand < $quantity) {
            throw new InvalidArgumentException(
                "Insufficient assembly stock to unbuild. On hand: {$onHand}, requested: {$quantity}."
            );
        }

        $bom = $data['bom_id']
            ? BillOfMaterial::where('company_id', $companyId)
                ->where('id', $data['bom_id'])
                ->where('assembly_product_id', $productId)
                ->active()
                ->first()
            : BillOfMaterial::where('company_id', $companyId)
                ->where('assembly_product_id', $productId)
                ->active()
                ->first();

        return DB::transaction(function () use ($companyId, $productId, $bom, $quantity, $date, $userId, $data, $product) {
            $consumedLayers = $this->inventoryService->consumeStock(
                $companyId,
                $productId,
                $data['branch_id'] ?? null,
                $quantity,
                $date
            );

            $assemblyCost = array_sum(array_column($consumedLayers, 'total_cost'));
            $unitCost = $quantity > 0 ? round($assemblyCost / $quantity, 4) : 0;

            $totalComponentCost = 0;
            $componentDetails = [];

            if ($bom) {
                $bom->load('lines.componentProduct');
                $totalBomQty = $bom->lines->sum('quantity');

                foreach ($bom->lines as $line) {
                    $returnedQty = round($line->quantity * $quantity, 4);
                    $lineCost = $totalBomQty > 0
                        ? round($assemblyCost * ($line->quantity / $totalBomQty), 2)
                        : 0;
                    $compUnitCost = $returnedQty > 0 ? round($lineCost / $returnedQty, 4) : 0;

                    $this->inventoryService->receiveStock(
                        $companyId,
                        $line->component_product_id,
                        $data['branch_id'] ?? null,
                        $returnedQty,
                        $compUnitCost,
                        'assembly_unbuild',
                        0,
                        $date
                    );

                    $lineCost = round($returnedQty * $compUnitCost, 2);
                    $totalComponentCost += $lineCost;

                    $componentDetails[] = [
                        'product_id' => $line->component_product_id,
                        'product_name' => $line->componentProduct->name,
                        'quantity_returned' => $returnedQty,
                        'unit_cost' => $compUnitCost,
                        'total_cost' => $lineCost,
                    ];
                }
            }

            $buildNumber = $this->generateBuildNumber($companyId);

            $assemblyAccount = Account::where('company_id', $companyId)->where('code', '1200')->first();
            $journalLines = [];
            if ($assemblyAccount && $totalComponentCost > 0) {
                $journalLines[] = [
                    'account_id' => $assemblyAccount->id,
                    'debit' => 0,
                    'credit' => $assemblyCost,
                    'memo' => "Assembly unbuild {$buildNumber} - {$product->name}",
                ];

                foreach ($componentDetails as $detail) {
                    $compProduct = Product::find($detail['product_id']);
                    $compAccountId = $compProduct?->effective_inventory_asset_account_id ?? $assemblyAccount->id;
                    $journalLines[] = [
                        'account_id' => $compAccountId,
                        'debit' => $detail['total_cost'],
                        'credit' => 0,
                        'memo' => "Assembly unbuild {$buildNumber} - component return",
                    ];
                }
            }

            $journalEntry = null;
            if (!empty($journalLines)) {
                $journalEntry = $this->postingEngine->post([
                    'company_id' => $companyId,
                    'created_by' => $userId,
                    'date' => $date,
                    'source_module' => 'assembly_unbuild',
                    'reference' => $buildNumber,
                    'memo' => "Assembly unbuild {$buildNumber} - {$product->name}",
                    'lines' => $journalLines,
                ]);
            }

            $build = AssemblyBuild::create([
                'company_id' => $companyId,
                'assembly_product_id' => $productId,
                'bom_id' => $bom?->id,
                'build_number' => $buildNumber,
                'type' => 'unbuild',
                'quantity' => $quantity,
                'total_component_cost' => $assemblyCost,
                'unit_cost' => $unitCost,
                'status' => 'completed',
                'memo' => $data['memo'] ?? null,
                'journal_entry_id' => $journalEntry?->id,
                'created_by' => $userId,
                'date' => $date,
            ]);

            return $build;
        });
    }

    public function generateBuildNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'BLD-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $last = DB::table('assembly_builds')
            ->where('company_id', $companyId)
            ->where('build_number', 'like', $prefix . '%')
            ->orderByDesc('build_number')
            ->first();

        if ($last) {
            $lastSequence = (int) substr($last->build_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }
}
