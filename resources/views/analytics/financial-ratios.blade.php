<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'financial-ratios';
            $title = 'Financial Ratios';
            $subtitle = 'Key ratios computed from the balance sheet and income statement as at ' . $period['as_of'] . '.';

            $RATIO_META = [
                'liquidity' => [
                    'name' => 'Liquidity', 'icon' => '&#128737;',
                    'rows' => [
                        'current_ratio' => ['label' => 'Current Ratio', 'unit' => 'x', 'money' => false],
                        'quick_ratio' => ['label' => 'Quick Ratio', 'unit' => 'x', 'money' => false],
                        'working_capital' => ['label' => 'Working Capital', 'unit' => '', 'money' => true],
                    ],
                ],
                'profitability' => [
                    'name' => 'Profitability', 'icon' => '&#10003;',
                    'rows' => [
                        'gross_margin' => ['label' => 'Gross Margin', 'unit' => '%', 'money' => false],
                        'net_margin' => ['label' => 'Net Margin', 'unit' => '%', 'money' => false],
                        'roa' => ['label' => 'Return on Assets', 'unit' => '%', 'money' => false],
                        'roe' => ['label' => 'Return on Equity', 'unit' => '%', 'money' => false],
                    ],
                ],
                'efficiency' => [
                    'name' => 'Efficiency', 'icon' => '&#9881;',
                    'rows' => [
                        'ar_turnover' => ['label' => 'AR Turnover', 'unit' => 'x', 'money' => false],
                        'dso' => ['label' => 'Days Sales Outstanding', 'unit' => 'days', 'money' => false],
                        'ap_turnover' => ['label' => 'AP Turnover', 'unit' => 'x', 'money' => false],
                        'dpo' => ['label' => 'Days Payable Outstanding', 'unit' => 'days', 'money' => false],
                        'inventory_turnover' => ['label' => 'Inventory Turnover', 'unit' => 'x', 'money' => false],
                        'dio' => ['label' => 'Days Inventory Outstanding', 'unit' => 'days', 'money' => false],
                        'cash_conversion_cycle' => ['label' => 'Cash Conversion Cycle', 'unit' => 'days', 'money' => false],
                    ],
                ],
                'leverage' => [
                    'name' => 'Leverage', 'icon' => '&#9878;',
                    'rows' => [
                        'debt_to_equity' => ['label' => 'Debt to Equity', 'unit' => 'x', 'money' => false],
                        'debt_to_assets' => ['label' => 'Debt to Assets', 'unit' => 'x', 'money' => false],
                    ],
                ],
            ];
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <div class="an-kpis an-kpis--4">
            @foreach ([
                'total_assets' => ['label' => 'Total Assets'],
                'current_assets' => ['label' => 'Current Assets'],
                'current_liabilities' => ['label' => 'Current Liabilities'],
                'net_income' => ['label' => 'Net Income (FYTD)'],
            ] as $k => $spec)
                <div class="an-kpi">
                    <div class="l">{{ $spec['label'] }}</div>
                    <div class="v">@money($data['summary'][$k] ?? 0)</div>
                </div>
            @endforeach
        </div>

        <div class="an-grid an-grid--2">
            @foreach ($RATIO_META as $group => $meta)
                <div class="an-card">
                    <h2 class="an-card-t">{!! $meta['icon'] !!} {{ $meta['name'] }}</h2>
                    <div class="an-tbl-wrap">
                        <table class="an-tbl">
                            <thead>
                                <tr>
                                    <th>Ratio</th>
                                    <th class="r">Value</th>
                                    <th class="r">Target</th>
                                    <th class="r">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($meta['rows'] as $key => $row)
                                    @php
                                        $r = $data['ratios'][$group][$key] ?? null;
                                        $value = $r['value'] ?? null;
                                        $target = $r['target'] ?? null;
                                        $status = (is_numeric($value) && is_numeric($target) && (float) $target != 0)
                                            ? (abs((float) $value - (float) $target) / abs((float) $target) <= 0.2 ? 'ok' : 'warn')
                                            : null;
                                    @endphp
                                    <tr>
                                        <td>{{ $row['label'] }}</td>
                                        <td class="r">
                                            @if ($value === null)
                                                &mdash;
                                            @elseif ($row['money'])
                                                @money($value)
                                            @elseif ($row['unit'] === '%')
                                                {{ format_number($value * 100, 1) }}%
                                            @elseif ($row['unit'] === 'days' || $row['unit'] === 'x')
                                                {{ format_number($value, 2) }} {{ $row['unit'] }}
                                            @else
                                                {{ format_number($value, 2) }}
                                            @endif
                                        </td>
                                        <td class="r">
                                            @if ($target === null || $target === '')
                                                &mdash;
                                            @elseif ($row['money'])
                                                @money($target)
                                            @elseif ($row['unit'] === '%')
                                                {{ format_number($target * 100, 1) }}%
                                            @else
                                                {{ format_number($target, 2) }} {{ $row['unit'] }}
                                            @endif
                                        </td>
                                        <td class="r">
                                            @if ($status === 'ok')
                                                <span class="an-chip c-ok">On target</span>
                                            @elseif ($status === 'warn')
                                                <span class="an-chip c-warn">Off target</span>
                                            @else
                                                <span class="an-chip c-nt">n/a</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="c an-empty-cell">No ratio data.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="an-note">Ratios use balances as at {{ $period['as_of'] }} and fiscal-year-to-date income statement values. Targets are read from Settings &rarr; Accounting &rarr; Ratio Targets where configured.</div>
    </div>
</x-app-layout>