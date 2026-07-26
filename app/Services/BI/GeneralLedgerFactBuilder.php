<?php

namespace App\Services\BI;

use Illuminate\Support\Facades\DB;

class GeneralLedgerFactBuilder
{
    public function build(): int
    {
        $now = now();

        $inserts = [];

        DB::table('journal_entry_lines AS jel')
            ->join('journal_entries AS je', 'je.id', '=', 'jel.journal_entry_id')
            ->where('je.status', 'posted')
            ->select(
                'je.company_id',
                'je.date',
                'jel.account_id',
                'jel.branch_id',
                'jel.cost_center_id',
                'je.id AS journal_entry_id',
                'je.journal_number',
                'je.source_module',
                'jel.entity_type',
                'jel.entity_id',
                'jel.debit',
                'jel.credit',
                'jel.foreign_amount',
                'jel.foreign_currency',
                'jel.exchange_rate',
                'jel.memo'
            )
            ->orderBy('jel.id')
            ->chunk(2000, function ($rows) use (&$inserts, $now) {
                foreach ($rows as $row) {
                    $inserts[] = [
                        'company_key'      => $row->company_id,
                        'date_key'         => (int) $row->date,
                        'account_key'      => $row->account_id,
                        'branch_key'       => $row->branch_id,
                        'cost_center_key'  => $row->cost_center_id,
                        'journal_entry_id' => $row->journal_entry_id,
                        'journal_number'   => $row->journal_number,
                        'source_module'    => $row->source_module ?? 'manual',
                        'entity_type'      => $row->entity_type ?? 'journal_entry',
                        'entity_id'        => $row->entity_id ?? $row->journal_entry_id,
                        'debit'            => $row->debit,
                        'credit'           => $row->credit,
                        'foreign_amount'   => $row->foreign_amount,
                        'foreign_currency' => $row->foreign_currency,
                        'exchange_rate'    => $row->exchange_rate,
                        'memo'             => $row->memo,
                        'refreshed_at'     => $now,
                    ];
                }

                DB::table('fact_general_ledger')->insert($inserts);
                $inserts = [];
            });

        if ($inserts) {
            DB::table('fact_general_ledger')->insert($inserts);
        }

        return DB::table('fact_general_ledger')->count();
    }
}
