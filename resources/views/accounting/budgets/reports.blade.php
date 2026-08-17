<x-app-layout>
    <div class="bu-wrap">

        {{-- §3.1 PAGE HEAD — title left, actions right on one row --}}
        <div class="bur-head">
            <div>
                <h1>Budget Reports</h1>
                <div class="sub">Export and analyse budget performance.</div>
            </div>
            <div class="bur-head-acts">
                <div class="bur-bgroup">
                    <button type="button" onclick="window.print()"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>Print</button>
                    <a href="{{ route('accounting.budgets.reports', array_merge(request()->query(), ['export' => 'csv'])) }}"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>PDF</a>
                    <a href="{{ route('accounting.budgets.reports', array_merge(request()->query(), ['export' => 'csv'])) }}"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="8" y1="13" x2="16" y2="13"/><line x1="8" y1="17" x2="16" y2="17"/><line x1="12" y1="9" x2="12" y2="21"/></svg>Excel</a>
                </div>
                <span class="bur-vsep"></span>
                <form method="POST" action="{{ route('accounting.budgets.reports.save-view') }}" style="display:inline">
                    @csrf
                    <input type="hidden" name="report_type" value="{{ $reportType }}">
                    <input type="hidden" name="fiscal_year_id" value="{{ $fiscalYearId }}">
                    <input type="hidden" name="period" value="{{ $period }}">
                    <input type="hidden" name="department" value="{{ $department }}">
                    <input type="hidden" name="branch_id" value="{{ $branchId }}">
                    <button type="submit" class="bur-btn bur-btn-ghost"><svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/></svg>Save View</button>
                </form>
            </div>
        </div>

        <x-budgeting-subnav active-tab="reports" />

        {{-- §3.2 FILTER CARD --}}
        <div class="bu-card" style="margin-bottom:4px">
            <div class="bu-card-h">
                <h2 style="font-size:13.5px;font-weight:800;color:var(--ink)">Report Parameters</h2>
                <span style="font-size:11px;color:var(--faint);font-weight:600;margin-left:auto">Last generated {{ now()->format('d M Y, H:i') }} &middot; by {{ auth()->user()->name ?? 'System' }}</span>
            </div>
            <div class="bu-pad">
                <form method="GET" action="{{ route('accounting.budgets.reports') }}" id="bur-report-form">
                    <div class="bur-filters">
                        <div class="bur-f">
                            <label for="report-type">Report Type</label>
                            <select name="report_type" class="in" id="report-type">
                                <option value="vs_actual" {{ $reportType === 'vs_actual' ? 'selected' : '' }}>Budget vs Actual</option>
                                <option value="summary" {{ $reportType === 'summary' ? 'selected' : '' }}>Budget Summary</option>
                                <option value="variance" {{ $reportType === 'variance' ? 'selected' : '' }}>Variance Analysis</option>
                                <option value="department" {{ $reportType === 'department' ? 'selected' : '' }}>Departmental Performance</option>
                                <option value="utilization" {{ $reportType === 'utilization' ? 'selected' : '' }}>Budget Utilization</option>
                            </select>
                        </div>
                        <div class="bur-f">
                            <label for="fiscal-year">Fiscal Year</label>
                            <select name="fiscal_year_id" class="in" id="fiscal-year">
                                @foreach($fiscalYears as $fy)
                                    <option value="{{ $fy->id }}" {{ $fiscalYearId == $fy->id ? 'selected' : '' }}>{{ $fy->label ?? $fy->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bur-f">
                            <label for="period">Period</label>
                            <select name="period" class="in" id="period">
                                <option value="annual" {{ $period === 'annual' ? 'selected' : '' }}>Annual</option>
                                <option value="q1" {{ $period === 'q1' ? 'selected' : '' }}>Q1</option>
                                <option value="q2" {{ $period === 'q2' ? 'selected' : '' }}>Q2</option>
                                <option value="q3" {{ $period === 'q3' ? 'selected' : '' }}>Q3</option>
                                <option value="q4" {{ $period === 'q4' ? 'selected' : '' }}>Q4</option>
                                <option value="mtd" {{ $period === 'mtd' ? 'selected' : '' }}>Month to Date</option>
                            </select>
                        </div>
                        <div class="bur-f">
                            <label for="department">Department</label>
                            <select name="department" class="in" id="department">
                                <option value="">All Departments</option>
                                @foreach($departments as $dept)
                                    <option value="{{ $dept }}" {{ $department === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="bur-f">
                            <label for="branch-id">Branch</label>
                            <select name="branch_id" class="in" id="branch-id">
                                <option value="">All Branches</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ $branchId == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bur-btn bur-btn-cta" style="height:42px">Generate Report</button>
                    </div>

                    <div class="bur-divider"></div>

                    {{-- §3.2 Quick-switch segmented control --}}
                    <div class="bur-rtype">
                        <span class="lab">Quick switch</span>
                        <div class="bur-seg" id="bur-seg">
                            <button type="button" class="{{ $reportType === 'vs_actual' ? 'on' : '' }}" data-value="vs_actual">Budget vs Actual</button>
                            <button type="button" class="{{ $reportType === 'summary' ? 'on' : '' }}" data-value="summary">Budget Summary</button>
                            <button type="button" class="{{ $reportType === 'variance' ? 'on' : '' }}" data-value="variance">Variance</button>
                            <button type="button" class="{{ $reportType === 'department' ? 'on' : '' }}" data-value="department">Departmental</button>
                            <button type="button" class="{{ $reportType === 'utilization' ? 'on' : '' }}" data-value="utilization">Utilization</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if(isset($reportData))
        {{-- §3.3 KPI SUMMARY --}}
        <div class="bur-kpis">
            <div class="bur-kpi">
                <div class="l">Total Budgeted</div>
                <div class="v">{{ $cs }}{{ number_format($reportData['totalBudgeted'], 0) }}</div>
                <div class="n" style="color:var(--faint)">{{ $fiscalYear->label ?? $fiscalYear->name ?? '' }} &middot; {{ ucfirst($period) }}</div>
            </div>
            <div class="bur-kpi">
                <div class="l">Total Actual</div>
                <div class="v">{{ $cs }}{{ number_format($reportData['totalActual'], 0) }}</div>
                <div class="n" style="color:var(--faint)">posted from GL</div>
            </div>
            <div class="bur-kpi">
                <div class="l">Variance</div>
                <div class="v {{ $reportData['totalVariance'] >= 0 ? 'bur-up' : 'bur-dn' }}">{{ $cs }}{{ number_format(abs($reportData['totalVariance']), 0) }}</div>
                <div class="n {{ $reportData['totalVariance'] >= 0 ? 'bur-up' : 'bur-dn' }}">{{ $reportData['totalVariance'] >= 0 ? 'Favourable' : 'Unfavourable' }} &middot; {{ $reportData['totalVariancePct'] }}%</div>
            </div>
            <div class="bur-kpi hero">
                <div class="l">Utilization</div>
                <div class="v">{{ $reportData['overallUtil'] }}%</div>
                <div class="n">{{ $reportData['overCount'] + $reportData['warnings'] }} lines over 90%</div>
            </div>
        </div>

        {{-- §3.4 + §3.5 RESULTS --}}
        <div class="bur-grid2">
            {{-- Results table --}}
            <div class="bu-card">
                <div class="bu-card-h">
                    <h2 style="font-size:13.5px;font-weight:800;color:var(--ink)">
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
                        {{-- ======== VS_ACTUAL: full 7-col table ======== --}}
                        @if($reportType === 'vs_actual')
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

                        {{-- ======== SUMMARY: simplified 4-col table ======== --}}
                        @elseif($reportType === 'summary')
                        <thead>
                            <tr>
                                <th>Account / Line</th>
                                <th class="num">Budget</th>
                                <th class="num">Actual</th>
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
                                    <td>
                                        <span class="bu-badge bu-b-{{ $line['statusClass'] === 'ok' ? 'app' : ($line['statusClass'] === 'warn' ? 'pend' : 'lock') }}">
                                            <span class="bu-bdot"></span>{{ $line['statusLabel'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="bu-empty">No budget lines match the selected filters.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData['lines']) > 0)
                        <tfoot>
                            <tr>
                                <td style="font-weight:800">Total</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalBudgeted'], 2) }}</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalActual'], 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif

                        {{-- ======== VARIANCE: variance-focused 4-col table ======== --}}
                        @elseif($reportType === 'variance')
                        <thead>
                            <tr>
                                <th>Account / Line</th>
                                <th class="num">Variance</th>
                                <th class="num">Var %</th>
                                <th>Direction</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['lines'] as $line)
                                @php
                                    $isUnfavourable = ($line['variance'] < 0 && $line['line_type'] === 'expense') || ($line['variance'] > 0 && $line['line_type'] === 'income');
                                    $dirColor = $isUnfavourable ? 'var(--red-2)' : 'var(--green)';
                                    $dirLabel = $isUnfavourable ? 'Unfavourable' : 'Favourable';
                                    $dirBadge = $isUnfavourable ? 'lock' : 'app';
                                @endphp
                                <tr>
                                    <td style="font-weight:700;color:var(--ink)">
                                        <span class="bu-mono">{{ $line['account_code'] }}</span> &middot; {{ $line['account_name'] }}
                                        @if($line['line_type'] === 'income')
                                            <span class="bu-badge" style="background:rgba(18,143,142,.08);border:1px solid rgba(18,143,142,.3);color:var(--sec);font-size:9px;padding:2px 6px;margin-left:6px">Income</span>
                                        @endif
                                    </td>
                                    <td class="num" style="font-weight:800;color:{{ $dirColor }}">
                                        @if($line['variance'] < 0)({{ number_format(abs($line['variance']), 2) }})@else{{ number_format($line['variance'], 2) }}@endif
                                    </td>
                                    <td class="num" style="color:{{ $dirColor }}">
                                        {{ $line['variance'] >= 0 ? '+' : '−' }}{{ $line['variancePct'] }}%
                                    </td>
                                    <td>
                                        <span class="bu-badge bu-b-{{ $dirBadge }}">
                                            <span class="bu-bdot"></span>{{ $dirLabel }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="bu-empty">No budget lines match the selected filters.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData['lines']) > 0)
                        <tfoot>
                            <tr>
                                <td style="font-weight:800">Total</td>
                                <td class="num" style="font-weight:800;color:{{ $reportData['totalVariance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">
                                    @if($reportData['totalVariance'] < 0)({{ number_format(abs($reportData['totalVariance']), 2) }})@else{{ number_format($reportData['totalVariance'], 2) }}@endif
                                </td>
                                <td class="num" style="font-weight:800;color:{{ $reportData['totalVariance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">{{ $reportData['totalVariancePct'] }}%</td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif

                        {{-- ======== DEPARTMENT: department-grouped table ======== --}}
                        @elseif($reportType === 'department')
                        <thead>
                            <tr>
                                <th>Department</th>
                                <th class="num">Lines</th>
                                <th class="num">Budget</th>
                                <th class="num">Actual</th>
                                <th class="num">Variance</th>
                                <th style="width:16%">Utilization</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reportData['deptSummary'] as $dept => $d)
                                <tr>
                                    <td style="font-weight:700;color:var(--ink)">{{ $dept }}</td>
                                    <td class="num">{{ $d['lineCount'] }}</td>
                                    <td class="num">{{ number_format($d['budgeted'], 2) }}</td>
                                    <td class="num">{{ number_format($d['actual'], 2) }}</td>
                                    <td class="num" style="color:{{ $d['variance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">
                                        @if($d['variance'] < 0)({{ number_format(abs($d['variance']), 2) }})@else{{ number_format($d['variance'], 2) }}@endif
                                    </td>
                                    <td>
                                        <div class="bu-util">
                                            <div class="bu-ubar"><i class="bu-u-{{ $d['status'] }}" style="width:{{ min($d['util'], 100) }}%"></i></div>
                                            <span class="p">{{ $d['util'] }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="bu-badge bu-b-{{ $d['status'] === 'ok' ? 'app' : ($d['status'] === 'warn' ? 'pend' : 'lock') }}">
                                            <span class="bu-bdot"></span>{{ $d['status'] === 'ok' ? 'On track' : ($d['status'] === 'warn' ? 'Warning' : 'Over') }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="bu-empty">No departments with budget data.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData['deptSummary']) > 0)
                        <tfoot>
                            <tr>
                                <td style="font-weight:800">Total</td>
                                <td class="num" style="font-weight:800">{{ count($reportData['lines']) }}</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalBudgeted'], 2) }}</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalActual'], 2) }}</td>
                                <td class="num" style="font-weight:800;color:{{ $reportData['totalVariance'] >= 0 ? 'var(--green)' : 'var(--red-2)' }}">
                                    @if($reportData['totalVariance'] < 0)({{ number_format(abs($reportData['totalVariance']), 2) }})@else{{ number_format($reportData['totalVariance'], 2) }}@endif
                                </td>
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

                        {{-- ======== UTILIZATION: util-focused table ======== --}}
                        @else
                        <thead>
                            <tr>
                                <th>Account / Line</th>
                                <th class="num">Budget</th>
                                <th class="num">Actual</th>
                                <th style="width:20%">Utilization</th>
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
                                    <td>
                                        <div class="bu-util">
                                            <div class="bu-ubar"><i class="bu-u-{{ $line['statusClass'] }}" style="width:{{ min($line['utilization'], 100) }}%"></i></div>
                                            <span class="p" style="font-weight:800">{{ $line['utilization'] }}%</span>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="bu-badge bu-b-{{ $line['statusClass'] === 'ok' ? 'app' : ($line['statusClass'] === 'warn' ? 'pend' : 'lock') }}">
                                            <span class="bu-bdot"></span>{{ $line['statusLabel'] }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="bu-empty">No budget lines match the selected filters.</td></tr>
                            @endforelse
                        </tbody>
                        @if(count($reportData['lines']) > 0)
                        <tfoot>
                            <tr>
                                <td style="font-weight:800">Total</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalBudgeted'], 2) }}</td>
                                <td class="num" style="font-weight:800">{{ number_format($reportData['totalActual'], 2) }}</td>
                                <td>
                                    <div class="bu-util">
                                        <div class="bu-ubar"><i class="bu-u-warn" style="width:{{ min($reportData['overallUtil'], 100) }}%"></i></div>
                                        <span class="p" style="font-weight:800">{{ $reportData['overallUtil'] }}%</span>
                                    </div>
                                </td>
                                <td></td>
                            </tr>
                        </tfoot>
                        @endif
                        @endif
                    </table>
                </div>
            </div>

            {{-- §3.5 Chart card --}}
            <div class="bu-card">
                <div class="bu-card-h">
                    <h2 style="font-size:13.5px;font-weight:800;color:var(--ink)">Monthly Budget vs Actual</h2>
                    <div style="margin-left:auto">
                        <select class="bur-chart-select" id="chart-toggle">
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
        var segBtns = document.querySelectorAll('#bur-seg button');
        var select = document.getElementById('report-type');
        var form = document.getElementById('bur-report-form');

        segBtns.forEach(function(btn) {
            btn.addEventListener('click', function() {
                select.value = this.dataset.value;
                segBtns.forEach(function(b) { b.classList.remove('on'); });
                this.classList.add('on');
                form.submit();
            });
        });

        select.addEventListener('change', function() {
            segBtns.forEach(function(b) {
                b.classList.toggle('on', b.dataset.value === select.value);
            });
        });
    });
    </script>
    @endpush
</x-app-layout>
