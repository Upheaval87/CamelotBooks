<?php

namespace App\Services\Reporting\Analytics;

use App\Models\Account;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TaxAnalyticsService
{
    public function calculate(int $companyId, array $period, ?int $branchId = null): array
    {
        $payableIds = $this->accountIdsByCodeOrName($companyId, ['2300', '2301'], 'tax payable', 'liability');
        $receivableIds = $this->accountIdsByCodeOrName($companyId, ['1150', '1151'], 'tax receivable', 'asset');

        $vatPayable = count($payableIds) ? $this->glBalance($companyId, $payableIds, $period['as_of']) : 0.0;
        $vatReceivable = count($receivableIds) ? $this->glBalance($companyId, $receivableIds, $period['as_of']) : 0.0;

        $output = (float) DB::table('invoice_lines as il')
            ->join('invoices as i', 'i.id', '=', 'il.invoice_id')
            ->where('i.company_id', $companyId)
            ->whereIn('i.status', ['posted', 'paid', 'partially_paid'])
            ->where('i.invoice_date', '>=', $period['from'])
            ->where('i.invoice_date', '<=', $period['to'])
            ->when($branchId, fn ($q) => $q->where('i.branch_id', $branchId))
            ->sum('il.tax_amount');

        $input = (float) DB::table('bill_lines as bl')
            ->join('bills as b', 'b.id', '=', 'bl.bill_id')
            ->where('b.company_id', $companyId)
            ->whereIn('b.status', ['posted', 'paid', 'partially_paid'])
            ->where('b.bill_date', '>=', $period['from'])
            ->where('b.bill_date', '<=', $period['to'])
            ->when($branchId, fn ($q) => $q->where('b.branch_id', $branchId))
            ->sum('bl.tax_amount');

        $monthly = $this->monthlyOutputInput($companyId, $period, $branchId);

        return [
            'kpis' => [
                'vat_payable' => ['value' => $vatPayable],
                'vat_receivable' => ['value' => $vatReceivable],
                'net_due' => ['value' => max(0, $vatPayable - $vatReceivable)],
                'output_ytd' => ['value' => $output],
                'input_ytd' => ['value' => $input],
                'net_ytd' => ['value' => $output - $input],
            ],
            'monthly_labels' => $monthly['labels'],
            'monthly_output' => $monthly['output'],
            'monthly_input' => $monthly['input'],
        ];
    }

    private function accountIdsByCodeOrName(int $companyId, array $codes, string $namePart, string $type): array
    {
        $codesFound = Account::forCompany($companyId)
            ->where('type', $type)
            ->whereIn('code', $codes)
            ->where('is_active', true)
            ->pluck('id');

        if ($codesFound->count()) {
            return $codesFound->all();
        }

        return Account::forCompany($companyId)
            ->where('type', $type)
            ->where('name', 'like', '%' . $namePart . '%')
            ->where('is_active', true)
            ->pluck('id')
            ->all();
    }

    private function glBalance(int $companyId, array $accountIds, string $asOf): float
    {
        $row = DB::table('journal_entry_lines as l')
            ->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.company_id', $companyId)
            ->whereIn('l.account_id', $accountIds)
            ->whereIn('e.status', ['posted', 'reversed'])
            ->where('e.date', '<=', $asOf)
            ->selectRaw('COALESCE(SUM(l.debit),0) as d, COALESCE(SUM(l.credit),0) as c')
            ->first();

        $accounts = Account::whereIn('id', $accountIds)->get();
        $opening = 0;
        foreach ($accounts as $account) {
            $sign = $account->isDebitNormal() ? 1 : -1;
            $opening += $sign * (float) $account->opening_balance;
        }

        $debitNormal = $accounts->every(fn ($a) => $a->isDebitNormal());

        if ($debitNormal) {
            return ((float) $row->d - (float) $row->c) + $opening;
        }

        return ((float) $row->c - (float) $row->d) + $opening;
    }

    private function monthlyOutputInput(int $companyId, array $period, ?int $branchId): array
    {
        $start = Carbon::parse($period['from'])->startOfMonth();
        $end = Carbon::parse($period['to']);

        $outRows = DB::table('invoice_lines as il')
            ->join('invoices as i', 'i.id', '=', 'il.invoice_id')
            ->where('i.company_id', $companyId)
            ->whereIn('i.status', ['posted', 'paid', 'partially_paid'])
            ->where('i.invoice_date', '>=', $start->format('Y-m-d'))
            ->where('i.invoice_date', '<=', $end->format('Y-m-d'))
            ->when($branchId, fn ($q) => $q->where('i.branch_id', $branchId))
            ->selectRaw("DATE_FORMAT(i.invoice_date, '%Y-%m') as ym, SUM(il.tax_amount) as total")
            ->groupBy('ym')
            ->get();

        $inRows = DB::table('bill_lines as bl')
            ->join('bills as b', 'b.id', '=', 'bl.bill_id')
            ->where('b.company_id', $companyId)
            ->whereIn('b.status', ['posted', 'paid', 'partially_paid'])
            ->where('b.bill_date', '>=', $start->format('Y-m-d'))
            ->where('b.bill_date', '<=', $end->format('Y-m-d'))
            ->when($branchId, fn ($q) => $q->where('b.branch_id', $branchId))
            ->selectRaw("DATE_FORMAT(b.bill_date, '%Y-%m') as ym, SUM(bl.tax_amount) as total")
            ->groupBy('ym')
            ->get();

        $labels = [];
        $output = [];
        $input = [];
        $cursor = $start->copy();

        while ($cursor->format('Y-m') <= $end->format('Y-m')) {
            $key = $cursor->format('Y-m');
            $labels[] = $cursor->format('M y');
            $output[] = (float) ($outRows->firstWhere('ym', $key)->total ?? 0);
            $input[] = (float) ($inRows->firstWhere('ym', $key)->total ?? 0);
            $cursor->addMonth();
        }

        return ['labels' => $labels, 'output' => $output, 'input' => $input];
    }
}