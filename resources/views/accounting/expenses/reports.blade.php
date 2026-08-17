<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="ex-suite wrap">
        <div class="page-head">
            <div>
                <h1>{{ __('Expense Reports') }}</h1>
                <div class="sub">{{ __('Register, analysis, budget control and tax reporting.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap">
                <details class="more">
                    <summary class="btn btn-ghost btn-sm">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M6 9h12M6 15h12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        {{ __('More') }}
                    </summary>
                    <div class="more-menu">
                        <a class="more-item" href="{{ route('accounting.expenses.dashboard') }}">{{ __('Expense Dashboard') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.index') }}">{{ __('All Expenses') }}</a>
                        <a class="more-item" href="{{ route('accounting.expenses.claims.index') }}">{{ __('Expense Claims') }}</a>
                    </div>
                </details>
                <a href="{{ route('accounting.expenses.index') }}" class="btn btn-ghost btn-sm">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    {{ __('Record Expense') }}
                </a>
            </div>
        </div>

        <form method="GET" action="{{ route('accounting.expenses.reports') }}" class="filterbar" id="exp-reports-form">
            <div class="seg2">
                @foreach(['month' => __('This Month'), 'quarter' => __('This Quarter'), 'year' => __('This Year'), 'custom' => __('Custom')] as $pk => $pl)
                    <button type="submit" name="period" value="{{ $pk }}" class="{{ ($filters['period'] ?? 'month') === $pk ? 'on' : '' }}">{{ $pl }}</button>
                @endforeach
            </div>
            <select class="input" style="width:auto" name="branch_id" onchange="document.getElementById('exp-reports-form').submit()">
                <option value="">{{ __('All Branches') }}</option>
                @foreach($branches as $branch)
                    <option value="{{ $branch->id }}" {{ ($filters['branch_id'] ?? '') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                @endforeach
            </select>
            <select class="input" style="width:auto" name="department" onchange="document.getElementById('exp-reports-form').submit()">
                <option value="">{{ __('All Departments') }}</option>
                @foreach($departments as $dept)
                    <option value="{{ $dept }}" {{ ($filters['department'] ?? '') === $dept ? 'selected' : '' }}>{{ $dept }}</option>
                @endforeach
            </select>
            @if(($filters['period'] ?? 'month') === 'custom')
                <input class="input" style="width:auto" type="date" name="from_date" value="{{ $from?->format('Y-m-d') ?? request('from_date') }}">
                <input class="input" style="width:auto" type="date" name="to_date" value="{{ $to?->format('Y-m-d') ?? request('to_date') }}">
                <button class="btn btn-cta btn-xs" type="submit">{{ __('Apply') }}</button>
            @endif
            @if(($filters['period'] ?? 'month') !== 'custom')
                <button class="btn btn-ghost btn-xs" style="margin-left:auto" type="submit" disabled title="{{ __('Filters apply automatically') }}">{{ __('Filter') }}</button>
            @endif
        </form>

        <div class="repcards" style="margin-bottom:16px">
            <div class="repcard">
                <span class="t">{{ __('Expense Register') }}</span>
                <span class="d">{{ __('All expenses for the period with category, payee, status and payment.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.index') }}">{{ __('Open') }} →</a>
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('Expense by Category') }}</span>
                <span class="d">{{ __('Category totals with trend vs prior period.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.reports') }}">{{ __('Open') }} →</a>
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('By Department / Branch / Cost Centre') }}</span>
                <span class="d">{{ __('Spend grouped by any accounting dimension.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.reports') }}">{{ __('Open') }} →</a>
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('Employee Expenses') }}</span>
                <span class="d">{{ __('Claims by employee with approval and reimbursement status.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.claims.index') }}">{{ __('Open') }} →</a>
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('Expense vs Budget') }}</span>
                <span class="d">{{ __('Budget vs actual by category with variances and over-budget flags.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="#">{{ __('Open') }} →</a>
                    {{-- TODO: Restore link to accounting.budgets.index when budgeting module is rebuilt --}}
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('Unpaid Expenses') }}</span>
                <span class="d">{{ __('Approved/posted expenses awaiting payment, aged.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.index', ['payment_status' => 'unpaid']) }}">{{ __('Open') }} →</a>
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('Tax / VAT Expense Report') }}</span>
                <span class="d">{{ __('Input VAT by tax type and category for returns.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.reports') }}">{{ __('Open') }} →</a>
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('Pending Approval Report') }}</span>
                <span class="d">{{ __('Expenses and claims waiting at each approval step, by age.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.index', ['status' => 'pending']) }}">{{ __('Open') }} →</a>
                </div>
            </div>
            <div class="repcard">
                <span class="t">{{ __('Expense Audit Report') }}</span>
                <span class="d">{{ __('All changes with user, timestamp, old→new values and reasons.') }}</span>
                <div class="foot">
                    <span class="fmt">PDF</span><span class="fmt">Excel</span>
                    <a class="open-l" href="{{ route('accounting.expenses.index') }}">{{ __('Open') }} →</a>
                </div>
            </div>
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-bottom:16px">
            <div class="card">
                <div class="card-h">
                    <h2>{{ __('Summary') }} — {{ $periodLabel }}</h2>
                    <span class="fmt" style="margin-left:auto">{{ __('sample') }}</span>
                </div>
                <div class="card-sec">
                    <div class="g2">
                        <div class="field"><label>{{ __('Total Spend') }}</label><div class="val numr bold" style="font-size:1.429rem">{{ $cs }}{{ format_number($total) }}</div></div>
                        <div class="field"><label>{{ __('Expenses') }}</label><div class="val numr bold" style="font-size:1.429rem">{{ $count }}</div></div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-h">
                    <h2>{{ __('Expense by Category') }} — {{ $periodLabel }}</h2>
                </div>
                <div class="card-sec bars">
                    @forelse($byCategory as $cat)
                        @php $pct = max((float) $cat->total / $maxCategory * 100, 2); @endphp
                        <div class="brow">
                            <span class="lb">{{ $cat->category?->name ?? 'Uncategorised' }}</span>
                            <span class="bar"><i style="width:{{ $pct }}%"></i></span>
                            <span class="vl">{{ format_number($cat->total) }}</span>
                        </div>
                    @empty
                        <div class="em">{{ __('No expenses in this period.') }}</div>
                    @endforelse
                </div>
            </div>
        </div>

        @if($byDepartment->isNotEmpty())
            <div class="card">
                <div class="card-h">
                    <h2>{{ __('Expenses by Department') }} — {{ $periodLabel }}</h2>
                </div>
                <div class="card-sec" style="padding-top:6px">
                    <div class="li-wrap">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('Department') }}</th>
                                    <th class="num">{{ __('Expenses') }}</th>
                                    <th class="num">{{ __('Count') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($byDepartment as $row)
                                    <tr>
                                        <td class="em">{{ $row->department }}</td>
                                        <td class="numr">{{ format_number($row->total) }}</td>
                                        <td class="numr">{{ $row->c }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
