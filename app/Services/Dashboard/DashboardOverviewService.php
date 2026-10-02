<?php

namespace App\Services\Dashboard;

use App\Models\Account;
use App\Models\Bill;
use App\Models\Cheque;
use App\Models\CustomerPayment;
use App\Models\Invoice;
use App\Models\JournalEntry;
use App\Models\SystemSetting;
use App\Models\TaxObligation;
use App\Models\TaxReturn;
use App\Models\TodoTask;
use App\Models\User;
use App\Models\VendorPayment;
use App\Services\Reporting\IncomeStatementService;
use App\Services\Reporting\Analytics\RevenueExpenseTrendService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class DashboardOverviewService
{
    // Presets keyed by query param value.
    public const PRESET_MONTH = 'month';
    public const PRESET_QUARTER = 'quarter';
    public const PRESET_YTD = 'ytd';

    private const PRESETS = [
        self::PRESET_MONTH => [
            'label' => 'This Month',
            'vs' => 'last month',
            'sub' => 'M',
        ],
        self::PRESET_QUARTER => [
            'label' => 'This Quarter',
            'vs' => 'last quarter',
            'sub' => 'Q',
        ],
        self::PRESET_YTD => [
            'label' => 'Year to Date',
            'vs' => 'last year',
            'sub' => 'Y',
        ],
    ];

    private IncomeStatementService $incomeStatement;

    public function __construct(?IncomeStatementService $incomeStatement = null)
    {
        $this->incomeStatement = $incomeStatement ?? new IncomeStatementService();
    }

    /**
     * Build the full dashboard overview payload for one company + preset.
     * Read-only: never writes to the database.
     */
    public function build(int $companyId, string $preset, ?int $userId = null): array
    {
        $preset = $this->normalizePreset($preset);
        $now = Carbon::now();

        [$from, $to, $prevFrom, $prevTo] = $this->resolveRange($preset, $now);

        $symbol = (string) SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');
        $decimals = (int) SystemSetting::getValue('currency', 'decimal_places', $companyId, 2);
        $dateFormat = (string) SystemSetting::getValue('regional', 'date_format', $companyId, 'Y-m-d');

        $isCurrent = $this->incomeStatement->generate($companyId, null, $from, $to);
        $isPrior = $this->incomeStatement->generate($companyId, null, $prevFrom, $prevTo);

        $revenue = (float) $isCurrent['total_income'];
        $priorRevenue = (float) $isPrior['total_income'];
        $expenses = (float) $isCurrent['total_expenses'];
        $priorExpenses = (float) $isPrior['total_expenses'];
        $net = (float) $isCurrent['net_income'];
        $priorNet = (float) ($isPrior['net_income'] ?? ($isPrior['total_income'] - $isPrior['total_expenses']));

        $outstanding = $this->outstandingInvoices($companyId);
        $payables = $this->openBills($companyId);
        $cash = $this->cashPosition($companyId);

        $chart = $this->chartData($companyId);
        $chart['total_revenue'] = round(array_sum($chart['revenue']), 2);
        $chart['total_expenses'] = round(array_sum($chart['expenses']), 2);

        $aging = $this->receivablesAging($companyId);
        $upcoming = $this->upcomingBillsAndTaxes($companyId, $symbol, $decimals, $dateFormat);
        $tasks = $this->tasks($companyId, $userId);
        $activity = $this->recentActivity($companyId, $symbol, $decimals, $dateFormat);

        $empty = $this->isEmptyCompany($companyId);

        $periodSub = match ($preset) {
            self::PRESET_MONTH => $from->format('M'),
            self::PRESET_QUARTER => 'Q' . (int) floor(($from->month - 1) / 3 + 1) . ' ' . $from->year,
            default => (string) $to->year . ' YTD',
        };

        // The data window deliberately stops at "today" (month-to-date), but the
        // label must advertise the selected PERIOD, not the elapsed window —
        // otherwise a mid-month dashboard reads "Oct 1 - Oct 2".
        $labelTo = match ($preset) {
            self::PRESET_MONTH => $from->copy()->endOfMonth(),
            self::PRESET_QUARTER => $from->copy()->endOfQuarter(),
            default => $from->copy()->endOfYear(),
        };
        $rangeLabel = $from->format('M j') . ' – ' . $labelTo->format('M j');

        $margin = $revenue != 0.0 ? $net / $revenue * 100 : 0.0;
        $netKpi = $this->kpiValue($net, $priorNet, 'Net profit for ' . $periodSub);
        $netKpi['margin'] = (float) number_format($margin, 1, '.', '');
        $netKpi['margin_dir'] = $margin < 0 ? 'dn' : 'up';

        return [
            'preset' => $preset,
            'preset_label' => self::PRESETS[$preset]['label'],
            'vs_label' => 'vs ' . self::PRESETS[$preset]['vs'],
            'period_sub' => $periodSub,
            'range_label' => $rangeLabel,
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'sec_range' => $rangeLabel . ' · How money came in, went out, and stands right now.',
            'cs' => $symbol,
            'code' => (string) SystemSetting::getValue('currency', 'base_currency', $companyId, ''),
            'decimals' => $decimals,
            'date_format' => $dateFormat,
            'today_display' => $now->format('l, ') . $now->format($dateFormat),
            'kpi' => [
                'revenue' => $this->kpiValue($revenue, $priorRevenue, 'Total Income for ' . $periodSub),
                'expenses' => $this->kpiValue($expenses, $priorExpenses, 'Total Expenses for ' . $periodSub),
                'net' => $netKpi,
                'outstanding' => [
                    'value' => $outstanding['total'],
                    'sub' => $outstanding['unpaid'] . ' unpaid · ' . $outstanding['overdue'] . ' overdue',
                    'unpaid' => $outstanding['unpaid'],
                    'overdue' => $outstanding['overdue'],
                    'href' => route('accounting.invoices.index', ['status' => 'unpaid']),
                ],
                'payables' => [
                    'value' => $payables['total'],
                    'sub' => $payables['open'] . ' open · ' . $payables['due_week'] . ' due this week',
                    'open' => $payables['open'],
                    'overdue' => $payables['overdue'],
                    'overdue_amount' => $payables['overdue_amount'],
                    'href' => route('accounting.bills.index', ['status' => 'unpaid']),
                ],
                'cash' => [
                    'value' => $cash['total'],
                    'sub' => count($cash['rows']) . ' accounts',
                    'num_accounts' => $cash['num_accounts'],
                ],
            ],
            'cash' => $cash,
            'chart' => $chart,
            'aging' => $aging,
            'upcoming' => $upcoming,
            'tasks' => $tasks,
            'activity' => $activity,
            'empty' => $empty,
        ];
    }

    public function normalizePreset(?string $preset): string
    {
        $preset = (string) $preset;
        return in_array($preset, array_keys(self::PRESETS), true) ? $preset : self::PRESET_MONTH;
    }

    /**
     * @return array{0: Carbon, 1: Carbon, 2: Carbon, 3: Carbon} current + previous ranges
     */
    private function resolveRange(string $preset, Carbon $now): array
    {
        return match ($preset) {
            self::PRESET_MONTH => [
                $now->copy()->startOfMonth(),
                $now->copy(),
                $now->copy()->subMonthNoOverflow()->startOfMonth(),
                $now->copy()->subMonthNoOverflow(),
            ],
            self::PRESET_QUARTER => [
                $now->copy()->startOfQuarter(),
                $now->copy(),
                $now->copy()->subMonthsNoOverflow(3)->startOfQuarter(),
                $now->copy()->subMonthsNoOverflow(3),
            ],
            default => [
                $now->copy()->startOfYear(),
                $now->copy(),
                $now->copy()->startOfYear()->subYear(),
                $now->copy()->subYear(),
            ],
        };
    }

    /**
     * @return array{value: float, prev: float, delta: float, dir: string, sub: string}
     */
    private function kpiValue(float $current, float $prior, string $sub): array
    {
        $delta = $prior != 0.0
            ? ($current - $prior) / abs($prior) * 100
            : ($current != 0.0 ? 100.0 : 0.0);

        $dir = abs($delta) < 0.05 ? 'nt' : ($delta > 0 ? 'up' : 'dn');

        return [
            'value' => $current,
            'prev' => $prior,
            'delta' => (float) number_format($delta, 1, '.', ''),
            'dir' => $dir,
            'sub' => $sub,
        ];
    }

    /**
     * @return array{total: float, unpaid: int, overdue: int}
     */
    private function outstandingInvoices(int $companyId): array
    {
        $base = $this->openInvoicesQuery($companyId);

        $total = (float) (clone $base)->selectRaw('SUM(amount - amount_paid) as open_balance')->value('open_balance');
        $unpaid = (int) (clone $base)->count('id');
        $overdue = (int) (clone $base)
            ->where(function ($q) {
                $q->where('status', Invoice::STATUS_OVERDUE)
                    ->orWhere(fn ($q2) => $q2->where('due_date', '<', Carbon::today()));
            })
            ->count('id');

        return ['total' => $total, 'unpaid' => $unpaid, 'overdue' => $overdue];
    }

    /**
     * @return array{total: float, open: int, due_week: int, rows: array}
     */
    private function openBills(int $companyId): array
    {
        $base = \App\Models\Bill::query()
            ->forCompany($companyId)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->whereColumn('amount_paid', '<', 'amount');

        $total = (float) (clone $base)->selectRaw('SUM(amount - amount_paid) as open_balance')->value('open_balance');
        $open = (int) (clone $base)->count('id');
        $dueWeek = (int) (clone $base)
            ->whereBetween('due_date', [Carbon::today(), Carbon::today()->endOfWeek()])
            ->count('id');

        $overdueQuery = (clone $base)->where(function ($q) {
            $q->where('status', Bill::STATUS_OVERDUE)
                ->orWhere('due_date', '<', Carbon::today());
        });

        $overdue = (int) (clone $overdueQuery)->count('id');
        $overdueAmount = (float) (clone $overdueQuery)
            ->selectRaw('SUM(amount - amount_paid) as overdue_balance')
            ->value('overdue_balance');

        return [
            'total' => $total,
            'open' => $open,
            'due_week' => $dueWeek,
            'overdue' => $overdue,
            'overdue_amount' => $overdueAmount,
            'rows' => [],
        ];
    }

    /**
     * @return array{total: float, num_accounts: int, rows: array, cheques: int}
     */
    private function cashPosition(int $companyId): array
    {
        $banks = Account::query()
            ->forCompany($companyId)
            ->where('is_bank_account', true)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $petty = Account::query()
            ->forCompany($companyId)
            ->where('is_petty_cash', true)
            ->where('is_active', true)
            ->get();

        $rows = [];

        foreach ($banks as $bank) {
            $rows[] = [
                'name' => $bank->name,
                'value' => (float) $bank->current_balance,
                'badge' => 'BANK',
            ];
        }

        $pettyTotal = 0.0;
        foreach ($petty as $fund) {
            $pettyTotal += (float) $fund->current_balance;
        }

        if (count($petty) > 0) {
            $rows[] = [
                'name' => count($petty) === 1 ? $petty->first()->name : 'Cash in Drawer',
                'value' => $pettyTotal,
                'badge' => 'CASH',
            ];
        }

        $cheques = (int) Cheque::query()->forCompany($companyId)->outstanding()->count('id');

        return [
            'total' => round(array_sum(array_column($rows, 'value')), 2),
            'num_accounts' => count($rows),
            'rows' => $rows,
            'cheques' => $cheques,
        ];
    }

    /**
     * @return array{labels: array, revenue: array, expenses: array, caption: string, max: float, rev_pct: array, exp_pct: array}
     */
    private function chartData(int $companyId): array
    {
        $trend = (new RevenueExpenseTrendService())->calculate(
            $companyId,
            Carbon::now()->startOfMonth()->subMonthsNoOverflow(5)->toDateString(),
            Carbon::now()->endOfMonth()->toDateString(),
            6
        );

        $labels = [];
        $revenue = [];
        $expenses = [];

        foreach ($trend['labels'] as $i => $label) {
            $labels[] = Carbon::parse($label)->format('M');
            $revenue[] = (float) ($trend['revenue_data'][$i] ?? 0);
            $expenses[] = (float) ($trend['expense_data'][$i] ?? 0);
        }

        $max = max(array_merge($revenue, $expenses, [1.0]));

        $revPct = array_map(fn ($v) => (float) number_format($v / $max * 100, 2, '.', ''), $revenue);
        $expPct = array_map(fn ($v) => (float) number_format($v / $max * 100, 2, '.', ''), $expenses);

        return [
            'labels' => $labels,
            'revenue' => $revenue,
            'expenses' => $expenses,
            'caption' => 'Last 6 months',
            'max' => $max,
            'rev_pct' => $revPct,
            'exp_pct' => $expPct,
        ];
    }

    /**
     * @return array{total: float, buckets: array}
     */
    private function receivablesAging(int $companyId): array
    {
        $open = $this->openInvoicesQuery($companyId)
            ->get(['id', 'due_date', 'amount', 'amount_paid']);

        $today = Carbon::today();

        $buckets = [
            ['label' => 'Current', 'amount' => 0.0, 'count' => 0, 'tone' => 'teal'],
            ['label' => '1–30', 'amount' => 0.0, 'count' => 0, 'tone' => 'amber'],
            ['label' => '31–60', 'amount' => 0.0, 'count' => 0, 'tone' => 'orange'],
            ['label' => '60+', 'amount' => 0.0, 'count' => 0, 'tone' => 'red'],
        ];

        foreach ($open as $invoice) {
            $days = $invoice->due_date ? (int) $invoice->due_date->diffInDays($today, false) : -1;

            $idx = match (true) {
                $days <= 0 => 0,
                $days <= 30 => 1,
                $days <= 60 => 2,
                default => 3,
            };

            $buckets[$idx]['amount'] += (float) $invoice->balance_due;
            $buckets[$idx]['count']++;
        }

        $maxBucket = max(array_column($buckets, 'amount'));
        $widthBase = $maxBucket > 0 ? $maxBucket : 1.0;
        $total = (float) $open->sum(fn (Invoice $inv) => (float) $inv->balance_due);

        foreach ($buckets as &$bucket) {
            $bucket['pct'] = (float) number_format($bucket['amount'] / $widthBase * 100, 1, '.', '');
            $bucket['pct_total'] = $total > 0
                ? (float) number_format($bucket['amount'] / $total * 100, 1, '.', '')
                : 0.0;
        }
        unset($bucket);

        return [
            'total' => $total,
            'buckets' => $buckets,
        ];
    }

    /**
     * @return array{bills: array, taxes: array, items: array, count: int, overdue_total: float}
     */
    private function upcomingBillsAndTaxes(int $companyId, string $symbol, int $decimals, string $dateFormat): array
    {
        $today = Carbon::today();
        $cutoff = $today->copy()->addDays(45);

        $bills = Bill::query()
            ->forCompany($companyId)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->whereColumn('amount_paid', '<', 'amount')
            ->whereHas('vendor')
            ->with([
                'vendor:id,name,payment_terms,payment_terms_days',
                'purchaseOrder:id,po_number',
            ])
            ->orderBy('due_date')
            ->take(5)
            ->get()
            ->map(function (Bill $bill) use ($today, $dateFormat) {
                $due = $bill->due_date;
                $daysUntil = $due ? (int) $today->diffInDays($due, false) : 0;
                $overdue = $due ? $due->lt($today) : false;
                $ref = $bill->bill_number ?: $bill->internal_number;
                $vendorName = $bill->vendor?->name;

                $terms = $bill->vendor?->payment_terms
                    ?: ($bill->vendor?->payment_terms_days ? 'Net ' . $bill->vendor->payment_terms_days : null);
                $poNumber = $bill->po_number ?: $bill->purchaseOrder?->po_number;

                $rows = [];
                $rows[] = ['l' => 'Vendor', 'v' => $vendorName ?: '—', 'chip' => null];
                $rows[] = ['l' => 'Issue date', 'v' => $this->fmtDate($bill->bill_date, $dateFormat) ?: '—', 'chip' => null];
                $rows[] = [
                    'l' => 'Due date',
                    'v' => $this->fmtDate($due, $dateFormat) ?: '—',
                    'chip' => $overdue
                        ? ['tone' => 'over', 'text' => 'Overdue by ' . $this->dayWord(abs($daysUntil))]
                        : null,
                ];

                if ($terms) {
                    $rows[] = ['l' => 'Terms', 'v' => $terms, 'chip' => null];
                }
                if ($poNumber) {
                    $rows[] = ['l' => 'Linked PO', 'v' => $poNumber, 'chip' => null];
                }

                $rows[] = ['l' => 'Status', 'v' => '', 'chip' => ['tone' => 'bad', 'text' => 'Unpaid']];

                return [
                    'kind' => 'bill',
                    'icon' => 'doc',
                    'id' => $bill->id,
                    'ref' => $ref,
                    'name' => $vendorName ?: $ref,
                    'ref_line' => $ref,
                    'due' => $this->fmtDate($due, $dateFormat),
                    'due_label' => $overdue
                        ? 'Overdue'
                        : ($due ? 'Due ' . $due->format('j M') : 'No due date'),
                    'due_tone' => $overdue ? 'over' : ($daysUntil >= 0 && $daysUntil <= 14 ? 'soon' : null),
                    'date' => $due,
                    'date_ts' => $due ? $due->getTimestamp() : 0,
                    'amount' => (float) $bill->balance_due,
                    'overdue' => $overdue,
                    'overdue_days' => $overdue ? abs($daysUntil) : 0,
                    'href' => route('accounting.bills.show', $bill->id),
                    'rows' => $rows,
                    'desc' => $bill->supplier_notes ?: $bill->memo,
                ];
            })
            ->filter(fn ($row) => $row['date'] && $row['date']->lte($cutoff))
            ->values()
            ->all();

        $obligations = TaxObligation::query()
            ->forCompany($companyId)
            ->active()
            ->with(['taxType:id,name,code', 'period:id,label,filing_due_date'])
            ->get()
            ->filter(fn (TaxObligation $o) => $o->period?->filing_due_date && $o->period->filing_due_date->lte($cutoff))
            ->sortBy(fn (TaxObligation $o) => $o->period->filing_due_date)
            ->take(3)
            ->values()
            ->all();

        $periodIds = array_unique(array_map(fn ($o) => (int) $o->period_id, $obligations));

        $returnsByPeriod = [];
        if ($periodIds) {
            $returns = TaxReturn::query()
                ->forCompany($companyId)
                ->whereIn('period_id', $periodIds)
                ->whereIn('status', [TaxObligation::STATUS_FILED, TaxObligation::STATUS_RETURN_APPROVED])
                ->orderByDesc('version')
                ->get(['id', 'period_id', 'net_payable', 'status']);

            foreach ($returns as $return) {
                if (! isset($returnsByPeriod[(int) $return->period_id])) {
                    $returnsByPeriod[(int) $return->period_id] = (float) $return->net_payable;
                }
            }
        }

        $taxes = [];
        foreach ($obligations as $obligation) {
            $due = $obligation->period->filing_due_date;
            $daysUntil = (int) $today->diffInDays($due, false);
            $overdue = $due->lt($today);
            $status = $obligation->status;

            [$statusText, $statusTone] = match (true) {
                in_array($status, [TaxObligation::STATUS_FILED, TaxObligation::STATUS_PAID, TaxObligation::STATUS_CLOSED], true) => ['Filed', 'ok'],
                in_array($status, [TaxObligation::STATUS_RETURN_APPROVED, TaxObligation::STATUS_RETURN_DRAFTED], true) => ['Return ready', 'wait'],
                $status === TaxObligation::STATUS_RECONCILED => ['Reconciled', 'wait'],
                $status === TaxObligation::STATUS_REJECTED => ['Rejected', 'bad'],
                default => ['Not filed', 'wait'],
            };

            $rows = [];
            $rows[] = ['l' => 'Period', 'v' => (string) ($obligation->period?->label ?: '—'), 'chip' => null];
            $rows[] = [
                'l' => 'Due date',
                'v' => $this->fmtDate($due, $dateFormat) ?: '—',
                'chip' => $overdue
                    ? ['tone' => 'over', 'text' => 'Overdue by ' . $this->dayWord(abs($daysUntil))]
                    : ($daysUntil <= 14 ? ['tone' => 'soon', 'text' => 'Due in ' . $this->dayWord($daysUntil)] : null),
            ];
            $rows[] = ['l' => 'Status', 'v' => '', 'chip' => ['tone' => $statusTone, 'text' => $statusText]];

            $code = (string) ($obligation->taxType?->code ?: 'TAX');

            $taxes[] = [
                'kind' => 'tax',
                'icon' => 'tax',
                'id' => $obligation->id,
                'ref' => $code,
                'name' => (string) ($obligation->taxType?->name ?: 'Tax obligation'),
                'period' => (string) ($obligation->period?->label ?: ''),
                'ref_line' => 'STATUTORY' . ($code !== 'TAX' ? ' · ' . $code : ''),
                'due' => $this->fmtDate($due, $dateFormat),
                'due_label' => $overdue ? 'Overdue' : 'Due ' . $due->format('j M'),
                'due_tone' => $overdue ? 'over' : ($daysUntil <= 14 ? 'soon' : null),
                'date' => $due,
                'date_ts' => $due->getTimestamp(),
                'amount' => (float) ($returnsByPeriod[(int) $obligation->period_id] ?? 0.0),
                'overdue' => $overdue,
                'overdue_days' => $overdue ? abs($daysUntil) : 0,
                'href' => route('accounting.taxation.dashboard'),
                'rows' => $rows,
                'desc' => null,
            ];
        }

        $items = collect($bills)
            ->merge($taxes)
            ->sortBy('date_ts')
            ->values()
            ->all();

        $overdueBills = (float) Bill::query()
            ->forCompany($companyId)
            ->whereIn('status', [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE])
            ->whereColumn('amount_paid', '<', 'amount')
            ->where(function ($q) use ($today) {
                $q->where('status', Bill::STATUS_OVERDUE)->orWhere('due_date', '<', $today);
            })
            ->selectRaw('SUM(amount - amount_paid) as overdue_balance')
            ->value('overdue_balance');

        $overdueTaxes = (float) collect($taxes)->where('overdue', true)->sum('amount');

        return [
            'bills' => $bills,
            'taxes' => $taxes,
            'items' => $items,
            'count' => count($bills) + count($taxes),
            'overdue_total' => round((float) $overdueBills + $overdueTaxes, 2),
        ];
    }

    /**
     * @return array<int, array{label: string, due: string, overdue: bool, pending: bool, url: ?string}>
     */
    private function tasks(int $companyId, ?int $userId): array
    {
        $tasks = [];

        if ($userId !== null) {
            $todo = TodoTask::query()
                ->forCompany($companyId)
                ->forUser($userId)
                ->active()
                ->orderByDesc('deadline_date')
                ->take(3)
                ->get();

            foreach ($todo as $item) {
                $tasks[] = [
                    'label' => (string) $item->title,
                    'due' => $item->deadlineLabel(),
                    'overdue' => $item->isOverdue(),
                    'pending' => false,
                    'url' => route('todo.index'),
                ];
            }
        }

        $journals = JournalEntry::query()
            ->forCompany($companyId)
            ->pendingApproval()
            ->orderBy('date')
            ->take(3)
            ->get();

        foreach ($journals as $entry) {
            $tasks[] = [
                'label' => 'Approve journal ' . $entry->journal_number . ' · ' . number_format((float) $entry->total_debit, 0, '.', ','),
                'due' => $entry->date?->format('M j') ?? 'Pending',
                'overdue' => false,
                'pending' => true,
                'url' => route('accounting.journal-entries.show', $entry->id),
            ];
        }

        $bills = Bill::query()
            ->forCompany($companyId)
            ->where('status', Bill::STATUS_PENDING_APPROVAL)
            ->orderBy('bill_date')
            ->take(2)
            ->get();

        foreach ($bills as $bill) {
            $tasks[] = [
                'label' => 'Approve bill ' . ($bill->bill_number ?? $bill->internal_number) . ' · ' . number_format((float) $bill->amount, 0, '.', ','),
                'due' => $bill->bill_date?->format('M j') ?? 'Pending',
                'overdue' => false,
                'pending' => true,
                'url' => route('accounting.bills.show', $bill->id),
            ];
        }

        return $tasks;
    }

    /**
     * @return array<int, array{at: ?Carbon, kind: string, title: string, party: ?string, party_dir: ?string, party_label: ?string, ref: ?string, module: string, recorded_by: string, status: string, status_tone: string, memo: ?string, amount: float, amount_display: string, dir: ?string, tone: string, when: string, url: ?string, href: ?string, date_display: string, time_display: string, lines: array, desc: string}>
     */
    private function recentActivity(int $companyId, string $symbol, int $decimals, string $dateFormat): array
    {
        $events = new Collection();

        CustomerPayment::query()
            ->forCompany($companyId)
            ->with(['customer:id,name', 'journalEntry.lines.account'])
            ->whereHas('customer')
            ->orderByDesc('payment_date')
            ->take(6)
            ->get()
            ->each(static function (CustomerPayment $payment) use ($events) {
                $events->push([
                    'at' => $payment->payment_date,
                    'kind' => 'customer_payment',
                    'title' => 'Payment received',
                    'party' => $payment->customer?->name,
                    'party_dir' => 'in',
                    'ref' => $payment->payment_number,
                    'module' => 'Sales',
                    'by_id' => $payment->created_by,
                    'status' => 'Cleared',
                    'status_tone' => 'ok',
                    'memo' => $payment->memo,
                    'amount' => (float) $payment->amount,
                    'dir' => 'in',
                    'tone' => 'green',
                    'url' => route('accounting.customer-payments.show', $payment->id),
                    'journal' => $payment->journalEntry,
                ]);
            });

        VendorPayment::query()
            ->forCompany($companyId)
            ->with(['vendor:id,name', 'journalEntry.lines.account'])
            ->whereHas('vendor')
            ->orderByDesc('payment_date')
            ->take(4)
            ->get()
            ->each(static function (VendorPayment $payment) use ($events) {
                $events->push([
                    'at' => $payment->payment_date,
                    'kind' => 'vendor_payment',
                    'title' => 'Payment posted',
                    'party' => $payment->vendor?->name,
                    'party_dir' => 'out',
                    'ref' => $payment->payment_number,
                    'module' => 'Purchasing',
                    'by_id' => $payment->created_by,
                    'status' => 'Posted',
                    'status_tone' => 'ok',
                    'memo' => $payment->memo ?? $payment->reference,
                    'amount' => (float) $payment->amount,
                    'dir' => 'out',
                    'tone' => 'amber',
                    'url' => route('accounting.vendor-payments.show', $payment->id),
                    'journal' => $payment->journalEntry,
                ]);
            });

        Invoice::query()
            ->forCompany($companyId)
            ->with(['customer:id,name', 'journalEntry.lines.account'])
            ->where('status', '!=', Invoice::STATUS_DRAFT)
            ->orderByDesc('invoice_date')
            ->take(6)
            ->get()
            ->each(static function (Invoice $invoice) use ($events) {
                $events->push([
                    'at' => $invoice->invoice_date,
                    'kind' => 'invoice',
                    'title' => 'Invoice ' . ($invoice->invoice_date?->isPast() ? 'sent' : 'raised'),
                    'party' => $invoice->customer?->name,
                    'party_dir' => 'in',
                    'ref' => $invoice->invoice_number,
                    'module' => 'Sales',
                    'by_id' => $invoice->created_by,
                    'status' => 'Sent',
                    'status_tone' => 'ok',
                    'memo' => $invoice->memo ?? $invoice->reference,
                    'amount' => (float) $invoice->amount,
                    'dir' => 'in',
                    'tone' => 'teal',
                    'url' => route('accounting.invoices.show', $invoice->id),
                    'journal' => $invoice->journalEntry,
                ]);
            });

        Bill::query()
            ->forCompany($companyId)
            ->with(['vendor:id,name', 'journalEntry.lines.account'])
            ->where('status', '!=', Bill::STATUS_DRAFT)
            ->orderByDesc('bill_date')
            ->take(6)
            ->get()
            ->each(static function (Bill $bill) use ($events) {
                $events->push([
                    'at' => $bill->bill_date,
                    'kind' => 'bill',
                    'title' => 'Bill recorded',
                    'party' => $bill->vendor?->name,
                    'party_dir' => 'out',
                    'ref' => $bill->bill_number ?: $bill->internal_number,
                    'module' => 'Purchasing',
                    'by_id' => $bill->created_by,
                    'status' => 'Recorded',
                    'status_tone' => 'ok',
                    'memo' => $bill->supplier_notes ?: $bill->memo,
                    'amount' => (float) $bill->amount,
                    'dir' => 'out',
                    'tone' => 'amber',
                    'url' => route('accounting.bills.show', $bill->id),
                    'journal' => $bill->journalEntry,
                ]);
            });

        JournalEntry::query()
            ->forCompany($companyId)
            ->with('lines.account')
            ->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->orderByDesc('date')
            ->take(6)
            ->get()
            ->each(static function (JournalEntry $entry) use ($events) {
                $label = $entry->reference ?: $entry->memo ?: $entry->journal_number;
                if (! $label) {
                    $label = 'Journal entry ' . $entry->id;
                }
                $events->push([
                    'at' => $entry->date,
                    'kind' => 'journal',
                    'title' => 'Journal posted',
                    'party' => null,
                    'party_dir' => null,
                    'ref' => $label,
                    'module' => $entry->source_module
                        ? ucfirst(str_replace('_', ' ', (string) $entry->source_module))
                        : 'Accounting',
                    'by_id' => $entry->created_by,
                    'status' => 'Posted',
                    'status_tone' => 'ok',
                    'memo' => $entry->memo,
                    'amount' => (float) $entry->total_debit,
                    'dir' => null,
                    'tone' => 'teal',
                    'url' => route('accounting.journal-entries.show', $entry->id),
                    'journal' => $entry,
                ]);
            });

        $events = $events->sortByDesc(fn ($e) => $e['at'])->values()->take(8);

        $names = $this->userNameMap($events->pluck('by_id')->all());

        return $events->map(function (array $event) use ($names, $symbol, $decimals, $dateFormat) {
            /** @var Carbon|null $at */
            $at = $event['at'];

            $when = match (true) {
                $at === null => '—',
                $at->isToday() => 'Today · ' . $at->format('H:i'),
                $at->isYesterday() => 'Yesterday',
                default => $at->format('M j'),
            };

            $party = $event['party'];

            $event['when'] = $when;
            $event['href'] = $event['url'];
            $event['recorded_by'] = $names[$event['by_id']] ?? '—';
            $event['party_label'] = $event['party_dir'] === 'out'
                ? 'Payee'
                : ($event['party_dir'] === 'in' ? 'Payer' : null);
            $event['date_display'] = $at ? $at->format($dateFormat) : '—';
            $event['time_display'] = $at ? $at->format('H:i') : '';
            $event['amount_display'] = $this->money((float) $event['amount'], $symbol, $decimals);
            $event['lines'] = $this->journalLines($event['journal'] ?? null, $symbol, $decimals);

            $event['desc'] = $event['title']
                . ($party ? ' — ' . $party : '')
                . ($event['ref'] ? ' · ' . $event['ref'] : '');

            unset($event['journal'], $event['by_id']);

            return $event;
        })->all();
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int|string, string>
     */
    private function userNameMap(array $ids): array
    {
        $ids = array_values(array_unique(array_filter($ids, fn ($id) => $id !== null)));

        if (! $ids) {
            return [];
        }

        return User::query()->whereIn('id', $ids)->pluck('name', 'id')->all();
    }

    /**
     * @return array<int, array{side: string, code: string, name: string, amt: string}>
     */
    private function journalLines(?JournalEntry $entry, string $symbol, int $decimals): array
    {
        if (! $entry) {
            return [];
        }

        $lines = $entry->relationLoaded('lines') ? $entry->lines : $entry->lines()->with('account')->get();

        return $lines->map(function ($line) use ($symbol, $decimals) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $isDebit = $debit > 0;

            return [
                'side' => $isDebit ? 'DR' : 'CR',
                'code' => (string) ($line->account?->code ?? ''),
                'name' => (string) ($line->account?->name ?? ''),
                'amt' => $this->money($isDebit ? $debit : $credit, $symbol, $decimals),
            ];
        })->values()->all();
    }

    private function money(float $value, string $symbol, int $decimals): string
    {
        return ($value < 0 ? '-' : '') . $symbol . number_format(abs($value), $decimals, '.', ',');
    }

    private function fmtDate(?Carbon $date, string $format): ?string
    {
        return $date ? $date->format($format) : null;
    }

    private function dayWord(int $days): string
    {
        return $days . ' ' . ($days === 1 ? 'day' : 'days');
    }

    private function isEmptyCompany(int $companyId): bool
    {
        $any = JournalEntry::query()->forCompany($companyId)->whereIn('status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])->exists();
        $any = $any || Invoice::query()->forCompany($companyId)->where('status', '!=', Invoice::STATUS_DRAFT)->exists();
        $any = $any || Bill::query()->forCompany($companyId)->where('status', '!=', Bill::STATUS_DRAFT)->exists();
        $any = $any || CustomerPayment::query()->forCompany($companyId)->exists();
        $any = $any || Account::query()->forCompany($companyId)->where(fn ($q) => $q->where('is_bank_account', true)->orWhere('is_petty_cash', true))->exists();

        return ! $any;
    }

    private function openInvoicesQuery(int $companyId): \Illuminate\Database\Eloquent\Builder
    {
        return Invoice::query()
            ->forCompany($companyId)
            ->whereIn('status', [Invoice::STATUS_SENT, Invoice::STATUS_PARTIALLY_PAID, Invoice::STATUS_OVERDUE])
            ->whereColumn('amount_paid', '<', 'amount');
    }
}