<?php

namespace App\Services\POS;

use App\Models\Account;
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

        return PosCashierSession::create([
            'company_id' => $companyId,
            'terminal_id' => $terminalId,
            'user_id' => $userId,
            'opening_float' => $openingFloat,
            'status' => PosCashierSession::STATUS_OPEN,
            'opened_at' => $openedAt ?? now(),
        ]);
    }

    public function closeTill(PosCashierSession $session, float $actualCashCount, ?string $closedAt = null): PosCashierSession
    {
        if ($session->isClosed()) {
            throw new \LogicException('This till session is already closed.');
        }

        $cashSales = 0;
        if (Schema::hasTable('pos_sales')) {
            $cashSales = DB::table('pos_sales')
                ->where('cashier_session_id', $session->id)
                ->where('status', 'posted')
                ->sum('total');
        }

        $expectedCash = (float) $session->opening_float + (float) $cashSales;
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

        return $session->fresh();
    }

    private function postCloseEntry(PosCashierSession $session, float $expectedCash, float $actualCashCount, float $variance): JournalEntry
    {
        $companyId = $session->company_id;

        $undepositedFunds = Account::where('company_id', $companyId)->where('code', '1050')->firstOrFail();
        $cashInDrawer = Account::where('company_id', $companyId)->where('code', '1060')->firstOrFail();
        $cashOverage = Account::where('company_id', $companyId)->where('code', '7400')->firstOrFail();
        $cashShortage = Account::where('company_id', $companyId)->where('code', '6900')->firstOrFail();

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
