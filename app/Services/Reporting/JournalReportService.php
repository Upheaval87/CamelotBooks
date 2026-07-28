<?php

namespace App\Services\Reporting;

use App\Models\JournalEntry;
use App\Models\JournalEntryLine;

class JournalReportService
{
    public function generate(int $companyId, string $dateFrom, string $dateTo, ?int $branchId = null): array
    {
        $entries = JournalEntry::where('company_id', $companyId)
            ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->where('date', '>=', $dateFrom)
            ->where('date', '<=', $dateTo)
            ->when($branchId, function ($q) use ($branchId) {
                $q->whereHas('lines', fn ($lq) => $lq->where('branch_id', $branchId));
            })
            ->with(['lines.account', 'createdByUser'])
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $totalDebit = 0;
        $totalCredit = 0;

        foreach ($entries as $entry) {
            foreach ($entry->lines as $line) {
                $totalDebit += (float) $line->debit;
                $totalCredit += (float) $line->credit;
            }
        }

        return [
            'entries' => $entries,
            'total_debit' => $totalDebit,
            'total_credit' => $totalCredit,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ];
    }
}
