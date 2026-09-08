<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'variance';
            $title = 'Budget vs Actual';
            $subtitle = 'Variance between budgeted and actual account activity for ' . strtolower($period['label']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters', ['withCostCenter' => true])

        @php
            $rows = $data['rows'] ?? [];
        @endphp

        @if ($data['no_budget'] ?? false)
            <div class="an-card">
                <div class="an-empty">
                    No budget has been set up for this company in the selected period. Create a budget under Budgeting, or pick a period that has one.
                </div>
            </div>
        @else
            <div class="an-kpis an-kpis--4">
                <div class="an-kpi"><div class="l">Budget Total</div><div class="v">@money($data['budget_total'] ?? 0)</div></div>
                <div class="an-kpi"><div class="l">Actual Total</div><div class="v">@money($data['actual_total'] ?? 0)</div></div>
                <div class="an-kpi"><div class="l">Variance</div><div class="v {{ ($data['variance_total'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['variance_total'] ?? 0)</div></div>
                <div class="an-kpi"><div class="l">Accounts on Budget</div><div class="v">{{ count($rows) }}</div></div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Account Variance</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="r">Budgeted</th>
                                <th class="r">Actual</th>
                                <th class="r">Variance</th>
                                <th class="r">Variance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rows as $row)
                                <tr>
                                    <td>{{ $row['account_name'] ?? '' }} <span class="nt">({{ $row['account_code'] ?? '' }})</span><div class="nt">{{ $row['budget_name'] ?? '' }}</div></td>
                                    <td class="r">@money($row['budgeted'] ?? 0)</td>
                                    <td class="r">@money($row['actual'] ?? 0)</td>
                                    <td class="r {{ ($row['variance'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($row['variance'] ?? 0)</td>
                                    <td class="r">{{ $row['variance_pct'] !== null ? format_number($row['variance_pct'], 1) . '%' : '—' }}</td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="c an-empty-cell">No budget lines in the selected period.</td></tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <th>Total</th>
                                <th class="r">@money($data['budget_total'] ?? 0)</th>
                                <th class="r">@money($data['actual_total'] ?? 0)</th>
                                <th class="r {{ ($data['variance_total'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['variance_total'] ?? 0)</th>
                                <th class="r">—</th>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
            <p class="an-note">Negative variance means actuals overran the budget; positive means spend came in under budget.</p>
        @endif
    </div>
</x-app-layout>