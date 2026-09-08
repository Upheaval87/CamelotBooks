<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'branch';
            $title = 'Branch Profitability';
            $subtitle = 'Revenue, gross margin and net income per branch with shared-cost allocation drivers for ' . strtolower($period['label']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $rows = $data['branches'] ?? [];
            $totRev = array_sum(array_column($rows, 'revenue'));
            $totNet = array_sum(array_column($rows, 'net_income'));
            $totExp = array_sum(array_column($rows, 'total_expenses'));
            $overallMargin = $totRev > 0 ? ($totNet / $totRev) * 100 : 0;
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Branches</div><div class="v">{{ count($rows) }}</div></div>
            <div class="an-kpi"><div class="l">Total Revenue</div><div class="v">@money($totRev)</div></div>
            <div class="an-kpi"><div class="l">Total Net Income</div><div class="v {{ $totNet >= 0 ? 'up' : 'dn' }}">@money($totNet)</div></div>
            <div class="an-kpi"><div class="l">Net Margin</div><div class="v">{{ format_number($overallMargin, 1) }}%</div></div>
        </div>

        <div class="an-card">
            <h2 class="an-card-t">Branch Performance</h2>
            <div class="an-tbl-wrap">
                <table class="an-tbl">
                    <thead>
                        <tr>
                            <th>Branch</th>
                            <th class="r">Revenue</th>
                            <th class="r">COGS</th>
                            <th class="r">Gross Profit</th>
                            <th class="r">Gross Margin</th>
                            <th class="r">Payroll</th>
                            <th class="r">OPEX</th>
                            <th class="r">Total Expenses</th>
                            <th class="r">Net Income</th>
                            <th class="r">Net Margin</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                            <tr>
                                <td>{{ $row['branch_name'] ?? '' }}</td>
                                <td class="r">@money($row['revenue'] ?? 0)</td>
                                <td class="r">@money($row['cogs'] ?? 0)</td>
                                <td class="r">@money($row['gross_profit'] ?? 0)</td>
                                <td class="r">{{ isset($row['gross_margin']) && $row['gross_margin'] !== null ? format_number($row['gross_margin'], 1) . '%' : '—' }}</td>
                                <td class="r">@money($row['payroll'] ?? 0)</td>
                                <td class="r">@money($row['opex'] ?? 0)</td>
                                <td class="r">@money($row['total_expenses'] ?? 0)</td>
                                <td class="r {{ ($row['net_income'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($row['net_income'] ?? 0)</td>
                                <td class="r">{{ isset($row['net_margin']) && $row['net_margin'] !== null ? format_number($row['net_margin'], 1) . '%' : '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="10" class="c an-empty-cell">No branch performance data.</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <th>Total</th>
                            <th class="r">@money($totRev)</th>
                            <th class="r">@money(array_sum(array_column($rows, 'cogs')))</th>
                            <th class="r">@money(array_sum(array_column($rows, 'gross_profit')))</th>
                            <th class="r">—</th>
                            <th class="r">@money(array_sum(array_column($rows, 'payroll')))</th>
                            <th class="r">@money(array_sum(array_column($rows, 'opex')))</th>
                            <th class="r">@money($totExp)</th>
                            <th class="r {{ $totNet >= 0 ? 'up' : 'dn' }}">@money($totNet)</th>
                            <th class="r">{{ format_number($overallMargin, 1) }}%</th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @php $allocation = $allocationSettings['pools'] ?? []; @endphp
        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Shared-Cost Allocation</h2>
                    <p class="an-card-s">Pick the driver used to spread each shared pool across branches. Saving re-computes the allocation on the next page load.</p>
                </div>
            </div>
            @if (auth()->user()->can('bi.exec'))
                <form method="POST" action="{{ route('bi.allocation.save') }}" class="an-filters" style="align-items:flex-end;">
                    @csrf
                    @foreach ($allocation as $key => $pool)
                        <span class="an-flabel">{{ $pool['label'] ?? ucwords(str_replace('_', ' ', $key)) }}
                            <select name="allocation[{{ $key }}_driver]" class="an-input">
                                @foreach (($pool['drivers'] ?? []) as $driver)
                                    <option value="{{ $driver }}" @selected(($pool['driver'] ?? null) === $driver)>{{ ucwords(str_replace('_', ' ', $driver)) }}</option>
                                @endforeach
                            </select>
                        </span>
                    @endforeach
                    @if ($allocation)
                        <span class="an-flabel" style="flex-direction:row;align-items:center;gap:8px;">
                            <input type="hidden" name="allocation[has_floor_area]" value="0">
                            <input type="checkbox" name="allocation[has_floor_area]" value="1" @checked((bool) ($allocationSettings['use_floor_area_sqm'] ?? false))>
                            Use floor area (sqm)
                        </span>
                    @endif
                    <button class="an-btn an-btn-cta" type="submit">Save allocation</button>
                    <a href="{{ route('bi.branch') }}" class="an-btn an-btn-ghost">Reset</a>
                </form>
                <p class="an-note">Drivers: revenue_share = proportion of branch revenue; headcount = proportion of employees; floor_area = square metres in the branch profile.</p>
            @else
                <div class="an-muted-msg">Only users with BI execute permission can change cost-allocation drivers. Current drivers:
                    @foreach ($allocation as $key => $pool)
                        <strong>{{ ucwords(str_replace('_', ' ', $key)) }}</strong> = {{ $pool['driver'] ?? '—' }}@if (!$loop->last),@endif
                    @endforeach
                </div>
            @endif
        </div>
    </div>
</x-app-layout>