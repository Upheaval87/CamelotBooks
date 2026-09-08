<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 an-wrap">
        @php
            $page = 'forecasts';
            $title = 'Forecasts';
            $subtitle = '13-week net cash-flow outlook on ' . $period['as_of'] . ' — eight weeks of actuals plus five projected weeks with confidence bands.';
        @endphp
        @include('analytics._nav')
        @include('analytics._head')

        <form method="GET" action="{{ route('analytics.forecasts') }}" class="an-filters">
            <span class="an-flabel">Period
                <select name="period" class="an-input">
                    @foreach (['month' => 'This month', 'quarter' => 'This quarter', 'ytd' => 'Year to date'] as $pk => $lbl)
                        <option value="{{ $pk }}" @selected($period['key'] === $pk)>{{ $lbl }}</option>
                    @endforeach
                </select>
            </span>
            <button class="an-btn an-btn-cta" type="submit">Apply</button>
            <a href="{{ route('analytics.forecasts') }}" class="an-btn an-btn-ghost">Clear</a>
        </form>

        @php
            $labels = $data['labels'] ?? [];
            $actual = $data['actual'] ?? [];
            $forecast = $data['forecast'] ?? [];
            $bandPlus = $data['band_plus'] ?? [];
            $bandMinus = $data['band_minus'] ?? [];
            $maxV = max(array_merge([1], array_map('abs', array_filter(array_merge($actual, $bandPlus), fn ($v) => $v !== null))));
        @endphp
        <div class="an-kpis an-kpis--4">
            <div class="an-kpi"><div class="l">Opening Cash ({{ $period['as_of'] }})</div><div class="v">@money($data['opening_cash'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Mean Weekly Net (8 wk)</div><div class="v {{ ($data['mean_weekly'] ?? 0) >= 0 ? 'up' : 'dn' }}">@money($data['mean_weekly'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Weekly Volatility (σ)</div><div class="v">@money($data['stddev'] ?? 0)</div></div>
            <div class="an-kpi"><div class="l">Forecast Horizon</div><div class="v">{{ $data['forecast_weeks'] ?? 5 }} weeks</div></div>
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">Weekly Net Cash Flow</h2>
                    <p class="an-card-s">Actuals (last {{ $data['actual_weeks'] ?? 8 }} weeks) with a {{ $data['forecast_weeks'] ?? 5 }}-week projection and σ-band.</p>
                </div>
                <div class="an-legend">
                    <span class="an-legend-k"><i class="sw act"></i>Actual</span>
                    <span class="an-legend-k"><i class="sw fcast"></i>Projected (mean)</span>
                    <span class="an-legend-k"><i class="sw band"></i>± σ band</span>
                </div>
            </div>
            @if ($labels)
                <div class="an-bars">
                    @foreach ($labels as $i => $label)
                        @php
                            $isProj = $i >= ($data['actual_weeks'] ?? count(array_filter($actual, fn ($v) => $v !== null)));
                            $isProj = $i >= ($data['actual_weeks'] ?? 8);
                            $val = $isProj ? ($forecast[$i] ?? 0) : ($actual[$i] ?? 0);
                            $bp = $bandPlus[$i] ?? null;
                            $bm = $bandMinus[$i] ?? null;
                            $bandStyle = ($bp !== null && $bm !== null) ? 'bottom:' . (((($bm + $maxV) / (2 * $maxV)) * 100)) . '%;height:' . ((abs($bp - $bm) / (2 * $maxV)) * 100) . '%' : '';
                        @endphp
                        <div class="an-bar">
                            <span class="an-bar-v" title="{{ $label }}: @money($val)"{{ $isProj ? ' data-proj="1"' : '' }}>{{ format_number($val, 0) }}</span>
                            @if ($bandStyle)
                                <span class="an-band" style="{{ $bandStyle }}"></span>
                            @endif
                            <div class="an-bar-cols">
                                <i class="an-bar-b {{ $isProj ? 'fcast' : 'act' }}" style="height:{{ (abs($val) / $maxV) * 100 }}%"></i>
                            </div>
                            <span class="an-bar-l">{{ $label }}</span>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="an-empty">No cash-flow history available for projection.</div>
            @endif
        </div>

        <div class="an-card">
            <div class="an-card-h">
                <div>
                    <h2 class="an-card-t">How to read this</h2>
                    <p class="an-card-s">The projection extends the trailing 8-week mean with a simple seasonal index; the ± band is one standard deviation of actual weekly net flow. Treat the projection as directional — actual results vary.</p>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>