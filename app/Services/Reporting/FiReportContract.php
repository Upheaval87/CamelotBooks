<?php

namespace App\Services\Reporting;

use App\Models\{Account, Branch, Company, CostCenter,
    JournalEntry, JournalEntryLine, ExchangeRate,
    AccountingPeriod, Currency};
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * FINANCIAL REPORTS — SHARED DATA CONTRACT (Stage 1)
 *
 * Every report (IS / SFP / CF / AR Aging / AP Aging) and the PDF engine
 * call ONLY these functions for shared data. No report independently
 * re-derives account classification, fiscal periods, or comparative-period
 * math. Report-specific formatting stays in the controller/view.
 *
 * ──────────────────────────────────────────────────────────────────────
 * §0.6  POSTED-DATA SCOPE
 *
 *   IS / SFP / CF (GL-backed reports):
 *     JournalEntry.status IN ('posted', 'reversed')
 *     This is the same scope used by IncomeStatementService,
 *     BalanceSheetService, and CashFlowStatementService today.
 *     Draft / pending_approval / approved entries are excluded.
 *     "Reversed" entries are included because they represent the
 *     REVERSE side of a prior period posting — excluding them would
 *     understate both the source and the reversal, breaking IS/SFP
 *     balance integrity.
 *
 *   AR Aging (subledger):
 *     Invoice.status IN ('posted', 'partially_paid')
 *     Uses the AR subledger, not GL lines. A "posted" invoice has
 *     been sent to the customer; "partially_paid" is still outstanding.
 *     Draft / void / overdue-only invoices are excluded.
 *
 *   AP Aging (subledger):
 *     Bill.status IN ('posted', 'partially_paid', 'approved')
 *     Uses the AP subledger. "approved" bills are awaiting payment.
 *     Draft / void bills are excluded.
 *
 *   All five reports are MUTUALLY CONSISTENT as-of the same cut-off.
 * ──────────────────────────────────────────────────────────────────────
 *
 * §0.5  SHARED DATA CONTRACT
 *
 *   (a) accountBalanceAsOf() — single account balance as-of a date
 *   (b) batchAccountBalances() — N accounts, 1 query
 *   (c) periodActivity() — income/expense activity in a date range
 *   (d) resolvePeriod() / comparativePeriod() — fiscal-period resolution
 *   (e) fxRate() — FX translation (§10.8)
 *   (f) arAgingData() / apAgingData() — aging buckets (§10.4)
 *   (g) incomeStatementData() / sfpData() / cashFlowData() — full report data
 *
 * ──────────────────────────────────────────────────────────────────────
 * §0.1  DEPARTMENT FILTER
 *
 *   The codebase has NO department_id column on any table (accounts,
 *   journal_entry_lines, journal_entries). The spec's "Department" filter
 *   therefore maps to cost_center_id as the closest available dimension.
 *   The filter label reads "Department / Cost Centre" in the UI.
 *
 * §10.9  "ALL" BRANCH / DEPARTMENT AGGREGATION
 *
 *   GL accounts are company-scoped; branches hold SEPARATE journal_entry_lines
 *   per account (same account_id, different branch_id). When filter = "All",
 *   naive SUM across branch_id is CORRECT — no inter-branch elimination needed.
 *   Shared (null-branch) lines are included when "All" is selected.
 */
class FiReportContract
{
    // ─── CONSTANTS ────────────────────────────────────────────────────

    /**
     * GL-backed report statuses (IS / SFP / CF).
     */
    const GL_REPORTABLE_STATUSES = [
        JournalEntry::STATUS_POSTED,
        JournalEntry::STATUS_REVERSED,
    ];

    // Account sub-type → section mapping for SFP
    const SFP_SECTIONS = [
        'asset'     => ['fixed_asset', 'non_current_asset', 'current_asset'],
        'liability' => ['long_term_liability', 'current_liability'],
        'equity'    => ['equity'],
    ];

    // Account sub-type → section mapping for IS
    const IS_SECTIONS = [
        'income'  => ['revenue', 'other_income'],
        'expense' => ['cost_of_goods_sold', 'operating_expense', 'non_operating_expense', 'other_expense'],
    ];

    // Aging buckets
    const AGING_BUCKETS = ['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_90_plus'];

    // ─── 1. PERIOD RESOLUTION ─────────────────────────────────────────

    /**
     * Resolve fiscal period date range from a period label or explicit dates.
     *
     * @return array{date_from: string, date_to: string, label: string}
     */
    public static function resolvePeriod(int $companyId, string $dateFrom, ?string $dateTo = null): array
    {
        if ($dateTo && preg_match('/^\d{4}-\d{2}-\d{2}$/', $dateFrom)) {
            return [
                'date_from' => $dateFrom,
                'date_to'   => $dateTo,
                'label'     => Carbon::parse($dateFrom)->format('d M Y') . ' – '
                             . Carbon::parse($dateTo)->format('d M Y'),
            ];
        }

        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('label', $dateFrom)
            ->first();

        if ($period) {
            return [
                'date_from' => $period->start_date->format('Y-m-d'),
                'date_to'   => $period->end_date->format('Y-m-d'),
                'label'     => $period->label,
            ];
        }

        return [
            'date_from' => $dateFrom,
            'date_to'   => $dateTo ?? now()->format('Y-m-d'),
            'label'     => Carbon::parse($dateFrom)->format('d M Y') . ' – '
                         . Carbon::parse($dateTo ?? now())->format('d M Y'),
        ];
    }

    /**
     * §10.7 — Like-for-like comparative period: same range one year earlier.
     */
    public static function comparativePeriod(string $dateFrom, string $dateTo): array
    {
        $prevFrom = Carbon::parse($dateFrom)->subYear()->format('Y-m-d');
        $prevTo   = Carbon::parse($dateTo)->subYear()->format('Y-m-d');

        return [
            'date_from' => $prevFrom,
            'date_to'   => $prevTo,
            'label'     => Carbon::parse($prevFrom)->format('d M Y') . ' – '
                         . Carbon::parse($prevTo)->format('d M Y'),
        ];
    }

    /**
     * Fiscal year labels for PDF year headers (§9.1).
     */
    public static function fiscalYearLabels(string $dateFrom, string $dateTo): array
    {
        return [
            'current'  => (string) Carbon::parse($dateTo)->year,
            'previous' => (string) (Carbon::parse($dateTo)->year - 1),
        ];
    }

    /**
     * Get the fiscal year start date for a given date.
     */
    public static function getFiscalYearStart(int $companyId, string $asOfDate): string
    {
        $period = AccountingPeriod::where('company_id', $companyId)
            ->where('start_date', '<=', $asOfDate)
            ->where('end_date', '>=', $asOfDate)
            ->first();

        if ($period) {
            return $period->start_date->format('Y-m-d');
        }

        $company = Company::find($companyId);
        $startMonth = $company->fiscal_year_start_month ?? 1;
        $date = Carbon::parse($asOfDate);

        if ($date->month >= $startMonth) {
            return $date->copy()->startOfYear()->addMonths($startMonth - 1)->format('Y-m-d');
        }

        return $date->copy()->subYear()->startOfYear()->addMonths($startMonth - 1)->format('Y-m-d');
    }

    // ─── 2. GL BALANCE FUNCTIONS (§0.5 a/b) ──────────────────────────

    /**
     * §0.5(a) — Single account balance as-of a date.
     *
     * Balance = opening_balance + Σ(lines, signed by normal balance direction).
     * Lines scoped to GL_REPORTABLE_STATUSES only (§0.6).
     */
    public static function accountBalanceAsOf(
        int $accountId,
        int $companyId,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): float {
        $account = Account::findOrFail($accountId);

        $lineQuery = JournalEntryLine::where('account_id', $accountId)
            ->whereHas('journalEntry', function ($q) use ($companyId, $dateTo) {
                $q->where('company_id', $companyId)
                  ->whereIn('status', self::GL_REPORTABLE_STATUSES)
                  ->where('date', '<=', $dateTo);
            });

        if ($branchId) {
            $lineQuery->where('branch_id', $branchId);
        }
        if ($costCenterId) {
            $lineQuery->where('cost_center_id', $costCenterId);
        }

        $totalDebit  = (float) $lineQuery->sum('debit');
        $totalCredit = (float) $lineQuery->sum('credit');
        $opening     = (float) $account->opening_balance;

        return $account->isDebitNormal()
            ? $opening + $totalDebit - $totalCredit
            : $opening + $totalCredit - $totalDebit;
    }

    /**
     * §0.5(b) — Batch account balances: N accounts, single grouped query.
     *
     * @return array<int, float>  accountId → balance as-of dateTo
     */
    public static function batchAccountBalances(
        array $accountIds,
        int $companyId,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        if (empty($accountIds)) return [];

        $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');

        $lineQuery = JournalEntryLine::whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function ($q) use ($companyId, $dateTo) {
                $q->where('company_id', $companyId)
                  ->whereIn('status', self::GL_REPORTABLE_STATUSES)
                  ->where('date', '<=', $dateTo);
            });

        if ($branchId) {
            $lineQuery->where('branch_id', $branchId);
        }
        if ($costCenterId) {
            $lineQuery->where('cost_center_id', $costCenterId);
        }

        $aggregated = $lineQuery->groupBy('account_id')
            ->selectRaw('account_id, COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
            ->get()
            ->keyBy('account_id');

        $balances = [];
        foreach ($accounts as $id => $account) {
            $debit   = (float) ($aggregated[$id]->total_debit ?? 0);
            $credit  = (float) ($aggregated[$id]->total_credit ?? 0);
            $opening = (float) $account->opening_balance;

            $balances[$id] = $account->isDebitNormal()
                ? $opening + $debit - $credit
                : $opening + $credit - $debit;
        }

        return $balances;
    }

    /**
     * Batch balances for TWO periods (comparative IS / SFP).
     *
     * @return array{current: array<int,float>, previous: array<int,float>}
     */
    public static function batchComparativeBalances(
        array $accountIds,
        int $companyId,
        string $dateTo1,
        string $dateTo2,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        return [
            'current'  => self::batchAccountBalances($accountIds, $companyId, $dateTo1, $branchId, $costCenterId),
            'previous' => self::batchAccountBalances($accountIds, $companyId, $dateTo2, $branchId, $costCenterId),
        ];
    }

    // ─── 3. PERIOD ACTIVITY (§0.5 c) ─────────────────────────────────

    /**
     * §0.5(c) — Net movement per income/expense account within a date range.
     *
     * Income (credit-normal): net = credit - debit
     * Expense (debit-normal): net = debit - credit
     *
     * @return array{total: array<int,float>, breakdown: array<int, array{debit:float,credit:float,net:float}>}
     */
    public static function periodActivity(
        array $accountIds,
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null
    ): array {
        if (empty($accountIds)) return ['total' => [], 'breakdown' => []];

        $accounts = Account::whereIn('id', $accountIds)->get()->keyBy('id');

        $lineQuery = JournalEntryLine::whereIn('account_id', $accountIds)
            ->whereHas('journalEntry', function ($q) use ($companyId, $dateFrom, $dateTo) {
                $q->where('company_id', $companyId)
                  ->whereIn('status', self::GL_REPORTABLE_STATUSES)
                  ->whereBetween('date', [$dateFrom, $dateTo]);
            });

        if ($branchId) {
            $lineQuery->where('branch_id', $branchId);
        }
        if ($costCenterId) {
            $lineQuery->where('cost_center_id', $costCenterId);
        }

        $aggregated = $lineQuery->groupBy('account_id')
            ->selectRaw('account_id, COALESCE(SUM(debit),0) as total_debit, COALESCE(SUM(credit),0) as total_credit')
            ->get()
            ->keyBy('account_id');

        $total = [];
        $breakdown = [];

        foreach ($accountIds as $id) {
            if (!isset($accounts[$id])) continue;

            $debit  = (float) ($aggregated[$id]->total_debit ?? 0);
            $credit = (float) ($aggregated[$id]->total_credit ?? 0);
            $net    = $accounts[$id]->isCreditNormal() ? ($credit - $debit) : ($debit - $credit);

            $total[$id] = $net;
            $breakdown[$id] = ['debit' => $debit, 'credit' => $credit, 'net' => $net];
        }

        return ['total' => $total, 'breakdown' => $breakdown];
    }

    // ─── 4. FULL REPORT DATA ──────────────────────────────────────────

    /**
     * Income Statement data. Delegates to IncomeStatementService + contract
     * comparatives. Returns a normalized array consumed by the IS view and
     * the PDF engine.
     *
     * @return array with sections, totals, prev_totals, margin, date_from/to, year_headers
     */
    public static function incomeStatementData(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null,
        ?int $costCenterId = null,
        bool $withComparison = true
    ): array {
        $service = app(IncomeStatementService::class);
        $current = $service->generate($companyId, $branchId, $dateFrom, $dateTo, null, $costCenterId);

        $prev = self::comparativePeriod($dateFrom, $dateTo);
        $previous = $withComparison
            ? $service->generate($companyId, $branchId, $prev['date_from'], $prev['date_to'], null, $costCenterId)
            : null;

        $totalRevenue   = $current['total_income'];
        $prevRevenue    = $previous['total_income'] ?? 0;
        $totalCogs      = 0;
        $prevCogs       = 0;
        $totalOpex      = $current['total_expenses'];
        $prevOpex       = $previous['total_expenses'] ?? 0;
        $grossProfit    = $totalRevenue - $totalCogs;
        $prevGp         = $prevRevenue - $prevCogs;
        $operatingProfit = $grossProfit - $totalOpex;
        $prevOpProfit   = $prevGp - $prevOpex;
        $netProfit      = $current['net_income'];
        $prevNetProfit  = $previous['net_income'] ?? 0;

        return [
            'raw' => $current,
            'prev_raw' => $previous,
            'totals' => [
                'revenue'          => $totalRevenue,
                'cogs'             => $totalCogs,
                'gross_profit'     => $grossProfit,
                'total_opex'       => $totalOpex,
                'operating_profit' => $operatingProfit,
                'net_profit'       => $netProfit,
            ],
            'prev_totals' => [
                'revenue'          => $prevRevenue,
                'cogs'             => $prevCogs,
                'gross_profit'     => $prevGp,
                'total_opex'       => $prevOpex,
                'operating_profit' => $prevOpProfit,
                'net_profit'       => $prevNetProfit,
            ],
            'margin' => [
                'gross'    => self::safePct($grossProfit, $totalRevenue),
                'prev_gross' => self::safePct($prevGp, $prevRevenue),
                'net'      => self::safePct($netProfit, $totalRevenue),
            ],
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'period_label' => Carbon::parse($dateFrom)->format('d M Y') . ' – ' . Carbon::parse($dateTo)->format('d M Y'),
            'prev_label'   => $prev['label'],
            'year_headers' => self::fiscalYearLabels($dateFrom, $dateTo),
        ];
    }

    /**
     * Statement of Financial Position data. Delegates to BalanceSheetService
     * for the current position; uses contract batchAccountBalances for the
     * comparative position.
     */
    public static function sfpData(
        int $companyId,
        string $asOfDate,
        ?int $branchId = null,
        ?int $costCenterId = null,
        bool $withComparison = true
    ): array {
        $service = app(BalanceSheetService::class);
        $current = $service->generate($companyId, $branchId, $asOfDate, $costCenterId);

        // Previous year position
        $prevDate = Carbon::parse($asOfDate)->subYear()->format('Y-m-d');
        $previous = $withComparison
            ? $service->generate($companyId, $branchId, $prevDate, $costCenterId)
            : null;

        // KPIs
        $totalAssets     = $current['total_assets'];
        $totalLiabilities = $current['total_liabilities'];
        $totalEquity     = $current['total_equity'];
        $prevAssets      = $previous['total_assets'] ?? 0;
        $prevLiabilities = $previous['total_liabilities'] ?? 0;
        $prevEquity      = $previous['total_equity'] ?? 0;

        // Current assets/liabilities for working capital KPI
        $ca = 0;
        $cl = 0;
        foreach ($current['groups']['asset']['current_asset'] ?? [] as $item) {
            $ca += $item['balance'];
        }
        foreach ($current['groups']['liability']['current_liability'] ?? [] as $item) {
            $cl += abs($item['balance']);
        }

        return [
            'raw' => $current,
            'prev_raw' => $previous,
            'totals' => [
                'total_assets'      => $totalAssets,
                'total_liabilities' => $totalLiabilities,
                'total_equity'      => $totalEquity,
                'total_le'          => $totalLiabilities + $totalEquity,
                'balanced'          => $current['balanced'],
                'prev_assets'       => $prevAssets,
                'prev_liabilities'  => $prevLiabilities,
                'prev_equity'       => $prevEquity,
                'prev_le'           => $prevLiabilities + $prevEquity,
            ],
            'kpi' => [
                'working_capital'   => $ca - $cl,
                'current_ratio'     => $cl != 0 ? round($ca / $cl, 2) : null,
                'debt_to_equity'    => $totalEquity != 0 ? round($totalLiabilities / $totalEquity, 2) : null,
                'equity_ratio'      => $totalAssets != 0 ? round($totalEquity / $totalAssets * 100, 2) : null,
                'net_assets'        => $totalAssets - $totalLiabilities,
            ],
            'as_of_date'   => $asOfDate,
            'year_headers' => self::fiscalYearLabels(Carbon::parse($asOfDate)->startOfYear()->format('Y-m-d'), $asOfDate),
        ];
    }

    /**
     * Cash Flow Statement data. Delegates directly to CashFlowStatementService.
     */
    public static function cashFlowData(
        int $companyId,
        string $dateFrom,
        string $dateTo,
        ?int $branchId = null
    ): array {
        $service = app(CashFlowStatementService::class);
        return $service->generate($companyId, $branchId, $dateFrom, $dateTo);
    }

    /**
     * §10.4 — AR aging data. Delegates to AgingReportService.
     *
     * @return array{customers: array, totals: array, as_of_date: string, detail: array}
     */
    public static function arAgingData(
        int $companyId,
        string $asOfDate,
        ?int $branchId = null
    ): array {
        $service = app(AgingReportService::class);
        $summary = $service->arAging($companyId, $branchId, $asOfDate);
        $detail  = $service->arAgingDetail($companyId, $branchId, $asOfDate);

        return array_merge($summary, ['detail' => $detail['customers'] ?? []]);
    }

    /**
     * §10.4 — AP aging data. Delegates to AgingReportService.
     *
     * @return array{vendors: array, totals: array, as_of_date: string, detail: array}
     */
    public static function apAgingData(
        int $companyId,
        string $asOfDate,
        ?int $branchId = null
    ): array {
        $service = app(AgingReportService::class);
        $summary = $service->apAging($companyId, $branchId, $asOfDate);
        $detail  = $service->apAgingDetail($companyId, $branchId, $asOfDate);

        return array_merge($summary, ['detail' => $detail['vendors'] ?? []]);
    }

    // ─── 5. FX TRANSLATION (§10.8) ───────────────────────────────────

    /**
     * §10.8 — FX exchange rate lookup.
     *
     *   SFP balances → closing rate (latest rate ≤ as_of_date)
     *   IS/CF movements → period-average rate (avg of rates in range)
     *
     * Returns 1.0 when base === report currency (no translation needed).
     */
    public static function fxRate(
        int $companyId,
        string $baseCurrency,
        string $reportCurrency,
        string $date,
        string $mode = 'closing',
        ?string $dateFrom = null
    ): float {
        if (strtoupper($baseCurrency) === strtoupper($reportCurrency)) {
            return 1.0;
        }

        if ($mode === 'average' && $dateFrom) {
            $avg = ExchangeRate::where('company_id', $companyId)
                ->where('currency_from', strtoupper($baseCurrency))
                ->where('currency_to', strtoupper($reportCurrency))
                ->whereBetween('effective_date', [$dateFrom, $date])
                ->avg('rate');

            return $avg > 0 ? (float) $avg : 1.0;
        }

        // Direct query avoids TenantScoped connection-resolution edge cases.
        $rate = ExchangeRate::where('company_id', $companyId)
            ->whereRaw('UPPER(currency_from) = ?', [strtoupper($baseCurrency)])
            ->whereRaw('UPPER(currency_to) = ?', [strtoupper($reportCurrency)])
            ->whereRaw('DATE(effective_date) <= ?', [$date])
            ->orderByDesc('effective_date')
            ->first();

        return $rate ? (float) $rate->rate : 1.0;
    }

    // ─── 6. FORMATTING HELPERS ────────────────────────────────────────

    /**
     * §10.6 — Safe percentage: when denominator ≈ 0, return null (UI renders "—").
     */
    public static function safePct(?float $numerator, ?float $denominator): ?float
    {
        if ($denominator === null || abs($denominator) < 0.01) {
            return null;
        }
        $pct = ($numerator / abs($denominator)) * 100;
        return is_finite($pct) ? round($pct, 2) : null;
    }

    /**
     * Format a number for display.
     */
    public static function fmt(?float $value): string
    {
        return format_number($value ?? 0);
    }

    /**
     * Format with grey parentheses for negatives (PDF §9.3).
     */
    public static function fmtParens(?float $value): string
    {
        $v = $value ?? 0;
        if ($v < -0.005) {
            return '(' . format_number(abs($v)) . ')';
        }
        return format_number($v);
    }

    /**
     * System currency symbol.
     */
    public static function systemCurrencySymbol(int $companyId): string
    {
        return \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $companyId, 'K');
    }

    // ─── 7. §10 FORMULA FUNCTIONS ──────────────────────────────────────
    //
    // Pure computation functions consumed by the web views and PDF engine.
    // All take raw numeric inputs and return computed results. No DB access.

    // ─── 7a. Income Statement formulas (§10.1) ───────────────────────

    /**
     * Gross Profit = Revenue − COGS.
     */
    public static function computeGp(float $revenue, float $cogs): float
    {
        return $revenue - $cogs;
    }

    /**
     * Gross Profit Margin = GP / Revenue. Returns null when revenue ≈ 0.
     */
    public static function computeGpMargin(float $revenue, float $cogs): ?float
    {
        return self::safePct($revenue - $cogs, $revenue) !== null
            ? round(($revenue - $cogs) / abs($revenue) * 100, 2)
            : null;
    }

    /**
     * Operating Profit = GP − OpEx.
     */
    public static function computeOperatingProfit(float $gp, float $opex): float
    {
        return $gp - $opex;
    }

    /**
     * Profit Before Tax = Operating Profit + Finance Income − Finance Costs.
     */
    public static function computePbt(float $operatingProfit, float $financeIncome, float $financeCosts): float
    {
        return $operatingProfit + $financeIncome - $financeCosts;
    }

    /**
     * Net Profit = PBT − Income Tax.
     */
    public static function computeNetProfit(float $pbt, float $tax): float
    {
        return $pbt - $tax;
    }

    /**
     * §10.1 — Variance = Current − Previous.
     */
    public static function computeVariance(float $current, float $previous): float
    {
        return $current - $previous;
    }

    /**
     * §10.6 — Variance % = (Current − Previous) / |Previous| × 100.
     * Returns null when Previous = 0 (displays "—" in UI, never Infinity).
     */
    public static function computeVariancePct(float $current, float $previous): ?float
    {
        return self::safePct($current - $previous, $previous);
    }

    // ─── 7b. SFP formulas (§10.2) ────────────────────────────────────

    /**
     * Working Capital = Current Assets − Current Liabilities.
     */
    public static function computeWorkingCapital(float $currentAssets, float $currentLiabilities): float
    {
        return $currentAssets - $currentLiabilities;
    }

    /**
     * Current Ratio = Current Assets / Current Liabilities.
     * Returns null when CL = 0.
     */
    public static function computeCurrentRatio(float $currentAssets, float $currentLiabilities): ?float
    {
        if (abs($currentLiabilities) < 0.01) {
            return null;
        }
        $ratio = $currentAssets / $currentLiabilities;
        return is_finite($ratio) ? round($ratio, 2) : null;
    }

    /**
     * Debt-to-Equity = Total Liabilities / Total Equity.
     * Returns null when Equity = 0.
     */
    public static function computeDebtToEquity(float $totalLiabilities, float $totalEquity): ?float
    {
        if (abs($totalEquity) < 0.01) {
            return null;
        }
        $ratio = $totalLiabilities / $totalEquity;
        return is_finite($ratio) ? round($ratio, 2) : null;
    }

    /**
     * Equity Ratio = Total Equity / Total Assets × 100.
     * Returns null when Assets = 0.
     */
    public static function computeEquityRatio(float $totalEquity, float $totalAssets): ?float
    {
        if (abs($totalAssets) < 0.01) {
            return null;
        }
        $ratio = ($totalEquity / $totalAssets) * 100;
        return is_finite($ratio) ? round($ratio, 2) : null;
    }

    /**
     * §5.5 / §10.2 — SFP balance check.
     * Returns true when |Assets − (Liabilities + Equity)| < tolerance.
     */
    public static function checkSfpBalance(
        float $totalAssets,
        float $totalLiabilities,
        float $totalEquity,
        float $tolerance = 0.01
    ): bool {
        return abs($totalAssets - ($totalLiabilities + $totalEquity)) < $tolerance;
    }

    // ─── 7c. Cash Flow formulas (§10.3) ──────────────────────────────

    /**
     * §10.3 — Net Cash Flow = Operating + Investing + Financing.
     */
    public static function computeCfNet(float $operating, float $investing, float $financing): float
    {
        return $operating + $investing + $financing;
    }

    /**
     * §6.3 / §10.3 — CF closing check.
     * Returns true when |closing − (opening + net)| < tolerance.
     */
    public static function checkCfClosing(
        float $openingCash,
        float $netChange,
        float $closingCash,
        float $tolerance = 0.01
    ): bool {
        return abs($closingCash - ($openingCash + $netChange)) < $tolerance;
    }

    // ─── 7d. Aging formulas (§10.4) ──────────────────────────────────

    /**
     * §10.4 — Classify a days-overdue value into an aging bucket.
     *
     * @return 'current'|'days_1_30'|'days_31_60'|'days_61_90'|'days_90_plus'
     */
    public static function bucketAgingDays(int $daysOverdue): string
    {
        if ($daysOverdue <= 0)  return 'current';
        if ($daysOverdue <= 30) return 'days_1_30';
        if ($daysOverdue <= 60) return 'days_31_60';
        if ($daysOverdue <= 90) return 'days_61_90';
        return 'days_90_plus';
    }

    /**
     * §10.4 — Verify aging bucket totals reconcile to outstanding total.
     */
    public static function reconcileAgingTotals(array $bucketTotals, float $outstandingTotal, float $tolerance = 0.01): bool
    {
        $sum = array_sum($bucketTotals);
        return abs($sum - $outstandingTotal) < $tolerance;
    }

    // ─── 7e. Cross-report consistency ─────────────────────────────────

    /**
     * §16.8 — Cross-report: IS net profit should equal SFP current-year earnings.
     */
    public static function checkIsNetEqualsSfpCype(float $isNetProfit, float $sfpCurrentYearEarnings, float $tolerance = 0.01): bool
    {
        return abs($isNetProfit - $sfpCurrentYearEarnings) < $tolerance;
    }

    // ─── 8. LOOKUP HELPERS ────────────────────────────────────────────

    public static function branches(int $companyId)
    {
        return Branch::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public static function costCenters(int $companyId)
    {
        return CostCenter::where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    public static function currencies()
    {
        return Currency::query()->active()->ordered()->get();
    }
}
