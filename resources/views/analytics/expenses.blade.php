<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'expenses';
            $title = 'Expenses';
            $subtitle = 'Spend mix, top accounts and monthly outflows for ' . $period['label'] . ' (' . $period['from'] . ' to ' . $period['to'] . ').';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.expenses') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $k => $lbl)
                        <option value="{{ $k }}" @selected($period['key'] === $k)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <span class="an-flabel">Branch
                <select name="branch_id" class="an-input">
                    <option value="">All branches</option>
                    @foreach ($branches as $b)
                        <option value="{{ $b->id }}" @selected((int) request('branch_id') === $b->id)>{{ $b->name }}</option>
                    @endforeach
                </select>
            </span>
            <span class="an-flabel">Cost Center
                <select name="cost_center_id" class="an-input">
                    <option value="">All cost centers</option>
                    @foreach ($costCenters as $cc)
                        <option value="{{ $cc->id }}" @selected((int) request('cost_center_id') === $cc->id)>{{ $cc->name }}</option>
                    @endforeach
                </select>
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.expenses') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $k = $data['kpis'] ?? [];
            $total = (float) ($data['total'] ?? 0);
            $prev = (float) ($k['total_expense']['prev'] ?? 0);
            $chg = $prev != 0 ? (($total - $prev) / abs($prev)) * 100 : 0;
            $mix = $data['mix'] ?? [];
            $mixTotal = (float) array_sum(array_map(fn ($m) => $m['value'] ?? 0, $mix));
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Expenses</div><div class="v">@money($total)</div></div>
            <div class="an-kpi"><div class="l">vs Previous Period</div><div class="v {{ $chg <= 0 ? 'up' : 'dn' }}">{{ ($chg >= 0 ? '+' : '') . format_number($chg, 1) }}%</div></div>
            <div class="an-kpi"><div class="l">Payroll</div><div class="v">@money($k['payroll']['value'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Payroll % of Spend</div><div class="v">{{ ($k['payroll_share']['value'] ?? 0) !== null ? format_number($k['payroll_share']['value'] ?? 0, 1) . '%' : 'N/A' }}</div></div>
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">Spend Mix</h2>
                @if ($mix)
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th class="r">Amount</th>
                                <th class="r">Share</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($mix as $label => $m)
                                <tr>
                                    <td>{{ $m['label'] ?? $label }}</td>
                                    <td class="r">@money($m['value'] ?? 0)</td>
                                    <td class="r">{{ ($m['pct'] ?? null) !== null ? format_number($m['pct'], 1) . '%' : 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr class="an-tfoot-row">
                                <th>Total</th>
                                <th class="r">@money($mixTotal)</th>
                                <th class="r">{{ $mixTotal > 0 ? '100.0%' : 'N/A' }}</th>
                            </tr>
                        </tfoot>
                    </table>
                @else
                    <div class="an-empty">No expense activity in this period.</div>
                @endif
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Top Expense Accounts</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="r">Total</th>
                                <th class="r">Budget</th>
                                <th class="c">vs Budget</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['top_accounts'] ?? []) as $row)
                                @php
                                    $budgeted = (float) ($row['budgeted'] ?? 0);
                                    $status = $row['status'] ?? null;
                                @endphp
                                <tr>
                                    <td>{{ $row['code'] }} · {{ $row['name'] }}</td>
                                    <td class="r">@money($row['total'] ?? 0)</td>
                                    <td class="r">{{ $budgeted > 0 || ($row['budgeted'] ?? null) !== null ? format_money($budgeted) : '—' }}</td>
                                    <td class="c">
                                        @if ($status === 'over')
                                            <span class="an-chip c-ov">Over budget</span>
                                        @elseif ($status === 'ok')
                                            <span class="an-chip c-ok">Within budget</span>
                                        @else
                                            <span class="an-chip c-nt">No budget</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="c an-empty-cell">No expense accounts posted</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Monthly Expense Trend</h2>
            @php
                $labels = $data['monthly_labels'] ?? [];
                $values = $data['monthly_data'] ?? [];
                $maxV = max(array_merge([1], array_map('abs', $values)));
            @endphp
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: @money($values[$i] ?? 0)">{{ format_number($values[$i] ?? 0, 0) }}</span>
                            <div class="an-bar-cols">
                                <i class="an-bar-b exp" style="height:{{ ((abs($values[$i] ?? 0)) / $maxV) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No monthly activity.</div>
            @endif
        </div>
    </div>
</x-app-layout>