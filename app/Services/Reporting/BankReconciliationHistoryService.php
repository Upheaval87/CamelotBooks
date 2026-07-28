<?php

namespace App\Services\Reporting;

use App\Models\BankReconciliation;

class BankReconciliationHistoryService
{
    public function generate(int $companyId, ?int $bankAccountId = null): array
    {
        $query = BankReconciliation::where('company_id', $companyId)
            ->with(['bankAccount', 'completedBy'])
            ->orderBy('statement_date', 'desc');

        if ($bankAccountId) {
            $query->where('bank_account_id', $bankAccountId);
        }

        $reconciliations = $query->get()->map(fn ($r) => [
            'id' => $r->id,
            'bank_account' => $r->bankAccount->name ?? 'N/A',
            'bank_account_code' => $r->bankAccount->code ?? '',
            'statement_date' => $r->statement_date,
            'statement_balance' => (float) $r->statement_balance,
            'book_balance' => (float) $r->book_balance,
            'cleared_balance' => (float) $r->cleared_balance,
            'difference' => round((float) $r->statement_balance - (float) $r->cleared_balance, 2),
            'status' => $r->status,
            'completed_by' => $r->completedBy->name ?? '—',
            'completed_at' => $r->completed_at?->format('Y-m-d H:i'),
        ])->toArray();

        $bankAccounts = BankReconciliation::where('company_id', $companyId)
            ->pluck('bank_account_id')
            ->unique()
            ->toArray();

        return [
            'reconciliations' => $reconciliations,
            'bank_account_ids' => $bankAccounts,
            'bank_account_id' => $bankAccountId,
        ];
    }
}
