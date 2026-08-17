<x-app-layout>
    <div class="bu-wrap max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">

        <!-- §3.1 Head -->
        <div class="page-head">
            <div>
                <h1 style="font-size:22px;font-weight:800;letter-spacing:-.02em;color:var(--ink)">Budget Reports</h1>
                <div class="sub">Export and analyse budget performance.</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <button onclick="window.print()" class="bu-btn bu-btn-ghost bu-btn-sm">&#x1F5A8; Print</button>
                <a href="{{ route('accounting.budgets.reports', array_merge(request()->query(), ['export' => 'csv'])) }}" class="bu-btn bu-btn-ghost bu-btn-sm">PDF</a>
                <a href="{{ route('accounting.budgets.reports', array_merge(request()->query(), ['export' => 'csv'])) }}" class="bu-btn bu-btn-ghost bu-btn-sm">Excel</a>
                <button type="button" class="bu-btn bu-btn-ghost bu-btn-sm">Save View</button>
            </div>
        </div>

        <!-- §3.2 Filter Card -->
        <div class="bu-card" style="margin-bottom:16px">
            <div class="bu-pad">
                <form method="GET" action="{{ route('accounting.budgets.reports') }}" id="bu-report-form">
                    <div class="bu-filter-row">
                        <div class="bu-f">
                            <label>Report Type</label>
                            <select name="report_type" class="in" id="report-type-select">
                                <option value="vs_actual" {{ $reportType === 'vs_actual' ? 'selected' : '' }}>Budget vs Actual</option>
                                <option value="summary" {{ $reportType === 'summary' ? 'selected' : '' }}>Budget Summary</option>
                                <option value="variance" {{ $reportType === 'variance' ? 'selected' : '' }}>Variance Analysis</option>
                                <option value="department" {{ $reportType === 'department' ? 'selected' : '' }}>Departmental Performance</option>
                                <option value="utilization" {{ $reportType === 'utilization' ? 'selected' : '' }}>Budget Utilization</option>
                            </select>
                        </div>
                        <div class="bu-f">
                            <label>Fiscal Year</label>
                            <select name="fiscal_year_id" class="in">
                                @foreach($fiscalYears as $fy)
                                    <option value="{{ $fy->id }}" {{ request('fiscal_year_id', $currentFiscalYear?->id) == $fy->id ? 'selected' : '' }}>{{ $fy->label ?? $fy->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bu-f">
                            <label>Period</label>
                            <select name="period" class="in">
                                <option value="annual" {{ $period === 'annual' ? 'selected' : '' }}>Annual</option>
                                <option value="q1" {{ $period === 'q1' ? 'selected' : '' }}>Q1</option>
                                <option value="q2" {{ $period === 'q2' ? 'selected' : '' }}>Q2</option>
                                <option value="q3" {{ $period === 'q3' ? 'selected' : '' }}>Q3</option>
                                <option value="q4" {{ $period === 'q4' ? 'selected' : '' }}>Q4</option>
                                <option value="mtd" {{ $period === 'mtd' ? 'selected' : '' }}>Month to Date</option>
                            </select>
                        </div>
                        <div class="bu-f">
                            <label>Department</label>
                            <select name="department" class="in">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ $department === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bu-f">
                            <label>Branch</label>
                            <select name="branch_id" class="in">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bu-btn bu-btn-cta" style="height:42px;align-self:end">Generate Report</button>
                    </div>
                    <!-- Quick-switch chips -->
                    <div style="display:flex;gap:8px;flex-wrap:wrap;margin-top:14px" id="report-chips">
                        <button type="button" class="bu-chip {{ $reportType === 'vs_actual' ? 'on' : '' }}" data-value="vs_actual">Budget vs Actual</button>
                        <button type="button" class="bu-chip {{ $reportType === 'summary' ? 'on' : '' }}" data-value="summary">Budget Summary</button>
                        <button type="button" class="bu-chip {{ $reportType === 'variance' ? 'on' : '' }}" data-value="variance">Variance</button>
                        <button type="button" class="bu-chip {{ $reportType === 'department' ? 'on' : '' }}" data-value="department">Departmental</button>
                        <button type="button" class="bu-chip {{ $reportType === 'utilization' ? 'on' : '' }}" data-value="utilization">Utilization</button>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($reportData))
        <!-- §3.3 KPI Summary -->
        <div class="bu-kpis" style="margin-bottom:16px">
            <div class="bu-kpi">
                <div class="l">Total Budgeted</div>
                <div class="v">{{ $cs }}{{ number_format($reportData['totalBudgeted'], 0) }}</div>
                <div class="n">{{ $fiscalYear->label ?? $fiscalYear->name ?? '' }} &middot; {{ ucfirst($period) }}</div>
            </div>
            <div class="bu-kpi">
                <div class="l">Total Actual</div>
                <div class="v">{{ $cs }}{{ number_format($reportData['totalActual'], 0) }}</div>
                <div class="n">posted from GL</div>
            </div>
            <div class="bu-kpi">
                <div class="l">Variance</div>
                <div class="v" style="color:{{ $reportData['totalVariance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">{{ $cs }}{{ number_format(abs($reportData['totalVariance']), 0) }}</div>
                <div class="n" style="color:{{ $reportData['totalVariance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">{{ $reportData['totalVariance'] >= 0 ? 'Favourable' : 'Unfavourable' }} &middot; {{ $reportData['totalVariancePct'] }}%</div>
            </div>
            <div class="bu-kpi hero">
                <div class="l">Utilization</div>
                <div class="v">{{ $reportData['overallUtil'] }}%</div>
                <div class="n">{{ $reportData['overCount'] + $reportData['warnings'] }} lines over 90%</div>
            </div>
        </div>

        <!-- §3.4 + §3.5 Grid -->
        <div class="bu-g2">
            <!-- Results Table -->
            <div class="bu-card">
                <div class="bu-card-h">
                    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">
                        @if($reportType === 'vs_actual') Budget vs Actual &mdash; by line
                        @elseif($reportType === 'summary') Budget Summary
                        @elseif($reportType === 'variance') Variance Analysis
                        @elseif($reportType === 'department') Departmental Performance
                        @else Budget Utilization
                        @endif
                    </h2>
                    <div style="margin-left:auto;display:flex;gap:8px;flex-wrap:wrap">
                        @if($reportData['warnings'] > 0)
                            <span class="bu-badge bu-b-pend"><span class="bu-bdot"></span>{{ $reportData['warnings'] }} {{ Str::plural('warning', $reportData['warnings']) }}</span>
                        @endif
                        @if($reportData['overCount'] > 0)
                            <span class="bu-badge bu-b-lock"><span class="bu-bdot"></span>{{ $reportData['overCount'] }} over</span>
                        @endif
                    </div>
                </div>
                <div class="bu-li-wrap">
                    <table>
                        <thead>
                            <tr>
                                <th>Account / Line</th>
                                <th class="num">Budget</th>
                                <th class="num">Actual</th>
                                <th class="num">Variance</th>
                                <th class="num">Var %</th>
                                <th style="width:16%">Utilization</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['lines'] as $line)
                                <tr>
                                    <td style="font-weight:700;color:var(--ink)">
                                        <span class="bu-mono">{{ $line['account_code'] }}</span> &middot; {{ $line['account_name'] }}
                                        @if($line['line_type'] === 'income')
                                            <span class="bu-badge" style="background:rgba(18,143,142,.08);border:1px solid rgba(18,143,142,.3);color:var(--sec);font-size:9px;padding:2px 6px;margin-left:6px">Income</span>
                                        @endif
                                    </td>
                                    <td class="num">{{ number_format($line['budgeted'], 2) }}</td>
                                    <td class="num">{{ number_format($line['actual'], 2) }}</td>
                                    <td class="num" style="color:{{ ($line['variance'] < 0 && $line['line_type'] === 'expense') || ($line['variance'] > 0 && $line['line_type'] === 'income') ? 'var(--red-2)' : 'var(--green)' }}">
                                        @if($line['variance'] < 0)({{ number_format(abs($line['variance']), 2) }})@else{{ number_format($line['variance'], 2) }}@endif
                                    </td>
                                    <td class="num" style="color:{{ ($line['variance'] < 0 && $line['line_type'] === 'expense') || ($line['variance'] > 0 && $line['line_type'] === 'income') ? 'var(--red-2)' : 'var(--green)' }}">
                                        {{ $line['variance'] >= 0 ? '+' : '−' }}{{ $line['variancePct'] }}%
                                    </td>
                                    <td>
                                        <div class="bu-util">
                                            <div class="bu-ubar"><i class="bu-u-{{ $line['statusClass'] }}" style="width:{{ min($line['utilization'], 100) }}%"></i></div>
                                            <span class="p">{{ $line['utilization'] }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="bu-badge bu-b-{{ $line['statusClass'] === 'ok' ? 'app' : ($line['statusClass'] === 'warn' ? 'pend' : 'lock') }}">
                                            <span class="bu-bdot"></span>{{ $line['statusLabel'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="bu-empty">No budget lines match the selected filters.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData['lines']) > 0)
                        <tfoot>
                            <tr>
                                <td style="font-weight:800">Total</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalBudgeted'], 2) }}</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalActual'], 2) }}</td>
                                <td class="num" style="font-weight:800;color:{{ $reportData['totalVariance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">
                                    @if($reportData['totalVariance'] < 0)({{ number_format(abs($reportData['totalVariance']), 2) }})@else{{ number_format($reportData['totalVariance'], 2) }}@endif
                                </td>
                                <td class="num" style="font-weight:800;color:{{ $reportData['totalVariance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">{{ $reportData['totalVariancePct'] }}%</td>
                                <td>
                                    <div class="bu-util">
                                        <div class="bu-ubar"><i class="bu-u-warn" style="width:{{ min($reportData['overallUtil'], 100) }}%"></i></div>
                                        <span class="p">{{ $reportData['overallUtil'] }}%</span>
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>

            <!-- §3.5 Chart Card -->
            <div class="bu-card">
                <div class="bu-card-h">
                    <h2 style="font-size:14px;font-weight:800;color:var(--ink)">Monthly Budget vs Actual</h2>
                    <div style="margin-left:auto">
                        <select class="in" style="height:36px;width:auto;font-size:12.5px" id="chart-toggle">
                            <option value="all">All</option>
                            <option value="expense">Expenses</option>
                            <option value="income">Income</option>
                        </select>
                    </div>
                </div>
                <div class="bu-pad">
                    <div class="bu-chart" id="report-chart">
                        @php
                            $cd = $reportData['chartData'];
                            $allVals = array_merge($cd['budget'], $cd['actual']);
                            $maxVal = count($allVals) > 0 ? max(array_merge($allVals, [1])) : 1;
                        @endphp
                        @foreach($cd['labels'] as $i => $label)
                            <div class="bu-cg">
                                <div class="bu-bars">
                                    <i class="bu-b-budget" style="height:{{ round(($cd['budget'][$i] ?? 0) / $maxVal * 100) }}%" title="Budget: {{ number_format($cd['budget'][$i] ?? 0, 0) }}"></i>
                                    <i class="bu-b-actual" style="height:{{ round(($cd['actual'][$i] ?? 0) / $maxVal * 100) }}%" title="Actual: {{ number_format($cd['actual'][$i] ?? 0, 0) }}"></i>
                                </div>
                                <span class="bu-cm">{{ $label }}</span>
                            </div>
                        @endforeach
                        @if(empty($cd['labels']))
                            <div class="bu-empty" style="width:100%;text-align:center;padding:40px 0">No chart data for this period.</div>
                        @endif
                    </div>
                    <div class="bu-legend">
                        <span><i class="bu-b-budget"></i>Budget</span>
                        <span><i class="bu-b-actual"></i>Actual</span>
                    </div>
                </div>
            </div>
        </div>
        @endif
    </div>

    @push('scripts')
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        var chips = document.querySelectorAll('#report-chips .bu-chip');
        var select = document.getElementById('report-type-select');
        var form = document.getElementById('bu-report-form');

        chips.forEach(function(chip) {
            chip.addEventListener('click', function() {
                select.value = this.dataset.value;
                chips.forEach(function(c) { c.classList.remove('on'); });
                this.classList.add('on');
                form.submit();
            });
        });

        select.addEventListener('change', function() {
            chips.forEach(function(c) {
                c.classList.toggle('on', c.dataset.value === select.value);
            });
        });
    });
    </script>
    @endpush
</x-app-layout>
