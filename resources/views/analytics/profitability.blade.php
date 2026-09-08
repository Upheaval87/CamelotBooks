<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'profitability';
            $title = 'Profitability';
            $subtitle = 'Margins and income-statement summary for ' . $period['label'] . ' (' . $period['from'] . ' to ' . $period['to'] . ').';
            $is = $data['income_statement'] ?? [];
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.profitability') }}" class="an-filters">
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
            <a href="{{ route('analytics.profitability') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $income = (float) ($is['total_income'] ?? 0);
            $expense = (float) ($is['total_expenses'] ?? 0);
            $net = (float) ($is['net_income'] ?? 0);
            $margin = $income != 0 ? ($net / $income) * 100 : 0;
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Total Revenue</div><div class="v">@money($income)</div></div>
            <div class="an-kpi"><div class="l">Total Expenses</div><div class="v">@money($expense)</div></div>
            <div class="an-kpi"><div class="l">Net Income</div><div class="v {{ $net >= 0 ? 'up' : 'dn' }}">@money($net)</div></div>
            <div class="an-kpi"><div class="l">Net Margin</div><div class="v {{ $margin >= 0 ? 'up' : 'dn' }}">{{ format_number($margin, 1) }}%</div></div>
        </div>

        <div class="an-grid an-grid--2">
            <div class="an-card">
                <h2 class="an-card-t">By Branch</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Branch</th>
                                <th class="r">Revenue</th>
                                <th class="r">Expenses</th>
                                <th class="r">Net</th>
                                <th class="r">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['by_branch'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['branch_name'] ?? 'Unclassified' }}</td>
                                    <td class="r">@money($row['revenue'] ?? 0)</td>
                                    <td class="r">@money($row['expenses'] ?? 0)</td>
                                    <td class="r {{ ($row['net_income'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($row['net_income'] ?? 0)</td>
                                    <td class="r">{{ ($row['margin_pct'] ?? null) !== null ? format_number($row['margin_pct'], 1) . '%' : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="c an-empty-cell">No branch data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">By Cost Center</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Cost Center</th>
                                <th class="r">Revenue</th>
                                <th class="r">Expenses</th>
                                <th class="r">Net</th>
                                <th class="r">Margin</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['by_cost_center'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['cost_center_name'] ?? 'Unclassified' }}</td>
                                    <td class="r">@money($row['revenue'] ?? 0)</td>
                                    <td class="r">@money($row['expenses'] ?? 0)</td>
                                    <td class="r {{ ($row['net_income'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($row['net_income'] ?? 0)</td>
                                    <td class="r">{{ ($row['margin_pct'] ?? null) !== null ? format_number($row['margin_pct'], 1) . '%' : 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="c an-empty-cell">No cost-center data</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Gross Margin by Account</h2>
                    <p class="an-card-s">Revenue and cost-of-goods-sold per revenue account (posted entries).</p>
                </div>
            </div>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th class="r">Revenue</th>
                            <th class="r">COGS</th>
                            <th class="r">Gross Margin</th>
                            <th class="r">GM %</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($data['gross_margin_by_account'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['account_code'] }} · {{ $row['account_name'] }}</td>
                                <td class="r">@money($row['revenue'] ?? 0)</td>
                                <td class="r">@money($row['cogs'] ?? 0)</td>
                                <td class="r up">@money($row['gross_margin'] ?? 0)</td>
                                <td class="r">{{ ($row['gross_margin_pct'] ?? null) !== null ? format_number($row['gross_margin_pct'], 1) . '%' : 'N/A' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="c an-empty-cell">No gross-margin data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">By Product</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th class="r">Qty Sold</th>
                            <th class="r">Avg Price</th>
                            <th class="r">Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse (($data['by_product'] ?? []) as $row)
                            <tr>
                                <td>{{ $row['product_name'] }} <span class="an-chip c-nt">{{ $row['sku'] }}</span></td>
                                <td class="r">{{ format_number($row['quantity_sold'] ?? 0, 0) }}</td>
                                <td class="r">@money($row['avg_price'] ?? 0)</td>
                                <td class="r">@money($row['revenue'] ?? 0)</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="c an-empty-cell">No product data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>