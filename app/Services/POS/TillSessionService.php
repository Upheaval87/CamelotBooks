<?php

namespace App\Services\POS;

use App\Models\AuditLog;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntry;
use App\Models\PosCashierSession;
use App\Services\Accounting\JournalPostingEngine;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class TillSessionService
{
    public function __construct(
        private JournalPostingEngine $postingEngine
    ) {}

    public function openTill(int $companyId, int $terminalId, int $userId, float $openingFloat, ?string $openedAt = null): PosCashierSession
    {
        $existingOpen = PosCashierSession::where('company_id', $companyId)
            ->where('terminal_id', $terminalId)
            ->where('status', PosCashierSession::STATUS_OPEN)
            ->exists();

        if ($existingOpen) {
            throw new \LogicException('This terminal already has an open till session.');
        }

        $session = PosCashierSession::create([
            'company_id' => $companyId,
            'terminal_id' => $terminalId,
            'user_id' => $userId,
            'opening_float' => $openingFloat,
            'status' => PosCashierSession::STATUS_OPEN,
            'opened_at' => $openedAt ?? now(),
        ]);

        AuditLog::log(
            $companyId,
            $userId,
            PosCashierSession::class,
            $session->id,
            'pos.till.opened',
            null,
            ['opening_float' => $openingFloat, 'terminal_id' => $terminalId],
            "Till opened with float $" . number_format($openingFloat, 2)
        );

        return $session;
    }

    public function closeTill(PosCashierSession $session, float $actualCashCount, ?string $closedAt = null): PosCashierSession
    {
        if ($session->isClosed()) {
            throw new \LogicException('This till session is already closed.');
        }

        $cashSales = 0;
        if (Schema::hasTable('pos_sales') && Schema::hasTable('pos_payments') && Schema::hasTable('pos_payment_methods')) {
            $cashSales = (float) DB::table('pos_payments AS pp')
                ->join('pos_sales AS ps', 'ps.id', '=', 'pp.pos_sale_id')
                ->join('pos_payment_methods AS pm', 'pm.id', '=', 'pp.payment_method_id')
                ->where('ps.cashier_session_id', $session->id)
                ->where('ps.status', 'posted')
                ->where('pm.type', 'cash')
                ->sum('pp.amount');
        }

        $expectedCash = (float) $session->opening_float + $cashSales;
        $variance = round($actualCashCount - $expectedCash, 2);

        $journalEntry = null;

        if ($expectedCash > 0 || $actualCashCount > 0) {
            $journalEntry = $this->postCloseEntry($session, $expectedCash, $actualCashCount, $variance);
        }

        $session->update([
            'status' => PosCashierSession::STATUS_CLOSED,
            'closed_at' => $closedAt ?? now(),
            'actual_cash_count' => $actualCashCount,
            'expected_cash' => $expectedCash,
            'variance' => $variance,
            'journal_entry_id' => $journalEntry?->id,
        ]);

        AuditLog::log(
            $session->company_id,
            $session->user_id,
            PosCashierSession::class,
            $session->id,
            'pos.till.closed',
            ['status' => PosCashierSession::STATUS_OPEN],
            [
                'status' => PosCashierSession::STATUS_CLOSED,
                'actual_cash_count' => $actualCashCount,
                'expected_cash' => $expectedCash,
                'variance' => $variance,
            ],
            "Till closed. Variance: $" . number_format($variance, 2)
        );

        return $session->fresh();
    }

    private function postCloseEntry(PosCashierSession $session, float $expectedCash, float $actualCashCount, float $variance): JournalEntry
    {
        $companyId = $session->company_id;

        $undepositedFunds = DefaultAccountMapping::getAccount($companyId, 'undeposited_funds');
        $cashInDrawer = DefaultAccountMapping::getAccount($companyId, 'cash_in_drawer');
        $cashOverage = DefaultAccountMapping::getAccount($companyId, 'cash_overage');
        $cashShortage = DefaultAccountMapping::getAccount($companyId, 'cash_shortage');

        $lines = [
            [
                'account_id' => $undepositedFunds->id,
                'debit' => $actualCashCount,
                'credit' => 0,
                'description' => 'Till close – cash deposited',
            ],
            [
                'account_id' => $cashInDrawer->id,
                'debit' => 0,
                'credit' => $expectedCash,
                'description' => 'Till close – clear drawer',
            ],
        ];

        if ($variance > 0) {
            $lines[] = [
                'account_id' => $cashOverage->id,
                'debit' => 0,
                'credit' => $variance,
                'description' => 'Cash overage',
            ];
        } elseif ($variance < 0) {
            $lines[] = [
                'account_id' => $cashShortage->id,
                'debit' => abs($variance),
                'credit' => 0,
                'description' => 'Cash shortage',
            ];
        }

        return $this->postingEngine->post([
            'company_id' => $companyId,
            'date' => $session->closed_at ?? now()->toDateString(),
            'reference' => "TILL-CLOSE-{$session->id}",
            'memo' => "Till session #{$session->id} close",
            'lines' => $lines,
            'created_by' => $session->user_id,
            'source_module' => 'pos',
        ]);
    }

    public function getOpenSession(int $companyId, int $terminalId): ?PosCashierSession
    {
        return PosCashierSession::where('company_id', $companyId)
            ->where('terminal_id', $terminalId)
            ->where('status', PosCashierSession::STATUS_OPEN)
            ->first();
    }
}
