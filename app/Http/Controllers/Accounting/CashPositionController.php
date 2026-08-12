<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\BankTransaction;
use App\Models\Branch;
use App\Models\Company;
use App\Models\CostCenter;
use App\Models\Currency;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\SystemSetting;
use App\Services\Accounting\BankService;
use App\Services\Accounting\PettyCashService;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CashPositionController extends Controller
{
    public function __construct(
        protected PettyCashService $pettyCashService,
        protected BankService $bankService
    ) {
    }

    public static function periodOptions(): array
    {
        return [
            'today' => 'Today',
            'week' => 'This Week',
            'month' => 'This Month',
            'quarter' => 'This Quarter',
            'year' => 'This Year',
            'custom' => 'Custom Range',
        ];
    }

    public static function periodShortLabels(): array
    {
        return [
            'today' => 'Today',
            'week' => 'Week',
            'month' => 'Month',
            'quarter' => 'Quarter',
            'year' => 'Year',
            'custom' => 'Custom',
        ];
    }

    public static function periodRange(string $period, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $now = now();

        switch ($period) {
            case 'today':
                return [$now->toDateString(), $now->toDateString()];
            case 'week':
                return [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()];
            case 'quarter':
                return [$now->copy()->startOfQuarter()->toDateString(), $now->copy()->endOfQuarter()->toDateString()];
            case 'year':
                return [$now->copy()->startOfYear()->toDateString(), $now->copy()->endOfYear()->toDateString()];
            case 'custom':
                return [$dateFrom ?: $now->copy()->startOfMonth()->toDateString(), $dateTo ?: $now->toDateString()];
            default:
                return [$now->copy()->startOfMonth()->toDateString(), $now->copy()->endOfMonth()->toDateString()];
        }
    }

    public static function transactionTypeOptions(): array
    {
        return [
            '' => 'All',
            'manual' => 'Manual JE',
            'sales_receipt' => 'Sales Receipt',
            'invoice' => 'Sales Invoice',
            'bill' => 'Bill',
            'grn' => 'Goods Received',
            'vendor_payment' => 'Payment',
            'customer_payment' => 'Customer Payment',
            'bank_transfer' => 'Bank Transfer',
            'bank_manual' => 'Bank Manual',
            'make_deposit' => 'Make Deposit',
            'cheque' => 'Cheque',
            'cheque_void' => 'Cheque Void',
            'expense' => 'Expense',
            'credit_note' => 'Credit Note',
            'vendor_credit' => 'Vendor Credit',
            'pos' => 'POS Sale',
            'payroll' => 'Payroll',
            'employee_payment' => 'Employee Payment',
            'petty_cash_establish' => 'Petty Cash Establish',
            'petty_cash_expense' => 'Petty Cash Expense',
            'petty_cash_replenish' => 'Petty Cash Replenish',
            'depreciation_run' => 'Depreciation Run',
            'fixed_asset_disposal' => 'Asset Disposal',
            'fixed_asset_impairment' => 'Asset Impairment',
            'fixed_asset_impairment_reversal' => 'Impairment Reversal',
            'fixed_asset_revaluation' => 'Asset Revaluation',
            'fixed_asset_trueup' => 'Asset True-up',
            'inventory_adjustment' => 'Inventory Adjustment',
            'inventory_transfer' => 'Inventory Transfer',
            'assembly_build' => 'Assembly Build',
            'assembly_unbuild' => 'Assembly Unbuild',
            'stock_count' => 'Stock Count',
            'landed_cost' => 'Landed Cost',
            'realized_fx_gain_loss' => 'FX Gain/Loss',
            'unrealized_fx_revaluation' => 'FX Revaluation',
            'reversal' => 'Reversal',
            'recurring' => 'Recurring',
            'period_close' => 'Period Close',
            'year_end_close' => 'Year End Close',
        ];
    }

    public static function transactionTypeLabel(?string $sourceModule): string
    {
        return self::transactionTypeOptions()[$sourceModule] ?? 'Other';
    }

    public static function statusOptions(): array
    {
        return [
            '' => 'All statuses',
            JournalEntry::STATUS_DRAFT => 'Draft',
            JournalEntry::STATUS_PENDING_APPROVAL => 'Pending Approval',
            JournalEntry::STATUS_POSTED => 'Posted',
            JournalEntry::STATUS_REVERSED => 'Reversed',
        ];
    }

    public static function reconciledOptions(): array
    {
        return [
            '' => 'All',
            'reconciled' => 'Reconciled',
            'unreconciled' => 'Unreconciled',
        ];
    }

    protected function resolveFilters(Request $request): array
    {
        $period = in_array($request->input('period'), array_keys(self::periodOptions()), true)
            ? $request->input('period')
            : 'month';

        [$dateFrom, $dateTo] = self::periodRange($period, $request->input('date_from'), $request->input('date_to'));

        return [
            'period' => $period,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'q' => trim((string) $request->input('q')),
            'account_id' => $request->filled('account_id') ? (int) $request->input('account_id') : null,
            'currency' => $request->filled('currency') ? trim((string) $request->input('currency')) : null,
            'branch_id' => $request->filled('branch_id') ? (int) $request->input('branch_id') : null,
            'cost_center_id' => $request->filled('cost_center_id') ? (int) $request->input('cost_center_id') : null,
            'source_module' => $request->filled('source_module') ? trim((string) $request->input('source_module')) : null,
            'status' => $request->filled('status') ? trim((string) $request->input('status')) : null,
            'reconciled' => in_array($request->input('reconciled'), ['reconciled', 'unreconciled'], true)
                ? $request->input('reconciled')
                : null,
        ];
    }

    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');
        $f = $this->resolveFilters($request);
        $cs = SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        $accounts = $this->cashBankAccounts($companyId, $f);
        $accountIds = $accounts->pluck('id')->all();

        $movement = $this->movementPerAccount($companyId, $accountIds, $f);

        $totals = [
            'opening' => $movement->sum('opening'),
            'receipts' => $movement->sum('receipts'),
            'payments' => $movement->sum('payments'),
            'transfers_in' => $movement->sum('transfers_in'),
            'transfers_out' => $movement->sum('transfers_out'),
            'closing' => $movement->sum('closing'),
            'net' => $movement->sum('receipts') - $movement->sum('payments'),
            'transactions' => $movement->sum('txns'),
        ];

        $chips = [
            'bank' => $movement->where('type', 'bank')->sum('closing'),
            'cash' => $movement->where('type', 'cash')->sum('closing'),
            'unreconciled' => $this->unreconciledSum($companyId, $accountIds, $f),
        ];

        $bars = $this->movementBars($movement);

        $recent = $this->recentTransactions($companyId, $accountIds, $f, 5);

        $accountOptions = Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_bank_account', true)->orWhere('is_petty_cash', true);
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $branches = Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $costCenters = CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);
        $currencies = Currency::query()->active()->ordered()->get();

        $firstBankAccount = Account::where('company_id', $companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->first();
        $reconciliationUrl = $firstBankAccount
            ? route('accounting.bank-reconciliation.index', $firstBankAccount->id)
            : route('accounting.bank-accounts.index');

        $manualTransactionUrl = $firstBankAccount
            ? route('accounting.bank-accounts.manual-form', $firstBankAccount->id)
            : route('accounting.bank-accounts.index');

        return view('accounting.cash-position.index', compact(
            'accounts',
            'accountOptions',
            'movement',
            'totals',
            'chips',
            'bars',
            'recent',
            'branches',
            'costCenters',
            'currencies',
            'reconciliationUrl',
            'manualTransactionUrl',
            'f',
            'cs',
        ));
    }

    protected function cashBankAccounts(int $companyId, array $f): Collection
    {
        return Account::where('company_id', $companyId)
            ->where('is_active', true)
            ->where(function ($q) {
                $q->where('is_bank_account', true)->orWhere('is_petty_cash', true);
            })
            ->when($f['account_id'], fn ($q) => $q->where('id', $f['account_id']))
            ->when($f['currency'], fn ($q) => $q->where('currency', $f['currency']))
            ->when($f['q'], function ($q) use ($f) {
                $q->where(function ($qq) use ($f) {
                    $qq->where('name', 'like', "%{$f['q']}%")
                        ->orWhere('code', 'like', "%{$f['q']}%");
                });
            })
            ->orderBy('code')
            ->get();
    }

    protected function lineSums(int $companyId, array $accountIds, array $f, ?string $dateFrom, ?string $dateTo): Collection
    {
        if ($accountIds === []) {
            return collect();
        }

        $statuses = $f['status']
            ? [$f['status']]
            : [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED];

        return JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entry_lines.account_id', $accountIds)
            ->whereIn('journal_entries.status', $statuses)
            ->when($dateFrom, fn ($q) => $q->where('journal_entries.date', '>=', $dateFrom))
            ->when($dateTo, fn ($q) => $q->where('journal_entries.date', '<=', $dateTo))
            ->when($f['branch_id'], fn ($q) => $q->where('journal_entries.branch_id', $f['branch_id']))
            ->when($f['cost_center_id'], fn ($q) => $q->where('journal_entries.cost_center_id', $f['cost_center_id']))
            ->when($f['source_module'], fn ($q) => $q->where('journal_entries.source_module', $f['source_module']))
            ->when($f['q'], function ($q) use ($f) {
                $q->where(function ($qq) use ($f) {
                    $qq->where('journal_entries.memo', 'like', "%{$f['q']}%")
                        ->orWhere('journal_entries.journal_number', 'like', "%{$f['q']}%")
                        ->orWhere('journal_entries.reference', 'like', "%{$f['q']}%");
                });
            })
            ->groupBy('journal_entry_lines.account_id')
            ->selectRaw(
                'journal_entry_lines.account_id AS account_id, '
                . 'SUM(journal_entry_lines.debit) AS d, '
                . 'SUM(journal_entry_lines.credit) AS c, '
                . 'COUNT(*) AS n, '
                . 'SUM(CASE WHEN journal_entries.source_module = ? THEN journal_entry_lines.debit ELSE 0 END) AS d_t, '
                . 'SUM(CASE WHEN journal_entries.source_module = ? THEN journal_entry_lines.credit ELSE 0 END) AS c_t',
                ['bank_transfer', 'bank_transfer']
            )
            ->get()
            ->keyBy('account_id');
    }

    protected function movementPerAccount(int $companyId, array $accountIds, array $f): Collection
    {
        if ($accountIds === []) {
            return collect();
        }

        $accounts = $this->cashBankAccounts($companyId, $f)->keyBy('id');

        $prePeriod = $this->lineSums($companyId, $accountIds, $f, null, date('Y-m-d', strtotime($f['date_from'] . ' -1 day')));
        $period = $this->lineSums($companyId, $accountIds, $f, $f['date_from'], $f['date_to']);

        $rows = [];

        foreach ($accounts as $account) {
            $isDebitNormal = $account->isDebitNormal();
            $pre = $prePeriod->get($account->id);
            $cur = $period->get($account->id);

            $openingSum = $isDebitNormal
                ? (float) ($pre->d ?? 0) - (float) ($pre->c ?? 0)
                : (float) ($pre->c ?? 0) - (float) ($pre->d ?? 0);

            $opening = $openingSum + (float) $account->opening_balance;

            $d = (float) ($cur->d ?? 0);
            $c = (float) ($cur->c ?? 0);
            $dT = (float) ($cur->d_t ?? 0);
            $cT = (float) ($cur->c_t ?? 0);

            $receipts = $isDebitNormal ? $d - $dT : $c - $cT;
            $payments = $isDebitNormal ? $c - $cT : $d - $dT;
            $transfersIn = $isDebitNormal ? $dT : $cT;
            $transfersOut = $isDebitNormal ? $cT : $dT;

            $rows[] = [
                'id' => $account->id,
                'code' => $account->code,
                'name' => $account->name,
                'type' => $account->is_bank_account ? 'bank' : 'cash',
                'currency' => $account->currency ?: '—',
                'opening' => round($opening, 2),
                'receipts' => round($receipts, 2),
                'payments' => round($payments, 2),
                'transfers_in' => round($transfersIn, 2),
                'transfers_out' => round($transfersOut, 2),
                'closing' => round($opening + $receipts - $payments + $transfersIn - $transfersOut, 2),
                'net' => round($receipts - $payments, 2),
                'txns' => (int) ($cur->n ?? 0),
                'reconciled' => $account->is_bank_account
                    ? round($this->bankService->getReconciledBalance($account->id), 2)
                    : null,
            ];
        }

        return collect($rows);
    }

    protected function unreconciledSum(int $companyId, array $accountIds, array $f): float
    {
        if ($accountIds === []) {
            return 0.0;
        }

        return round((float) BankTransaction::query()
            ->where('company_id', $companyId)
            ->whereIn('bank_account_id', $accountIds)
            ->where('is_reconciled', false)
            ->where('date', '>=', $f['date_from'])
            ->where('date', '<=', $f['date_to'])
            ->when($f['branch_id'], fn ($q) => $q->where('branch_id', $f['branch_id']))
            ->when($f['cost_center_id'], fn ($q) => $q->where('cost_center_id', $f['cost_center_id']))
            ->when($f['q'], function ($q) use ($f) {
                $q->where(function ($qq) use ($f) {
                    $qq->where('description', 'like', "%{$f['q']}%")
                        ->orWhere('reference', 'like', "%{$f['q']}%");
                });
            })
            ->sum('amount'), 2);
    }

    protected function movementBars(Collection $movement): array
    {
        $in = $movement
            ->sortByDesc('receipts')
            ->take(5)
            ->map(fn ($r) => ['id' => $r['id'], 'code' => $r['code'], 'name' => $r['name'], 'value' => $r['receipts']])
            ->values();

        $out = $movement
            ->sortByDesc('payments')
            ->take(5)
            ->map(fn ($r) => ['id' => $r['id'], 'code' => $r['code'], 'name' => $r['name'], 'value' => $r['payments']])
            ->values();

        return [
            'in' => $in,
            'out' => $out,
            'max_in' => max($in->max('value'), 0.01),
            'max_out' => max($out->max('value'), 0.01),
        ];
    }

    protected function recentTransactions(int $companyId, array $accountIds, array $f, int $limit = 5): Collection
    {
        if ($accountIds === []) {
            return collect();
        }

        return BankTransaction::query()
            ->join('accounts', 'accounts.id', '=', 'bank_transactions.bank_account_id')
            ->leftJoin('journal_entries', 'journal_entries.id', '=', 'bank_transactions.journal_entry_id')
            ->where('bank_transactions.company_id', $companyId)
            ->whereIn('bank_transactions.bank_account_id', $accountIds)
            ->where('bank_transactions.date', '>=', $f['date_from'])
            ->where('bank_transactions.date', '<=', $f['date_to'])
            ->when($f['branch_id'], fn ($q) => $q->where('bank_transactions.branch_id', $f['branch_id']))
            ->when($f['cost_center_id'], fn ($q) => $q->where('bank_transactions.cost_center_id', $f['cost_center_id']))
            ->when($f['source_module'], fn ($q) => $q->where('journal_entries.source_module', $f['source_module']))
            ->when($f['status'], fn ($q) => $q->where('journal_entries.status', $f['status']))
            ->when($f['reconciled'] === 'reconciled', fn ($q) => $q->where('bank_transactions.is_reconciled', true))
            ->when($f['reconciled'] === 'unreconciled', fn ($q) => $q->where('bank_transactions.is_reconciled', false))
            ->when($f['q'], function ($q) use ($f) {
                $q->where(function ($qq) use ($f) {
                    $qq->where('bank_transactions.description', 'like', "%{$f['q']}%")
                        ->orWhere('bank_transactions.reference', 'like', "%{$f['q']}%")
                        ->orWhere('accounts.name', 'like', "%{$f['q']}%")
                        ->orWhere('accounts.code', 'like', "%{$f['q']}%")
                        ->orWhere('journal_entries.memo', 'like', "%{$f['q']}%")
                        ->orWhere('journal_entries.journal_number', 'like', "%{$f['q']}%");
                });
            })
            ->orderBy('bank_transactions.date', 'desc')
            ->orderBy('bank_transactions.id', 'desc')
            ->limit($limit)
            ->get([
                'bank_transactions.id',
                'bank_transactions.bank_account_id',
                'bank_transactions.type',
                'bank_transactions.source_type',
                'bank_transactions.date',
                'bank_transactions.description',
                'bank_transactions.reference',
                'bank_transactions.amount',
                'bank_transactions.is_reconciled',
                'bank_transactions.journal_entry_id',
                'accounts.name AS account_name',
                'accounts.code AS account_code',
                'journal_entries.source_module AS source_module',
                'journal_entries.status AS je_status',
                'journal_entries.journal_number AS journal_number',
            ])
            ->map(function ($txn) {
                $amount = (float) $txn->amount;
                $txn->debit = $amount > 0 ? $amount : 0.0;
                $txn->credit = $amount < 0 ? abs($amount) : 0.0;
                $txn->source_label = self::transactionTypeLabel($txn->source_module);
                [$txn->source_kind, $txn->source_url] = $this->sourceDocFor($txn);
                return $txn;
            });
    }

    protected function sourceDocFor($txn): array
    {
        $jeUrl = $txn->journal_entry_id
            ? route('accounting.journal-entries.show', $txn->journal_entry_id)
            : null;

        switch ($txn->source_type) {
            case 'customer_payment':
                return ['Receipt', $txn->source_id ? route('accounting.customer-payments.show', $txn->source_id) : $jeUrl];
            case 'vendor_payment':
                return ['Payment', $txn->source_id ? route('accounting.vendor-payments.show', $txn->source_id) : $jeUrl];
            case 'transfer':
                return ['Transfer', $jeUrl];
            case 'make_deposit':
                return ['Deposit', $jeUrl];
            case 'cheque':
                return ['Cheque', $jeUrl];
            case 'petty_cash':
                return ['Petty Cash', $jeUrl];
            case 'manual':
                return ['Manual', $jeUrl];
            default:
                return [$txn->source_label ?: 'Entry', $jeUrl];
        }
    }

    public function exportCsv(Request $request)
    {
        $companyId = (int) session('current_company_id');
        $f = $this->resolveFilters($request);
        $accounts = $this->cashBankAccounts($companyId, $f);
        $movement = $this->movementPerAccount($companyId, $accounts->pluck('id')->all(), $f);

        $filename = "cash_position_{$f['date_from']}_to_{$f['date_to']}.csv";

        return Response::streamDownload(function () use ($f, $movement) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Cash Position', '', '']);
            fputcsv($handle, ['Period', $f['date_from'] . ' to ' . $f['date_to'], '']);
            fputcsv($handle, ['', '', '']);

            fputcsv($handle, ['Account', 'Currency', 'Opening', 'Receipts', 'Payments', 'Transfers In', 'Transfers Out', 'Closing']);
            foreach ($movement as $row) {
                fputcsv($handle, [
                    $row['code'] . ' - ' . $row['name'],
                    $row['currency'],
                    number_format($row['opening'], 2, '.', ''),
                    number_format($row['receipts'], 2, '.', ''),
                    number_format($row['payments'], 2, '.', ''),
                    number_format($row['transfers_in'], 2, '.', ''),
                    number_format($row['transfers_out'], 2, '.', ''),
                    number_format($row['closing'], 2, '.', ''),
                ]);
            }
            fputcsv($handle, [
                'TOTAL',
                '',
                number_format($movement->sum('opening'), 2, '.', ''),
                number_format($movement->sum('receipts'), 2, '.', ''),
                number_format($movement->sum('payments'), 2, '.', ''),
                number_format($movement->sum('transfers_in'), 2, '.', ''),
                number_format($movement->sum('transfers_out'), 2, '.', ''),
                number_format($movement->sum('closing'), 2, '.', ''),
            ]);

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv', 'Content-Disposition' => "attachment; filename=\"{$filename}\""]);
    }

    public function exportPdf(Request $request)
    {
        return $this->renderPrint($request, false);
    }

    public function print(Request $request)
    {
        return $this->renderPrint($request, true);
    }

    protected function renderPrint(Request $request, bool $direct)
    {
        $companyId = (int) session('current_company_id');
        $f = $this->resolveFilters($request);
        $company = Company::findOrFail($companyId);

        $accounts = $this->cashBankAccounts($companyId, $f);
        $movement = $this->movementPerAccount($companyId, $accounts->pluck('id')->all(), $f);

        $content = view('accounting.cash-position.print', compact('company', 'f', 'accounts', 'movement'))->render();

        if ($direct) {
            return response($content)->header('Content-Type', 'text/html');
        }

        return response()->view('accounting.print-export', [
            'title' => "Cash Position {$f['date_from']} to {$f['date_to']}",
            'content' => $content,
        ])->header('Content-Type', 'text/html');
    }
}
