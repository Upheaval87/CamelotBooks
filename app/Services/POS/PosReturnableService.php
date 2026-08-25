<?php

namespace App\Services\POS;

use App\Models\AuditLog;
use App\Models\DefaultAccountMapping;
use App\Models\ItemReturnable;
use App\Models\PosReturnable;
use App\Services\Accounting\InventoryService;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosReturnableService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private InventoryService $inventoryService,
        private NumberingSequenceService $numberingService,
    ) {}

    /**
     * Register bottle/container intake and issue a BRR receipt.
     */
    public function intake(array $data, int $userId): PosReturnable
    {
        $this->validateIntakeData($data);

        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];
            $productId = $data['product_id'];
            $bottleCount = (int) $data['bottle_count'];
            $branchId = $data['branch_id'] ?? null;

            // Load the product's returnable packaging record
            $returnable = ItemReturnable::where('company_id', $companyId)
                ->where('item_id', $productId)
                ->first();

            if (!$returnable) {
                throw new InvalidArgumentException(
                    "Product \"{$productId}\" is not configured as a returnable container. Set up returnable packaging in Items → Form."
                );
            }

            $depositValue = (float) $returnable->deposit_value;
            $creditAmount = round($bottleCount * $depositValue, 2);

            // Generate BRR number
            $intakeNumber = $this->numberingService->getNextNumber($companyId, 'bottle_return_receipt');

            // Calculate expiry date from return window
            $expiryDate = null;
            if ($returnable->return_window_days > 0) {
                $expiryDate = now()->addDays($returnable->return_window_days)->toDateString();
            }

            // Create the returnable record
            $returnableRecord = PosReturnable::create([
                'company_id' => $companyId,
                'product_id' => $productId,
                'customer_id' => $data['customer_id'] ?? null,
                'branch_id' => $branchId,
                'quantity' => $bottleCount,
                'bottle_count' => $bottleCount,
                'credit_amount' => $creditAmount,
                'value_each' => $depositValue,
                'intake_number' => $intakeNumber,
                'brr_number' => $intakeNumber,
                'expiry_date' => $expiryDate,
                'status' => PosReturnable::STATUS_PENDING,
                'notes' => $data['notes'] ?? null,
                'created_by' => $userId,
            ]);

            // Post journal entry: DR Returnable Containers / CR Bottle Credits Liability
            $containerAccount = DefaultAccountMapping::getAccount($companyId, 'returnable_containers');
            $liabilityAccount = DefaultAccountMapping::getAccount($companyId, 'bottle_credits_liability');

            if (!$containerAccount || !$liabilityAccount) {
                throw new InvalidArgumentException(
                    'Returnable GL accounts are not configured. Please set up Returnable Containers and Bottle Credits Liability in Settings → Accounts.'
                );
            }

            $journalEntry = $this->postingEngine->post([
                'company_id' => $companyId,
                'branch_id' => $branchId,
                'date' => now()->toDateString(),
                'reference' => "BRR-{$intakeNumber}",
                'memo' => "Bottle intake: {$bottleCount} container(s) – " . ($returnable->container_type ?? 'bottle'),
                'lines' => [
                    [
                        'account_id' => $containerAccount->id,
                        'debit' => $creditAmount,
                        'credit' => 0,
                        'description' => "BRR-{$intakeNumber} – Returnable Containers",
                    ],
                    [
                        'account_id' => $liabilityAccount->id,
                        'debit' => 0,
                        'credit' => $creditAmount,
                        'description' => "BRR-{$intakeNumber} – Bottle Credits Liability",
                    ],
                ],
                'created_by' => $userId,
                'source_module' => 'pos',
            ]);

            $returnableRecord->update(['journal_entry_id' => $journalEntry->id]);

            // Receive inventory for the container if tracking is enabled
            if ($returnable->container_stock_tracking && $returnable->linked_empty_item_id) {
                $this->inventoryService->receiveStock(
                    $companyId,
                    $returnable->linked_empty_item_id,
                    $branchId,
                    $bottleCount,
                    $depositValue,
                    'bottle_intake',
                    $returnableRecord->id,
                    now()->toDateString()
                );
            }

            AuditLog::log(
                $companyId,
                $userId,
                PosReturnable::class,
                $returnableRecord->id,
                'pos.returnable.intake',
                null,
                [
                    'intake_number' => $intakeNumber,
                    'bottle_count' => $bottleCount,
                    'credit_amount' => $creditAmount,
                ],
                "Bottle intake BRR-{$intakeNumber}: {$bottleCount} container(s), credit " . number_format($creditAmount, 2)
            );

            return $returnableRecord->fresh(['product', 'customer', 'branch', 'journalEntry']);
        });
    }

    /**
     * Apply bottle credits to a POS checkout. Returns the credit amount applied and affected IDs.
     */
    public function redeemOnCheckout(int $companyId, int $customerId, ?int $branchId, float $saleSubtotal, int $userId): array
    {
        // Fetch unredeemed, non-expired returnables for this customer (FIFO: oldest first)
        $returnables = PosReturnable::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->unredeemed()
            ->notExpired()
            ->orderBy('created_at', 'asc')
            ->get();

        if ($returnables->isEmpty()) {
            return ['bottle_credit_applied' => 0, 'returnable_ids' => []];
        }

        $totalAvailable = $returnables->sum(fn ($r) => $r->remaining_credit);
        $creditToApply = min(round($totalAvailable, 2), round($saleSubtotal, 2));

        if ($creditToApply <= 0) {
            return ['bottle_credit_applied' => 0, 'returnable_ids' => []];
        }

        return DB::transaction(function () use ($returnables, $companyId, $creditToApply, $userId) {
            $remaining = $creditToApply;
            $usedIds = [];

            foreach ($returnables as $returnable) {
                if ($remaining <= 0) break;

                $available = $returnable->remaining_credit;
                if ($available <= 0) continue;

                $applyToThis = min($available, $remaining);
                $bottlesRedeemed = (int) round($applyToThis / $returnable->value_each);
                if ($bottlesRedeemed < 1) {
                    $bottlesRedeemed = 1;
                    $applyToThis = $returnable->value_each;
                }
                $applyToThis = min(round($bottlesRedeemed * $returnable->value_each, 2), $available);

                $newRedeemedQty = $returnable->redeemed_qty + $bottlesRedeemed;
                $newStatus = $newRedeemedQty >= $returnable->quantity
                    ? PosReturnable::STATUS_REDEEMED
                    : PosReturnable::STATUS_PARTIALLY_REDEEMED;

                $returnable->update([
                    'redeemed_qty' => $newRedeemedQty,
                    'redeemed_at' => now(),
                    'status' => $newStatus,
                ]);

                $remaining = round($remaining - $applyToThis, 2);
                $usedIds[] = $returnable->id;
            }

            $totalApplied = $creditToApply - $remaining;

            // Post journal entry: DR Bottle Credits Liability / CR Returnable Containers
            $liabilityAccount = DefaultAccountMapping::getAccount($companyId, 'bottle_credits_liability');
            $containerAccount = DefaultAccountMapping::getAccount($companyId, 'returnable_containers');

            if ($liabilityAccount && $containerAccount && $totalApplied > 0) {
                $this->postingEngine->post([
                    'company_id' => $companyId,
                    'date' => now()->toDateString(),
                    'reference' => 'BRR-REDEEM',
                    'memo' => 'Bottle credit redemption at POS – ' . number_format($totalApplied, 2),
                    'lines' => [
                        [
                            'account_id' => $liabilityAccount->id,
                            'debit' => $totalApplied,
                            'credit' => 0,
                            'description' => 'Bottle credit redemption',
                        ],
                        [
                            'account_id' => $containerAccount->id,
                            'debit' => 0,
                            'credit' => $totalApplied,
                            'description' => 'Bottle credit redemption',
                        ],
                    ],
                    'created_by' => $userId,
                    'source_module' => 'pos',
                ]);
            }

            return [
                'bottle_credit_applied' => $totalApplied,
                'returnable_ids' => $usedIds,
            ];
        });
    }

    /**
     * Void an unredeemed returnable receipt.
     */
    public function void(int $returnableId, int $companyId, int $userId): PosReturnable
    {
        return DB::transaction(function () use ($returnableId, $companyId, $userId) {
            $returnable = PosReturnable::where('company_id', $companyId)
                ->findOrFail($returnableId);

            if (!$returnable->isVoidable()) {
                throw new InvalidArgumentException(
                    'Cannot void a returnable that has been partially or fully redeemed.'
                );
            }

            $returnable->update(['status' => PosReturnable::STATUS_VOIDED]);

            // Reverse the intake journal entry: DR Liability / CR Container
            $liabilityAccount = DefaultAccountMapping::getAccount($companyId, 'bottle_credits_liability');
            $containerAccount = DefaultAccountMapping::getAccount($companyId, 'returnable_containers');

            if ($liabilityAccount && $containerAccount && $returnable->credit_amount > 0) {
                $this->postingEngine->post([
                    'company_id' => $companyId,
                    'date' => now()->toDateString(),
                    'reference' => "BRR-VOID-{$returnable->intake_number}",
                    'memo' => "Void BRR-{$returnable->intake_number} – " . number_format($returnable->credit_amount, 2),
                    'lines' => [
                        [
                            'account_id' => $liabilityAccount->id,
                            'debit' => $returnable->credit_amount,
                            'credit' => 0,
                            'description' => "Void BRR-{$returnable->intake_number} – reverse liability",
                        ],
                        [
                            'account_id' => $containerAccount->id,
                            'debit' => 0,
                            'credit' => $returnable->credit_amount,
                            'description' => "Void BRR-{$returnable->intake_number} – reverse container",
                        ],
                    ],
                    'created_by' => $userId,
                    'source_module' => 'pos',
                ]);
            }

            // Consume inventory if it was received
            $returnableItem = ItemReturnable::where('company_id', $companyId)
                ->where('item_id', $returnable->product_id)
                ->first();

            if ($returnableItem?->container_stock_tracking && $returnableItem->linked_empty_item_id && $returnable->branch_id) {
                $this->inventoryService->consumeStock(
                    $companyId,
                    $returnableItem->linked_empty_item_id,
                    $returnable->branch_id,
                    $returnable->quantity,
                    now()->toDateString()
                );
            }

            AuditLog::log(
                $companyId,
                $userId,
                PosReturnable::class,
                $returnable->id,
                'pos.returnable.voided',
                ['status' => PosReturnable::STATUS_PENDING],
                ['status' => PosReturnable::STATUS_VOIDED],
                "Voided BRR-{$returnable->intake_number} – credit " . number_format($returnable->credit_amount, 2)
            );

            return $returnable->fresh(['product', 'customer', 'branch']);
        });
    }

    /**
     * Sweep expired returnables: forfeit deposits → revenue.
     */
    public function sweepExpired(int $companyId): Collection
    {
        $expired = PosReturnable::where('company_id', $companyId)
            ->unredeemed()
            ->expired()
            ->get();

        if ($expired->isEmpty()) {
            return collect();
        }

        $containerAccount = DefaultAccountMapping::getAccount($companyId, 'returnable_containers');
        $revenueAccount = DefaultAccountMapping::getAccount($companyId, 'bottle_deposit_revenue');

        if (!$containerAccount || !$revenueAccount) {
            throw new InvalidArgumentException(
                'Returnable GL accounts are not configured. Cannot sweep expired bottle credits.'
            );
        }

        foreach ($expired as $returnable) {
            DB::transaction(function () use ($returnable, $containerAccount, $revenueAccount, $companyId) {
                $returnable->update(['status' => PosReturnable::STATUS_EXPIRED]);

                // Forfeit: DR Liability / CR Revenue
                $creditAmount = $returnable->credit_amount;
                if ($creditAmount > 0) {
                    $this->postingEngine->post([
                        'company_id' => $companyId,
                        'date' => now()->toDateString(),
                        'reference' => "BRR-EXPIRE-{$returnable->intake_number}",
                        'memo' => "Expired BRR-{$returnable->intake_number} – deposit forfeited",
                        'lines' => [
                            [
                                'account_id' => $containerAccount->id,
                                'debit' => 0,
                                'credit' => $creditAmount,
                                'description' => "Expired BRR-{$returnable->intake_number} – container write-off",
                            ],
                            [
                                'account_id' => $revenueAccount->id,
                                'debit' => $creditAmount,
                                'credit' => 0,
                                'description' => "Expired BRR-{$returnable->intake_number} – deposit revenue",
                            ],
                        ],
                        'created_by' => $returnable->created_by,
                        'source_module' => 'pos',
                    ]);
                }
            });
        }

        return $expired;
    }

    /**
     * Get available bottle credit for a customer.
     */
    public function availableCredit(int $companyId, int $customerId): array
    {
        $returnables = PosReturnable::where('company_id', $companyId)
            ->where('customer_id', $customerId)
            ->unredeemed()
            ->notExpired()
            ->get();

        $totalCredit = $returnables->sum(fn ($r) => $r->remaining_credit);

        return [
            'available_credit' => round($totalCredit, 2),
            'receipt_count' => $returnables->count(),
        ];
    }

    private function validateIntakeData(array $data): void
    {
        if (empty($data['company_id'])) {
            throw new InvalidArgumentException('company_id is required.');
        }
        if (empty($data['product_id'])) {
            throw new InvalidArgumentException('product_id is required.');
        }
        if (empty($data['bottle_count']) || (int) $data['bottle_count'] < 1) {
            throw new InvalidArgumentException('bottle_count must be at least 1.');
        }
    }
}
