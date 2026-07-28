<?php

namespace App\Services\Reporting;

use App\Models\CreditNote;

class CustomerCreditBalanceService
{
    public function generate(int $companyId): array
    {
        $creditNotes = CreditNote::forCompany($companyId)
            ->whereIn('status', [CreditNote::STATUS_POSTED, CreditNote::STATUS_APPLIED])
            ->with('customer')
            ->get();

        $results = [];
        foreach ($creditNotes as $cn) {
            $unapplied = (float) $cn->amount - (float) $cn->amount_applied;
            if ($unapplied > 0.001) {
                $results[] = [
                    'credit_note_number' => $cn->credit_note_number,
                    'date' => $cn->credit_note_date,
                    'customer_name' => $cn->customer->name ?? 'N/A',
                    'amount' => (float) $cn->amount,
                    'applied' => (float) $cn->amount_applied,
                    'unapplied' => $unapplied,
                ];
            }
        }

        usort($results, fn ($a, $b) => $b['unapplied'] <=> $a['unapplied']);

        return [
            'credit_notes' => $results,
            'total_unapplied' => array_sum(array_column($results, 'unapplied')),
        ];
    }
}
