<?php

namespace App\Services\Reporting;

use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

/**
 * STAGE 3 — PDF Engine
 *
 * Normalizes the data from all five financial report services
 * into the shared template contract consumed by pdf/financial-report.blade.php.
 *
 * §9 rules: no meta block; actual-year column headers; negatives grey
 * parentheses; red only for 90+ aging; one template for all five.
 */
class FiReportPdfService
{
    private int $companyId;

    public function __construct()
    {
        $this->companyId = Session::get('current_company_id');
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.1 — INCOME STATEMENT
    // ═══════════════════════════════════════════════════════════════════

    public function incomeStatement(array $params = []): array
    {
        $branchId    = $params['branch_id'] ?? null;
        $dateFrom    = $params['date_from'] ?? now()->startOfYear()->format('Y-m-d');
        $dateTo      = $params['date_to'] ?? now()->format('Y-m-d');
        $compareMode = $params['compare_mode'] ?? null;
        $costCenterId = $params['cost_center_id'] ?? null;

        $service = app(\App\Services\Reporting\IncomeStatementService::class);
        $data = $service->generate($this->companyId, $branchId, $dateFrom, $dateTo, $compareMode, $costCenterId);

        $prevFrom = FiReportContract::comparativePeriod($dateFrom, $dateTo)['date_from'];
        $prevTo   = FiReportContract::comparativePeriod($dateFrom, $dateTo)['date_to'];
        $curYear  = date('Y', strtotime($dateFrom));
        $prevYear = date('Y', strtotime($prevFrom));

        $hasCompare = !empty($data['comparison']);

        $columns = $hasCompare
            ? [$curYear, $prevYear, 'Variance', '%']
            : [$curYear];

        $sections = [];

        // ── REVENUE ──────────────────────────────────────────────
        $items = [];
        if (!empty($data['groups']['income'])) {
            foreach ($data['groups']['income'] as $subType => $accounts) {
                foreach ($accounts as $item) {
                    $cur = $item['net'] ?? 0;
                    $values = [$this->fmtParens($cur)];
                    if ($hasCompare) {
                        $prev = $item['comparison']['net'] ?? 0;
                        $var  = $cur - $prev;
                        $pct  = $prev != 0 ? round(($var / abs($prev)) * 100, 2) : null;
                        $values[] = $this->fmtParens($prev);
                        $values[] = $this->fmtParens($var);
                        $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
                    }
                    $items[] = [
                        'label'  => $item['account']->name ?? '',
                        'code'   => $item['account']->code ?? '',
                        'values' => $values,
                    ];
                }
            }
            // Total Revenue
            $cur = $data['total_income'] ?? 0;
            $values = [$this->fmtParens($cur)];
            if ($hasCompare) {
                $prev = $data['comparison']['total_income'] ?? 0;
                $var  = $cur - $prev;
                $pct  = $prev != 0 ? round(($var / abs($prev)) * 100, 2) : null;
                $values[] = $this->fmtParens($prev);
                $values[] = $this->fmtParens($var);
                $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
            }
            $items[] = [
                'label'      => 'Total Revenue',
                'values'     => $values,
                'isSubtotal' => true,
            ];
        }
        $sections[] = ['label' => 'Revenue', 'items' => $items];

        // ── COST OF SALES ────────────────────────────────────────
        $items = [];
        if (!empty($data['groups']['expense'])) {
            // COGS items (sub_type = cost_of_goods_sold)
            foreach ($data['groups']['expense'] as $subType => $accounts) {
                if ($subType !== 'cost_of_goods_sold') continue;
                foreach ($accounts as $item) {
                    $cur = $item['net'] ?? 0;
                    $values = [$this->fmtParens($cur)];
                    if ($hasCompare) {
                        $prev = $item['comparison']['net'] ?? 0;
                        $var  = $cur - $prev;
                        $pct  = $prev != 0 ? round(($var / abs($prev)) * 100, 2) : null;
                        $values[] = $this->fmtParens($prev);
                        $values[] = $this->fmtParens($var);
                        $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
                    }
                    $items[] = [
                        'label'  => $item['account']->name ?? '',
                        'code'   => $item['account']->code ?? '',
                        'values' => $values,
                    ];
                }
            }
            // Total COGS
            $cogs = 0;
            if (!empty($data['groups']['expense']['cost_of_goods_sold'])) {
                $cogs = array_sum(array_map(fn($i) => $i['net'] ?? 0, $data['groups']['expense']['cost_of_goods_sold']));
            }
            $values = [$this->fmtParens($cogs)];
            if ($hasCompare) {
                $prevCogs = 0;
                if (!empty($data['comparison']['groups']['expense']['cost_of_goods_sold'])) {
                    $prevCogs = array_sum(array_map(fn($i) => $i['comparison']['net'] ?? 0, $data['comparison']['groups']['expense']['cost_of_goods_sold']));
                }
                $var = $cogs - $prevCogs;
                $pct = $prevCogs != 0 ? round(($var / abs($prevCogs)) * 100, 2) : null;
                $values[] = $this->fmtParens($prevCogs);
                $values[] = $this->fmtParens($var);
                $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
            }
            $items[] = [
                'label'      => 'Total Cost of Sales',
                'values'     => $values,
                'isSubtotal' => true,
            ];
        }
        $sections[] = ['label' => 'Cost of Sales', 'items' => $items];

        // ── GROSS PROFIT ─────────────────────────────────────────
        $gp = ($data['total_income'] ?? 0) - ($this->getCogs($data));
        $values = [$this->fmtParens($gp)];
        if ($hasCompare) {
            $prevGp = ($data['comparison']['total_income'] ?? 0) - $this->getCogs($data, 'comparison');
            $var = $gp - $prevGp;
            $pct = $prevGp != 0 ? round(($var / abs($prevGp)) * 100, 2) : null;
            $values[] = $this->fmtParens($prevGp);
            $values[] = $this->fmtParens($var);
            $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
        }
        $sections[] = [
            'label' => 'Gross Profit',
            'items' => [['label' => 'Gross Profit', 'values' => $values, 'isSubtotal' => true]],
        ];

        // ── OPERATING EXPENSES ───────────────────────────────────
        $items = [];
        $opEx = 0;
        $prevOpEx = 0;
        if (!empty($data['groups']['expense'])) {
            foreach ($data['groups']['expense'] as $subType => $accounts) {
                if ($subType === 'cost_of_goods_sold') continue;
                foreach ($accounts as $item) {
                    $cur = abs($item['net'] ?? 0);
                    $opEx += $cur;
                    $values = [$this->fmtParens($cur)];
                    if ($hasCompare) {
                        $prev = abs($item['comparison']['net'] ?? 0);
                        $prevOpEx += $prev;
                        $var = $cur - $prev;
                        $pct = $prev != 0 ? round(($var / abs($prev)) * 100, 2) : null;
                        $values[] = $this->fmtParens($prev);
                        $values[] = $this->fmtParens($var);
                        $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
                    }
                    $items[] = [
                        'label'  => $item['account']->name ?? '',
                        'code'   => $item['account']->code ?? '',
                        'values' => $values,
                    ];
                }
            }
            $values = [$this->fmtParens($opEx)];
            if ($hasCompare) {
                $var = $opEx - $prevOpEx;
                $pct = $prevOpEx != 0 ? round(($var / abs($prevOpEx)) * 100, 2) : null;
                $values[] = $this->fmtParens($prevOpEx);
                $values[] = $this->fmtParens($var);
                $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
            }
            $items[] = [
                'label'      => 'Total Operating Expenses',
                'values'     => $values,
                'isSubtotal' => true,
            ];
        }
        $sections[] = ['label' => 'Operating Expenses', 'items' => $items];

        // ── NET PROFIT ───────────────────────────────────────────
        $net = $data['net_income'] ?? 0;
        $values = [$this->fmtParens($net)];
        if ($hasCompare) {
            $prevNet = $data['comparison']['net_income'] ?? 0;
            $var = $net - $prevNet;
            $pct = $prevNet != 0 ? round(($var / abs($prevNet)) * 100, 2) : null;
            $values[] = $this->fmtParens($prevNet);
            $values[] = $this->fmtParens($var);
            $values[] = $pct !== null ? number_format($pct, 2) . '%' : '—';
        }
        $sections[] = [
            'label' => null,
            'items' => [[
                'label'        => ($net >= 0 ? 'Net Profit' : 'Net Loss'),
                'values'       => $values,
                'isTotal'      => true,
                'isDoubleRule' => true,
            ]],
        ];

        return [
            'title'       => 'Income Statement',
            'periodLabel' => \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('d M Y'),
            'currency'    => $this->currencySymbol(),
            'columns'     => $columns,
            'sections'    => $sections,
            'totals'      => null,
            'balanceCheck' => null,
            'signOff'     => true,
        ];
    }

    private function getCogs(array $data, string $key = ''): float
    {
        $src = $key ? ($data[$key] ?? []) : $data;
        if (empty($src['groups']['expense']['cost_of_goods_sold'])) return 0;
        return array_sum(array_map(fn($i) => $i['net'] ?? 0, $src['groups']['expense']['cost_of_goods_sold']));
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.2 — STATEMENT OF FINANCIAL POSITION
    // ═══════════════════════════════════════════════════════════════════

    public function balanceSheet(array $params = []): array
    {
        $branchId    = $params['branch_id'] ?? null;
        $asOfDate    = $params['as_of_date'] ?? now()->format('Y-m-d');
        $costCenterId = $params['cost_center_id'] ?? null;

        $service = app(\App\Services\Reporting\BalanceSheetService::class);
        $data = $service->generate($this->companyId, $branchId, $asOfDate);

        $columns = ['Current', 'Previous'];

        $sections = [];

        $groupLabels = [
            'asset'     => 'Assets',
            'liability' => 'Liabilities',
            'equity'    => 'Equity',
        ];

        foreach (['asset', 'liability', 'equity'] as $groupKey) {
            $items = [];
            $groupTotal = 0;

            if (!empty($data['groups'][$groupKey])) {
                foreach ($data['groups'][$groupKey] as $subType => $accounts) {
                    // Sub-type heading
                    $items[] = [
                        'label'     => str_replace('_', ' ', ucfirst($subType)),
                        'values'    => array_fill(0, count($columns), ''),
                        'isSection' => true,
                    ];

                    foreach ($accounts as $item) {
                        $cur = $item['balance'] ?? 0;
                        $groupTotal += $cur;
                        $values = [$this->fmtParens($cur)];

                        if (isset($item['comparison'])) {
                            $prev = $item['comparison']['balance'] ?? 0;
                            $values[] = $this->fmtParens($prev);
                        } else {
                            $values[] = '—';
                        }

                        $items[] = [
                            'label'  => $item['account']->name ?? '',
                            'code'   => $item['account']->code ?? '',
                            'values' => $values,
                        ];
                    }
                }
            }

            // Group subtotal
            $items[] = [
                'label'      => 'Total ' . $groupLabels[$groupKey],
                'values'     => array_merge([$this->fmtParens($groupTotal)], isset($data['comparison']) ? [$this->fmtParens($groupTotal)] : []),
                'isSubtotal' => true,
            ];

            $sections[] = ['label' => $groupLabels[$groupKey], 'items' => $items];
        }

        // ── BALANCE CHECK ────────────────────────────────────────
        $ta = $data['total_assets'] ?? 0;
        $tl = $data['total_liabilities'] ?? 0;
        $te = $data['total_equity'] ?? 0;
        $balanced = FiReportContract::checkSfpBalance($ta, $tl, $te);

        $totalLabel = 'Total Liabilities & Equity';
        $totalVal = $tl + $te;
        $totalValues = [$this->fmtParens($totalVal)];
        if (isset($data['comparison'])) {
            $prevTa = ($data['comparison']['total_assets'] ?? 0);
            $totalValues[] = $this->fmtParens($prevTa);
        }
        $sections[] = [
            'label' => null,
            'items' => [[
                'label'        => $totalLabel,
                'values'       => $totalValues,
                'isTotal'      => true,
                'isDoubleRule' => true,
            ]],
        ];

        $cs = $this->currencySymbol();
        return [
            'title'       => 'Statement of Financial Position',
            'periodLabel' => 'As at ' . \Carbon\Carbon::parse($asOfDate)->format('d F Y'),
            'currency'    => $cs,
            'columns'     => $columns,
            'sections'    => $sections,
            'totals'      => null,
            'balanceCheck' => [
                'text'    => "Balances — Total Assets equal Total Liabilities plus Equity ({$cs}" . number_format($ta, 0) . ')',
                'balanced' => $balanced,
            ],
            'signOff'     => true,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.3 — CASH FLOW STATEMENT
    // ═══════════════════════════════════════════════════════════════════

    public function cashFlow(array $params = []): array
    {
        $branchId = $params['branch_id'] ?? null;
        $dateFrom = $params['date_from'] ?? now()->startOfYear()->format('Y-m-d');
        $dateTo   = $params['date_to'] ?? now()->format('Y-m-d');

        $service = app(\App\Services\Reporting\CashFlowStatementService::class);
        $data = $service->generate($this->companyId, $branchId, $dateFrom, $dateTo);

        $columns = ['Inflow', 'Outflow', 'Net'];

        $sections = [];

        $activityLabels = [
            'operating'  => 'Operating Activities',
            'investing'  => 'Investing Activities',
            'financing'  => 'Financing Activities',
        ];

        foreach (['operating', 'investing', 'financing'] as $act) {
            $items = [];
            $itemsData = $data['sections'][$act] ?? [];
            $inTotal = 0;
            $outTotal = 0;

            foreach ($itemsData as $item) {
                $change = $item['change'] ?? 0;
                $inflow  = $change > 0 ? $change : 0;
                $outflow = $change < 0 ? abs($change) : 0;
                $inTotal += $inflow;
                $outTotal += $outflow;

                $items[] = [
                    'label'  => $item['account']->name ?? ($item['label'] ?? ''),
                    'code'   => $item['account']->code ?? '',
                    'values' => [
                        $inflow > 0 ? number_format($inflow, 0) : '—',
                        $outflow > 0 ? '(' . number_format($outflow, 0) . ')' : '—',
                        $this->fmtParens($change),
                    ],
                ];
            }

            // Section subtotal
            $net = $data[Str::camel($act) . '_total'] ?? ($inTotal - $outTotal);
            $items[] = [
                'label'      => 'Net ' . $activityLabels[$act],
                'values'     => [
                    number_format($inTotal, 0),
                    '(' . number_format($outTotal, 0) . ')',
                    $this->fmtParens($net),
                ],
                'isSubtotal' => true,
            ];

            $sections[] = ['label' => $activityLabels[$act], 'items' => $items];
        }

        // ── NET CASH MOVEMENT ────────────────────────────────────
        $netChange = $data['net_change'] ?? 0;
        $beginning = $data['beginning_cash'] ?? 0;
        $ending   = $data['ending_cash'] ?? 0;

        $sections[] = [
            'label' => null,
            'items' => [
                [
                    'label'  => 'Net Cash Movement',
                    'values' => ['', '', $this->fmtParens($netChange)],
                    'isSubtotal' => true,
                ],
                [
                    'label'  => 'Opening Cash',
                    'values' => ['', '', $this->fmtParens($beginning)],
                    'isSubtotal' => true,
                ],
                [
                    'label'        => 'Closing Cash',
                    'values'       => ['', '', $this->fmtParens($ending)],
                    'isTotal'      => true,
                    'isDoubleRule' => true,
                ],
            ],
        ];

        // ── CF CLOSING CHECK ─────────────────────────────────────
        $closingCheck = FiReportContract::checkCfClosing($beginning, $netChange, $ending);

        return [
            'title'       => 'Cash Flow Statement',
            'periodLabel' => \Carbon\Carbon::parse($dateFrom)->format('d M Y') . ' – ' . \Carbon\Carbon::parse($dateTo)->format('d M Y'),
            'currency'    => $this->currencySymbol(),
            'columns'     => $columns,
            'sections'    => $sections,
            'totals'      => null,
            'balanceCheck' => $closingCheck ? null : [
                'text'    => "Closing Cash does not equal Opening plus Net Movement. Please verify.",
                'balanced' => false,
            ],
            'signOff'     => true,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.4 — A/R AGING
    // ═══════════════════════════════════════════════════════════════════

    public function arAging(array $params = []): array
    {
        $branchId = $params['branch_id'] ?? null;
        $asOfDate = $params['as_of_date'] ?? now()->format('Y-m-d');

        $result = app(\App\Services\Reporting\AgingReportService::class)
            ->arAging($this->companyId, $branchId, $asOfDate);

        $columns = ['Current', '1–30', '31–60', '61–90', '90+', 'Total'];

        $items = [];
        foreach ($result['customers'] ?? [] as $row) {
            $has90Plus = ($row['days_90_plus'] ?? 0) > 0;
            $items[] = [
                'label'  => $row['customer_name'] ?? '',
                'values' => [
                    $this->fmtK($row['current'] ?? 0),
                    $this->fmtK($row['days_1_30'] ?? 0),
                    $this->fmtK($row['days_31_60'] ?? 0),
                    $this->fmtK($row['days_61_90'] ?? 0),
                    $this->fmtK($row['days_90_plus'] ?? 0),
                    $this->fmtK($row['total'] ?? 0),
                ],
                'isNegRed' => $has90Plus,
            ];
        }

        // Total row
        $t = $result['totals'] ?? [];
        $totals = [
            'label'  => 'Total',
            'values' => [
                $this->fmtK($t['current'] ?? 0),
                $this->fmtK($t['days_1_30'] ?? 0),
                $this->fmtK($t['days_31_60'] ?? 0),
                $this->fmtK($t['days_61_90'] ?? 0),
                $this->fmtK($t['days_90_plus'] ?? 0),
                $this->fmtK($t['total'] ?? 0),
            ],
            'isTotal' => true,
        ];

        return [
            'title'       => 'Accounts Receivable Aging',
            'periodLabel' => 'As at ' . \Carbon\Carbon::parse($asOfDate)->format('d F Y') . " · {$this->currencySymbol()}",
            'currency'    => $this->currencySymbol(),
            'columns'     => $columns,
            'sections'    => [['label' => 'Summary by Customer', 'items' => $items]],
            'totals'      => $totals,
            'balanceCheck' => null,
            'signOff'     => true,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  §10.4 — A/P AGING
    // ═══════════════════════════════════════════════════════════════════

    public function apAging(array $params = []): array
    {
        $branchId = $params['branch_id'] ?? null;
        $asOfDate = $params['as_of_date'] ?? now()->format('Y-m-d');

        $result = app(\App\Services\Reporting\AgingReportService::class)
            ->apAging($this->companyId, $branchId, $asOfDate);

        $columns = ['Current', '1–30', '31–60', '61–90', '90+', 'Total'];

        $items = [];
        foreach ($result['vendors'] ?? [] as $row) {
            $has90Plus = ($row['days_90_plus'] ?? 0) > 0;
            $items[] = [
                'label'  => $row['vendor_name'] ?? '',
                'values' => [
                    $this->fmtK($row['current'] ?? 0),
                    $this->fmtK($row['days_1_30'] ?? 0),
                    $this->fmtK($row['days_31_60'] ?? 0),
                    $this->fmtK($row['days_61_90'] ?? 0),
                    $this->fmtK($row['days_90_plus'] ?? 0),
                    $this->fmtK($row['total'] ?? 0),
                ],
                'isNegRed' => $has90Plus,
            ];
        }

        $t = $result['totals'] ?? [];
        $totals = [
            'label'  => 'Total',
            'values' => [
                $this->fmtK($t['current'] ?? 0),
                $this->fmtK($t['days_1_30'] ?? 0),
                $this->fmtK($t['days_31_60'] ?? 0),
                $this->fmtK($t['days_61_90'] ?? 0),
                $this->fmtK($t['days_90_plus'] ?? 0),
                $this->fmtK($t['total'] ?? 0),
            ],
            'isTotal' => true,
        ];

        return [
            'title'       => 'Accounts Payable Aging',
            'periodLabel' => 'As at ' . \Carbon\Carbon::parse($asOfDate)->format('d F Y') . " · {$this->currencySymbol()}",
            'currency'    => $this->currencySymbol(),
            'columns'     => $columns,
            'sections'    => [['label' => 'Summary by Vendor', 'items' => $items]],
            'totals'      => $totals,
            'balanceCheck' => null,
            'signOff'     => true,
        ];
    }

    // ═══════════════════════════════════════════════════════════════════
    //  FORMATTING HELPERS
    // ═══════════════════════════════════════════════════════════════════

    /**
     * Format value in parentheses when negative (§9.3 — grey parentheses).
     */
    private function fmtParens(float $value): string
    {
        return FiReportContract::fmtParens($value);
    }

    /**
     * Format in K'000 for aging reports.
     */
    private function fmtK(float $value): string
    {
        $k = $value / 1000;
        if (abs($k) < 0.01) return '—';
        return $k >= 0
            ? number_format($k, 0)
            : '(' . number_format(abs($k), 0) . ')';
    }

    private function currencySymbol(): string
    {
        return \App\Models\SystemSetting::getValue(
            'currency', 'currency_symbol',
            $this->companyId, '$'
        );
    }
}
