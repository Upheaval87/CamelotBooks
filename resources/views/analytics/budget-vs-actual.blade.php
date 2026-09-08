<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'budget-vs-actual';
            $title = 'Budget vs Actual';
            $subtitle = 'Budget utilisation by account for ' . $period['label'] . ' (' . $period['from'] . ' to ' . $period['to'] . ').';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.budget-vs-actual') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $pk => $lbl)
                        <option value="{{ $pk }}" @selected($period['key'] === $pk)>{{ $lbl }}</option>
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
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.budget-vs-actual') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $hasBudget = (bool) ($data['has_budget'] ?? false);
        @endphp

        @if (!$hasBudget)
            <div class="an-card">
                <div class="an-empty">No budget exists for the selected period. Create a budget before comparing actuals.</div>
            </div>
        @else
            @php
                $budgeted = (float) ($data['total_budgeted'] ?? 0);
                $actual = (float) ($data['total_actual'] ?? 0);
                $variance = (float) ($data['total_variance'] ?? 0);
                $util = (float) ($data['overall_utilization'] ?? 0);
            @endphp
            <div class="an-kpis an-kpis--4">
                <div class="an-kpi"><div class="l">Total Budgeted</div><div class="v">@money($budgeted)</div></div>
                <div class="an-kpi"><div class="l">Total Actual</div><div class="v">@money($actual)</div></div>
                <div class="an-kpi"><div class="l">Variance</div><div class="v {{ $variance >= 0 ? 'up' : 'dn' }}">@money($variance)</div></div>
                <div class="an-kpi"><div class="l">Overall Utilisation</div><div class="v {{ $util <= 100 ? 'up' : 'dn' }}">{{ format_number($util, 1) }}%</div></div>
            </div>

            <div class="an-card">
                <h2 class="an-card-t">Utilisation by Account</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Type</th>
                                <th class="r">Budgeted</th>
                                <th class="r">Actual</th>
                                <th class="r">Variance</th>
                                <th class="r">Utilisation</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse (($data['lines'] ?? []) as $line)
                                @php
                                    $lUtil = (float) ($line['var_pct'] ?? ($line['pct'] ?? 0));
                                @endphp
                                <tr>
                                    <td>{{ $line['account'] ?? $line['name'] }}</td>
                                    <td><span class="an-chip c-{{ ($line['type'] ?? '') === 'income' ? 'ok' : 'nt' }}">{{ str($line['type'] ?? 'expense')->title() }}</span></td>
                                    <td class="r">@money($line['budgeted'] ?? 0)</td>
                                    <td class="r">@money($line['actual'] ?? 0)</td>
                                    <td class="r {{ ($line['variance'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($line['variance'] ?? 0)</td>
                                    <td class="r {{ $lUtil <= 100 ? 'up' : 'dn' }}">{{ format_number($lUtil, 1) }}%</td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="c an-empty-cell">No budget lines for the selected period.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>