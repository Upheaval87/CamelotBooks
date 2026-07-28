<?php

namespace App\Services\Reporting;

use App\Models\BankTransaction;
use Illuminate\Support\Facades\DB;

class ChequeRegisterService
{
    public function generate(int $companyId, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $query = BankTransaction::where('company_id', $companyId)
            ->where('type', 'cheque')
            ->with(['bankAccount', 'createdBy'])
            ->orderBy('date');

        if ($dateFrom) {
            $query->where('date', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->where('date', '<=', $dateTo);
        }

        $cheques = $query->get()->map(fn ($tx) => [
            'id' => $tx->id,
            'date' => $tx->date,
            'cheque_number' => $tx->reference,
            'payee' => $tx->description,
            'bank_account' => $tx->bankAccount->name ?? 'N/A',
            'amount' => abs((float) $tx->amount),
            'type' => $tx->amount < 0 ? 'payment' : 'receipt',
            'is_reconciled' => $tx->is_reconciled,
            'created_by' => $tx->createdBy->name ?? '—',
        ])->toArray();

        $totalPayments = collect($cheques)->where('type', 'payment')->sum('amount');
        $totalReceipts = collect($cheques)->where('type', 'receipt')->sum('amount');

        return [
            'cheques' => $cheques,
            'total_payments' => $totalPayments,
            'total_receipts' => $totalReceipts,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
