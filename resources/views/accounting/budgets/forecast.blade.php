<x-app-layout>
    <div class="bu-wrap max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="page-head">
            <div>
                <h1 style="font-size:21px;font-weight:800;letter-spacing:-.02em;color:var(--ink)">Budget Forecast</h1>
                <div class="sub">Trend analysis and forecasting based on historical actuals.</div>
            </div>
        </div>

        <div class="bu-card" style="margin-top:20px">
            <div class="bu-pad">
                <form method="GET" action="{{ route('accounting.budgets.forecast') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <select name="budget_id" class="in" required>
                        <option value="">Select budget...</option>
                        @foreach($budgets as $b)
                            <option value="{{ $b->id }}" {{ request('budget_id') == $b->id ? 'selected' : '' }}>{{ $b->name }} ({{ $b->code }})</option>
                        @endforeach
                    </select>
                    <select name="trend_months" class="in">
                        <option value="3" {{ request('trend_months', 6) == 3 ? 'selected' : '' }}>3 months trend</option>
                        <option value="6" {{ request('trend_months', 6) == 6 ? 'selected' : '' }}>6 months trend</option>
                        <option value="12" {{ request('trend_months', 6) == 12 ? 'selected' : '' }}>12 months trend</option>
                    </select>
                    <button type="submit" class="bu-btn bu-btn-cta">Run Forecast</button>
                </form>
            </div>
        </div>

        @if(isset($forecastData))
            <div class="bu-card" style="margin-top:16px">
                <div class="bu-card-h">Forecast Summary</div>
                <div class="bu-pad">
                    <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:12px">
                        <div class="bu-kpi"><div class="l">Trend Period</div><div class="v">{{ $forecastData['trendMonths'] }} months</div></div>
                        <div class="bu-kpi"><div class="l">Avg Monthly Actual</div><div class="v">{{ number_format($forecastData['avgMonthlyActual'], 2) }}</div></div>
                        <div class="bu-kpi"><div class="l">Forecast Year Total</div><div class="v">{{ number_format($forecastData['forecastTotal'], 2) }}</div></div>
                    </div>
                </div>
            </div>

            <div class="bu-card" style="margin-top:16px">
                <div class="bu-card-h">Monthly Trend</div>
                <div class="bu-pad">
                    <div class="bu-li-wrap">
                        <table>
                            <thead><tr><th>Account</th><th class="num">Monthly Avg</th><th class="num">Forecast (12mo)</th><th class="num">Budget</th><th class="num">Projected Variance</th></tr></thead>
                            <tbody>
                                @forelse($forecastData['lines'] as $row)
                                    <tr>
                                        <td>{{ $row['account']->code }} — {{ $row['account']->name }}</td>
                                        <td class="num">{{ number_format($row['monthlyAvg'], 2) }}</td>
                                        <td class="num">{{ number_format($row['forecastAmount'], 2) }}</td>
                                        <td class="num">{{ number_format($row['budget'], 2) }}</td>
                                        <td class="num"><span class="bu-vch {{ $row['variance'] >= 0 ? 'bu-vch-ok' : 'bu-vch-crit' }}">{{ number_format($row['variance'], 2) }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="bu-empty">No forecast data. Select a budget and run the report.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
