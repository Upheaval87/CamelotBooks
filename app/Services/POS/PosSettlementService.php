<?php

namespace App\Services\POS;

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\JournalEntry;
use App\Models\NumberingSequence;
use App\Models\PosPayment;
use App\Models\PosPaymentMethod;
use App\Models\PosSettlement;
use App\Services\Accounting\JournalPostingEngine;
use App\Services\Admin\NumberingSequenceService;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class PosSettlementService
{
    public function __construct(
        private JournalPostingEngine $postingEngine,
        private NumberingSequenceService $numberingService
    ) {}

    public function settle(array $data, int $userId): PosSettlement
    {
        $this->validateSettlementData($data);

        return DB::transaction(function () use ($data, $userId) {
            $companyId = $data['company_id'];

            $settlementNumber = $this->numberingService->getNextNumber($companyId, 'pos_settlement');

            $settlement = PosSettlement::create([
                'company_id' => $companyId,
                'payment_method_id' => $data['payment_method_id'],
                'bank_account_id' => $data['bank_account_id'],
                'settlement_number' => $settlementNumber,
                'period_start' => $data['period_start'],
                'period_end' => $data['period_end'],
                'total_amount' => $data['total_amount'],
                'fee_amount' => $data['fee_amount'] ?? 0,
                'net_amount' => round((float) $data['total_amount'] - (float) ($data['fee_amount'] ?? 0), 2),
                'status' => PosSettlement::STATUS_DRAFT,
                'reference' => $data['reference'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $journalEntry = $this->postSettlementEntry($settlement, $companyId, $userId);

            $settlement->update([
                'status' => PosSettlement::STATUS_POSTED,
                'journal_entry_id' => $journalEntry->id,
                'settled_by' => $userId,
                'settled_at' => now(),
            ]);

            AuditLog::log(
                $companyId,
                $userId,
                PosSettlement::class,
                $settlement->id,
                'pos.settlement.created',
                null,
                [
                    'settlement_number' => $settlement->settlement_number,
                    'total_amount' => $settlement->total_amount,
                    'fee_amount' => $settlement->fee_amount,
                    'net_amount' => $settlement->net_amount,
                ],
                "POS Settlement {$settlement->settlement_number} – $" . number_format($settlement->net_amount, 2) . " net"
            );

            return $settlement->fresh(['paymentMethod', 'bankAccount', 'journalEntry', 'settledBy']);
        });
    }

    private function postSettlementEntry(PosSettlement $settlement, int $companyId, int $userId): JournalEntry
    {
        $paymentMethod = $settlement->paymentMethod;
        $bankAccount = $settlement->bankAccount;

        $bankAccountId = $bankAccount->id;
        $clearingAccountId = $paymentMethod->clearing_account_id;

        $merchantFeeAccount = Account::where('company_id', $companyId)->where('code', '6950')->first();

        $lines = [
            [
                'account_id' => $bankAccountId,
                'debit' => $settlement->net_amount,
                'credit' => 0,
                'description' => "Settlement {$settlement->settlement_number} – bank deposit (net)",
            ],
        ];

        if ($settlement->fee_amount > 0 && $merchantFeeAccount) {
            $lines[] = [
                'account_id' => $merchantFeeAccount->id,
                'debit' => $settlement->fee_amount,
                'credit' => 0,
                'description' => "Settlement {$settlement->settlement_number} – processing fee",
            ];
        }

        $lines[] = [
            'account_id' => $clearingAccountId,
            'debit' => 0,
            'credit' => $settlement->total_amount,
            'description' => "Settlement {$settlement->settlement_number} – clear {$paymentMethod->name}",
        ];

        return $this->postingEngine->post([
            'company_id' => $companyId,
            'date' => now()->toDateString(),
            'reference' => "STL-{$settlement->settlement_number}",
            'memo' => "POS Settlement {$settlement->settlement_number} – {$paymentMethod->name}",
            'lines' => $lines,
            'created_by' => $userId,
            'source_module' => 'pos',
        ]);
    }

    public function getUnsettledTotal(int $companyId, int $paymentMethodId): float
    {
        return (float) PosPayment::whereHas('sale', function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->where('status', 'posted');
        })
            ->where('payment_method_id', $paymentMethodId)
            ->sum('amount');
    }

    private function validateSettlementData(array $data): void
    {
        if (empty($data['company_id'])) {
            throw new InvalidArgumentException('company_id is required.');
        }
        if (empty($data['payment_method_id'])) {
            throw new InvalidArgumentException('payment_method_id is required.');
        }
        if (empty($data['bank_account_id'])) {
            throw new InvalidArgumentException('bank_account_id is required.');
        }
        if (empty($data['period_start'])) {
            throw new InvalidArgumentException('period_start is required.');
        }
        if (empty($data['period_end'])) {
            throw new InvalidArgumentException('period_end is required.');
        }
        if (empty($data['total_amount']) || (float) $data['total_amount'] <= 0) {
            throw new InvalidArgumentException('total_amount must be positive.');
        }
        if (!empty($data['fee_amount']) && (float) $data['fee_amount'] < 0) {
            throw new InvalidArgumentException('fee_amount cannot be negative.');
        }
    }
}
