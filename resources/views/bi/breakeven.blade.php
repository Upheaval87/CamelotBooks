<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'breakeven';
            $title = 'Break-Even Analysis';
            $subtitle = 'Contribution margin and the revenue / unit the business must clear to cover fixed costs for ' . strtolower($period['label']) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $classified = $data['classified'] ?? [];
            $savedClasses = $expenseClassSaved ?? collect();
            $listedIds = collect($classified)->pluck('account_id')->map(fn ($v) => (int) $v)->all();
            if (!empty($expenseAccounts)) {
                $classified = collect($classified)->keyBy('account_id');
                foreach ($expenseAccounts as $ea) {
                    if (in_array((int) $ea->id, $listedIds, true)) {
                        continue;
                    }
                    $classified[] = [
                        'account_id' => $ea->id,
                        'account_name' => $ea->name,
                        'account_code' => $ea->code,
                        'amount' => 0,
                        'classification' => ($savedClasses[(int) $ea->id] ?? 'variable'),
                    ];
                }
                $classified = $classified->values()->all();
            }
            $hasClassifications = count($classified) > 0;
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Revenue</div><div class="v">@money($data['revenue'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Fixed Cost</div><div class="v">@money($data['fixed_cost'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Variable Cost</div><div class="v">@money($data['variable_cost'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Contribution</div><div class="v">@money($data['contribution'] ?? 0)</div></div>
        </div>
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Contribution Margin</div><div class="v">{{ format_number($data['cm_ratio_pct'] ?? 0, 1) }}%</div></div>
            <div class="an-kpi"><div class="l">Break-Even Revenue</div><div class="v">@money($data['break_even_revenue'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Break-Even Units</div><div class="v">{{ number_format((float) ($data['break_even_units'] ?? 0), 0) }}</div></div>
            <div class="an-kpi"><div class="l">Contribution / Unit</div><div class="v">@money($data['avg_contribution_per_unit'] ?? 0)</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Expense Classification</h2>
                    <p class="an-card-s">Override the fixed / variable tag per account; keyword heuristics apply until a save.</p>
                </div>
            </div>
            @php
                $hasClassifications = count($classified) > 0;
            @endphp
            @if ($hasClassifications)
                <form method="POST" action="{{ route('bi.expense-class.save') }}">
                    @csrf
                    <div class="an-tbl-wrap">
                        <table class="an-tbl">
                            <thead>
                                <tr>
                                    <th>Account</th>
                                    <th class="r">Amount</th>
                                    <th class="c">Classification</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($classified as $row)
                                    @php
                                        $curClass = strtolower(strval($row['classification'] ?? 'variable'));
                                        $isFixed = in_array($curClass, ['fixed', 'f']);
                                    @endphp
                                    <tr>
                                        <td>{{ $row['account_name'] ?? '' }} <span class="nt">({{ $row['account_code'] ?? '' }})</span></td>
                                        <td class="r">@money($row['amount'] ?? 0)</td>
                                        <td class="c">
                                            @can('bi.exec')
                                                <select name="classifications[{{ $row['account_id'] }}]" class="an-input">
                                                    <option value="variable" @selected(!$isFixed)>Variable</option>
                                                    <option value="fixed" @selected($isFixed)>Fixed</option>
                                                </select>
                                            @else
                                                <span class="an-chip {{ $isFixed ? 'c-ct' : 'c-nt' }}">{{ $isFixed ? 'Fixed' : 'Variable' }}</span>
                                            @endcan
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @can('bi.exec')
                        <div class="an-card-h" style="margin-top:14px;justify-content:flex-end;">
                            <button class="an-btn an-btn-cta" type="submit">Save classifications</button>
                        </div>
                    @endcan
                </form>
            @else
                <div class="an-empty">No expense accounts were found for classification.</div>
            @endif
        </div>
        <p class="an-note">Break-even revenue = fixed cost ÷ contribution margin ratio. Requires a positive contribution margin.</p>
    </div>
</x-app-layout>