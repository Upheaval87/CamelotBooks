<?php

namespace App\Services\Accounting;

use App\Models\Account;
use App\Models\AccountAuditLog;
use App\Models\DefaultAccountMapping;
use App\Models\GoodsReceivedNote;
use App\Models\GrnLine;
use App\Models\Product;
use App\Models\Vendor;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class GoodsReceivedNoteService
{
    protected JournalPostingEngine $postingEngine;
    protected InventoryService $inventoryService;

    public function __construct(JournalPostingEngine $postingEngine, InventoryService $inventoryService)
    {
        $this->postingEngine = $postingEngine;
        $this->inventoryService = $inventoryService;
    }

    public function create(array $data, int $userId): GoodsReceivedNote
    {
        $companyId = $data['company_id'];

        $this->validateVendor($companyId, $data['vendor_id']);

        if (empty($data['lines'])) {
            throw new InvalidArgumentException('At least one GRN line is required.');
        }

        return DB::transaction(function () use ($data, $userId, $companyId) {
            $grnNumber = $this->generateGrnNumber($companyId);

            $grn = GoodsReceivedNote::create([
                'company_id' => $companyId,
                'branch_id' => $data['branch_id'] ?? null,
                'purchase_order_id' => $data['purchase_order_id'] ?? null,
                'vendor_id' => $data['vendor_id'],
                'grn_number' => $grnNumber,
                'date' => $data['date'],
                'status' => GoodsReceivedNote::STATUS_DRAFT,
                'memo' => $data['memo'] ?? null,
                'created_by' => $userId,
            ]);

            foreach ($data['lines'] as $lineData) {
                $totalCost = round($lineData['quantity_received'] * $lineData['unit_cost'], 2);

                GrnLine::create([
                    'goods_received_note_id' => $grn->id,
                    'purchase_order_line_id' => $lineData['purchase_order_line_id'] ?? null,
                    'product_id' => $lineData['product_id'] ?? null,
                    'description' => $lineData['description'],
                    'quantity_ordered' => $lineData['quantity_ordered'] ?? null,
                    'quantity_received' => $lineData['quantity_received'],
                    'unit_cost' => $lineData['unit_cost'],
                    'total_cost' => $totalCost,
                    'expense_account_id' => $lineData['expense_account_id'] ?? null,
                    'cost_center_id' => $lineData['cost_center_id'] ?? null,
                ]);
            }

            $this->logGrnAction($grn, 'created', null, $grn->toArray(), $userId);

            return $grn;
        });
    }

    public function post(GoodsReceivedNote $grn, int $userId): GoodsReceivedNote
    {
        if ($grn->status !== GoodsReceivedNote::STATUS_DRAFT) {
            throw new InvalidArgumentException('Only draft GRNs can be posted.');
        }

        $companyId = $grn->company_id;
        $accruedPurchasesAccount = $this->findAccountByCode($companyId, '2150');

        return DB::transaction(function () use ($grn, $userId, $companyId, $accruedPurchasesAccount) {
            $oldValues = $grn->toArray();

            $lines = $grn->lines()->with('product')->get();
            $jeLines = [];
            $totalDebit = 0;
            $totalCredit = 0;

            foreach ($lines as $line) {
                $debitAccountId = $this->resolveInventoryAccount($line, $companyId);

                $jeLines[] = [
                    'account_id' => $debitAccountId,
                    'debit' => $line->total_cost,
                    'credit' => 0,
                    'memo' => "GRN {$grn->grn_number} - {$line->description}",
                    'entity_type' => GoodsReceivedNote::class,
                    'entity_id' => $grn->id,
                    'cost_center_id' => $line->cost_center_id,
                ];
                $totalDebit += $line->total_cost;

                $jeLines[] = [
                    'account_id' => $accruedPurchasesAccount->id,
                    'debit' => 0,
                    'credit' => $line->total_cost,
                    'memo' => "GRN {$grn->grn_number} - {$line->description}",
                    'entity_type' => GoodsReceivedNote::class,
                    'entity_id' => $grn->id,
                    'cost_center_id' => $line->cost_center_id,
                ];
                $totalCredit += $line->total_cost;

                if ($line->product && $line->product->tracked_as_inventory && $line->product_id) {
                    $qty = (float) $line->quantity_received;
                    if ($qty > 0) {
                        $this->inventoryService->receiveStock(
                            $companyId,
                            $line->product_id,
                            $grn->branch_id,
                            $qty,
                            (float) $line->unit_cost,
                            'grn',
                            $grn->id,
                            $grn->date->format('Y-m-d')
                        );
                    }
                }
            }

            if (round($totalDebit, 2) !== round($totalCredit, 2)) {
                throw new InvalidArgumentException(
                    "Journal entry does not balance. Debit: " . number_format($totalDebit, 2) .
                    ", Credit: " . number_format($totalCredit, 2)
                );
            }

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'created_by' => $userId,
                'date' => $grn->date->format('Y-m-d'),
                'source_module' => 'grn',
                'reference' => $grn->grn_number,
                'memo' => "Goods received note {$grn->grn_number}",
                'branch_id' => $grn->branch_id,
                'lines' => $jeLines,
            ]);

            $grn->update([
                'status' => GoodsReceivedNote::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
            ]);

            if ($grn->purchase_order_id) {
                foreach ($lines as $line) {
                    if ($line->purchase_order_line_id) {
                        $poLine = $line->purchaseOrderLine;
                        if ($poLine) {
                            $poLine->increment('quantity_received', $line->quantity_received);
                            $this->updatePoStatus($poLine->purchaseOrder);
                        }
                    }
                }
            }

            $this->logGrnAction($grn, 'posted', $oldValues, $grn->toArray(), $userId);

            return $grn;
        });
    }

    public function generateGrnNumber(int $companyId): string
    {
        $year = (int) date('Y');
        $prefix = 'GRN-' . $year . '-';

        DB::table('companies')->where('id', $companyId)->lockForUpdate();

        $lastGrn = GoodsReceivedNote::where('company_id', $companyId)
            ->where('grn_number', 'like', $prefix . '%')
            ->orderByDesc('grn_number')
            ->first();

        if ($lastGrn) {
            $lastSequence = (int) substr($lastGrn->grn_number, strlen($prefix));
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        return $prefix . str_pad($newSequence, 4, '0', STR_PAD_LEFT);
    }

    protected function resolveInventoryAccount(GrnLine $line, int $companyId): int
    {
        if ($line->product && $line->product->tracked_as_inventory && $line->product->inventory_asset_account_id) {
            return $line->product->inventory_asset_account_id;
        }

        if ($line->expense_account_id) {
            return $line->expense_account_id;
        }

        $inventoryAccount = DefaultAccountMapping::getAccount($companyId, 'inventory_asset');

        return $inventoryAccount->id;
    }

    protected function updatePoStatus($po): void
    {
        $lines = $po->lines()->get();
        $allReceived = true;
        $anyReceived = false;

        foreach ($lines as $line) {
            if ((float) $line->quantity_received < (float) $line->quantity) {
                $allReceived = false;
            }
            if ((float) $line->quantity_received > 0) {
                $anyReceived = true;
            }
        }

        if ($allReceived) {
            $po->update(['status' => 'fully_received']);
        } elseif ($anyReceived) {
            $po->update(['status' => 'partially_received']);
        }
    }

    protected function validateVendor(int $companyId, int $vendorId): void
    {
        $vendor = Vendor::where('id', $vendorId)
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->first();

        if (!$vendor) {
            throw new InvalidArgumentException("Vendor ID {$vendorId} not found or inactive for this company.");
        }
    }

    protected function findAccountByCode(int $companyId, string $code): Account
    {
        $account = Account::where('company_id', $companyId)
            ->where('code', $code)
            ->first();

        if (!$account) {
            throw new InvalidArgumentException("Account with code {$code} not found for this company.");
        }

        return $account;
    }

    protected function logGrnAction(GoodsReceivedNote $grn, string $action, ?array $oldValues, ?array $newValues, int $userId): void
    {
        AccountAuditLog::create([
            'company_id' => $grn->company_id,
            'journalable_type' => GoodsReceivedNote::class,
            'journalable_id' => $grn->id,
            'action' => $action,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'user_id' => $userId,
            'created_at' => now(),
        ]);
    }
}
