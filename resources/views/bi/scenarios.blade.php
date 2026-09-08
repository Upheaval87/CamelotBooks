<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'scenarios';
            $title = 'Cash Scenarios';
            $subtitle = '13-week cash projection under Bull, Base and Bear assumptions, starting from opening cash ' . format_money($data['opening_cash'] ?? 0) . '.';
        @endphp
        @include('bi._nav')
        @include('bi._head')
        @include('bi._filters')

        @php
            $scenarios = $data['scenarios'] ?? [];
            $weekLabels = collect($scenarios)->flatMap(fn ($s) => array_map(fn ($w) => $w['label'], $s['weeks']))->unique()->values()->all();
            $weekCount = count($weekLabels);
        @endphp

        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Opening Cash</div><div class="v">@money($data['opening_cash'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Weekly Base Net Flow</div><div class="v">@money($data['weekly_base'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Weeks Projected</div><div class="v">{{ $weekCount }}</div></div>
            <div class="an-kpi"><div class="l">Horizon</div><div class="v">{{ isset($data['date_from'], $data['date_to']) ? $data['date_from'] . ' → ' . $data['date_to'] : '13 weeks' }}</div></div>
        </div>

        @foreach ($scenarios as $s)
            <div class="an-card">
                <div class="an-card-h">
                    <div>
                        <h2 class="an-card-t">{{ $s['label'] }}</h2>
                        <p class="an-card-s">Final projected balance: <strong class="an-delta {{ $s['final_balance'] >= 0 ? 'up' : 'dn' }}">@money($s['final_balance'])</strong></p>
                    </div>
                </div>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Week</th>
                                <th>Coalesce date</th>
                                <th class="r">Opening</th>
                                <th class="r">Inflow</th>
                                <th class="r">Outflow</th>
                                <th class="r">Net Flow</th>
                                <th class="r">Closing</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach (($s['weeks'] ?? []) as $w)
                                <tr>
                                    <td>WK {{ str_pad((string) $w['week'], 2, '0', STR_PAD_LEFT) }}</td>
                                    <td class="nt">{{ $w['label'] }}</td>
                                    <td class="r">@money($w['opening'])</td>
                                    <td class="r up">@money($w['inflow'])</td>
                                    <td class="r dn">@money($w['outflow'])</td>
                                    <td class="r {{ ($w['net_cash_flow'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($w['net_cash_flow'])</td>
                                    <td class="r {{ ($w['closing'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($w['closing'])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endforeach

        @if (count($scenarios) > 1)
            <div class="an-card">
                <h2 class="an-card-t">Scenario Comparison</h2>
                <div class="an-tbl-wrap">
                    <table class="an-tbl">
                        <thead>
                            <tr>
                                <th>Week</th>
                                @foreach ($scenarios as $s)
                                    <th class="r">{{ $s['label'] }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @for ($i = 0; $i < $weekCount; $i++)
                                <tr>
                                    <td class="nt">{{ $weekLabels[$i] }}</td>
                                    @foreach ($scenarios as $s)
                                        <td class="r {{ ($s['weeks'][$i]['closing'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($s['weeks'][$i]['closing'] ?? 0)</td>
                                    @endforeach
                                </tr>
                            @endfor
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @php $scenarioSettings = $scenarioSettings ?? collect($scenarios)->mapWithKeys(fn ($s) => [$s['key'] => ['inflow' => 1.0, 'outflow' => 1.0]])->all(); @endphp
        @can('bi.exec')
            <div class="an-card">
                <div class="an-card-h">
                    <div>
                        <h2 class="an-card-t">Scenario Multipliers</h2>
                        <p class="an-card-s">Adjust the inflow / outflow multiplier used to build each 13-week projection.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('bi.scenarios.save') }}" class="an-filters" style="align-items:flex-end;">
                    @csrf
                    @foreach ($scenarios as $s)
                        @php $v = $scenarioSettings[$s['key']] ?? ['inflow' => 1.0, 'outflow' => 1.0]; @endphp
                        <span class="an-flabel">{{ $s['label'] }}
                            <span style="display:flex;gap:10px;margin-top:6px;">
                                <input type="number" name="scenario[{{ $s['key'] }}][inflow]" class="an-input" step="0.01" min="0" value="{{ number_format((float) ($v['inflow'] ?? 1), 2) }}" title="Inflow multiplier"> ×
                                <input type="number" name="scenario[{{ $s['key'] }}][outflow]" class="an-input" step="0.01" min="0" value="{{ number_format((float) ($v['outflow'] ?? 1), 2) }}" title="Outflow multiplier">
                            </span>
                        </span>
                    @endforeach
                    <button class="an-btn an-btn-cta" type="submit">Save multipliers</button>
                </form>
                <p class="an-note">Multipliers are applied to the baseline weekly inflow and outflow per branch. Values below 1 reduce, above 1 increase.</p>
            </div>
        @endcan
    </div>
</x-app-layout>