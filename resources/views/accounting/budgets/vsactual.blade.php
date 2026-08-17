<x-app-layout>
    <div class="bu-wrap">
        <div class="bu-page-head">
            <div>
                <h1>Budget vs Actual</h1>
                <div class="sub">Compare budgeted amounts against actual GL figures.</div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.budgets.index') }}" class="bu-btn bu-btn-ghost">&larr; Back to Budgets</a>
            </div>
        </div>

        <x-budgeting-subnav active-tab="dashboard" />

        <div class="bu-card" style="margin-bottom:16px">
            <div class="bu-pad">
                <form method="GET" action="{{ route('accounting.budgets.vsactual') }}" class="bu-toolbar">
                    <select class="in" name="budget_id" required>
                        <option value="">Select Budget&hellip;</option>
                        @foreach($budgets as $b)
                            <option value="{{ $b->id }}" {{ ($selectedBudget?->id ?? request('budget_id')) == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                        @endforeach
                    </select>
                    <select class="in" name="fiscal_year_id">
                        <option value="">All Fiscal Years</option>
                        @foreach($fiscalYears as $fy)
                            <option value="{{ $fy->id }}" {{ request('fiscal_year_id') == $fy->id ? 'selected' : '' }}>{{ $fy->name }}</option>
                        @endforeach
                    </select>
                    <select class="in" name="branch_id">
                        <option value="">All Branches</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                        @endforeach
                    </select>
                    <select class="in" name="cost_center_id">
                        <option value="">All Cost Centers</option>
                        @foreach($costCenters as $cc)
                            <option value="{{ $cc->id }}" {{ request('cost_center_id') == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="bu-btn bu-btn-sec">Generate Report</button>
                    @if(request('budget_id'))
                        <a href="{{ route('accounting.budgets.vsactual') }}" class="bu-btn bu-btn-ghost">Clear</a>
                    @endif
                </form>
            </div>
        </div>

        @if($selectedBudget && $reportData)
            <div class="bu-sumbar" style="margin-bottom:16px">
                <div class="cell">
                    <div class="l">Total Budget</div>
                    <div class="v">{{ $cs }}{{ number_format($reportData['totalBudget'], 2) }}</div>
                </div>
                <div class="cell">
                    <div class="l">Total Actual</div>
                    <div class="v">{{ $cs }}{{ number_format($reportData['totalActual'], 2) }}</div>
                </div>
                <div class="cell">
                    <div class="l">Variance</div>
                    <div class="v" style="color:{{ $reportData['totalVariance'] < 0 ? 'var(--red-2)' : 'var(--green)' }}">{{ $cs }}{{ number_format(abs($reportData['totalVariance']), 2) }}</div>
                </div>
                <div class="cell hero">
                    <div class="l">Utilization</div>
                    <div class="v">{{ $reportData['totalBudget'] > 0 ? min(round($reportData['totalActual'] / $reportData['totalBudget'] * 100), 200) : 0 }}%</div>
                </div>
            </div>

            <div class="bu-card">
                <div class="bu-card-h"><h2>Line-by-Line Comparison</h2></div>
                <div class="bu-li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="num">Budget</th>
                                <th class="num">Actual</th>
                                <th class="num">Variance</th>
                                <th class="num">Variance %</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['lines'] as $line)
                                <tr>
                                    <td><span class="bu-mono">{{ $line['account']->code }}</span> {{ $line['account']->name }}</td>
                                    <td class="num">{{ $cs }}{{ number_format($line['budget'], 2) }}</td>
                                    <td class="num">{{ $cs }}{{ number_format($line['actual'], 2) }}</td>
                                    <td class="num {{ $line['variance'] < 0 ? 'red' : 'green' }}">{{ $cs }}{{ number_format(abs($line['variance']), 2) }}</td>
                                    <td class="num">{{ $line['variancePct'] }}%</td>
                                    <td>
                                        @php
                                            $vc = $line['utilization'] <= 84 ? 'vch-ok' : ($line['utilization'] <= 99 ? 'vch-warn' : 'vch-crit');
                                            $vl = $line['utilization'] <= 84 ? 'On Track' : ($line['utilization'] <= 99 ? 'Warning' : 'Over Budget');
                                        @endphp
                                        <span class="bu-vch {{ $vc }}">{{ $vl }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="bu-empty">No line data available for this budget.</td></tr>
                            @endforelse
                        </tbody>
                        @if(isset($reportData['lines']) && count($reportData['lines']) > 0)
                            <tfoot>
                                <tr>
                                    <td style="font-weight:800">Totals</td>
                                    <td class="num" style="font-weight:800">{{ $cs }}{{ number_format($reportData['totalBudget'], 2) }}</td>
                                    <td class="num" style="font-weight:800">{{ $cs }}{{ number_format($reportData['totalActual'], 2) }}</td>
                                    <td class="num {{ $reportData['totalVariance'] < 0 ? 'red' : 'green' }}" style="font-weight:800">{{ $cs }}{{ number_format(abs($reportData['totalVariance']), 2) }}</td>
                                    <td class="num" style="font-weight:800">{{ $reportData['totalBudget'] > 0 ? round($reportData['totalVariance'] / $reportData['totalBudget'] * 100, 1) : 0 }}%</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        @else
            <div class="bu-card">
                <div class="bu-empty">Select a budget above to generate the Budget vs Actual report.</div>
            </div>
        @endif
    </div>
</x-app-layout>
