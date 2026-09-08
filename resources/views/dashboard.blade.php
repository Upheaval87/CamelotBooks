<x-app-layout>
    <main class="dash wrap">

        {{-- HEAD --}}
        <header class="dash-head">
            <div class="dash-head-g">
                <h1>{{ $greeting }}</h1>
                <p class="dash-sub">{{ $subhead }}</p>
            </div>
            <div class="dash-head-r">
                <nav class="dash-chips" aria-label="Dashboard period">
                    <a href="{{ route('dashboard', ['period' => 'month']) }}" class="dash-chip {{ $preset === 'month' ? 'on' : '' }}" aria-current="{{ $preset === 'month' ? 'page' : 'false' }}">This Month</a>
                    <a href="{{ route('dashboard', ['period' => 'quarter']) }}" class="dash-chip {{ $preset === 'quarter' ? 'on' : '' }}" aria-current="{{ $preset === 'quarter' ? 'page' : 'false' }}">This Quarter</a>
                    <a href="{{ route('dashboard', ['period' => 'ytd']) }}" class="dash-chip {{ $preset === 'ytd' ? 'on' : '' }}" aria-current="{{ $preset === 'ytd' ? 'page' : 'false' }}">Year to Date</a>
                </nav>
                <a href="{{ $exportUrl }}" class="btn btn-ghost btn-sm dash-export">
                    <svg class="dash-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M12 4v12m0 0l-4-4m4 4l4-4"/></svg>
                    Export
                </a>
            </div>
        </header>
        <p class="dash-sec">{{ $sec_range }}</p>

        {{-- KPI --}}
        <section class="dash-kpis" aria-label="Key metrics">
            <div class="card dash-kpi">
                <div class="dash-kpi-top">
                    <h3>Total Revenue</h3>
                    <span class="d {{ $kpi['revenue']['dir'] }}"><svg class="dash-arr" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg><b>{{ $kpi['revenue']['delta'] }}%</b></span>
                </div>
                <p class="dash-kpi-v">{{ format_money($kpi['revenue']['value'], null, $decimals) }}</p>
                <p class="dash-kpi-s">{{ $kpi['revenue']['sub'] }}</p>
            </div>

            <div class="card dash-kpi">
                <div class="dash-kpi-top">
                    <h3>Total Expenses</h3>
                    <span class="d {{ $kpi['expenses']['dir'] }}"><svg class="dash-arr" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg><b>{{ $kpi['expenses']['delta'] }}%</b></span>
                </div>
                <p class="dash-kpi-v">{{ format_money($kpi['expenses']['value'], null, $decimals) }}</p>
                <p class="dash-kpi-s">{{ $kpi['expenses']['sub'] }}</p>
            </div>

            <div class="card dash-kpi">
                <div class="dash-kpi-top">
                    <h3>Net Profit</h3>
                    <span class="d {{ $kpi['net']['margin_dir'] }}"><svg class="dash-arr" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18"/></svg><b>margin {{ $kpi['net']['margin'] }}%</b></span>
                </div>
                <p class="dash-kpi-v">{{ format_money($kpi['net']['value'], null, $decimals) }}</p>
                <p class="dash-kpi-s">{{ $kpi['net']['sub'] }}</p>
            </div>

            <div class="card dash-kpi">
                <div class="dash-kpi-top">
                    <h3>Outstanding Invoices</h3>
                    <span class="d nt"><svg class="dash-arr" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18M5 14l7 7m0 0l7-7m-7 7V3"/></svg></span>
                </div>
                <p class="dash-kpi-v">{{ format_money($kpi['outstanding']['value'], null, $decimals) }}</p>
                <p class="dash-kpi-s">{{ $kpi['outstanding']['sub'] }}</p>
            </div>

            <div class="card dash-kpi">
                <div class="dash-kpi-top">
                    <h3>Bills Payable</h3>
                    <span class="d nt"><svg class="dash-arr" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18M5 14l7 7m0 0l7-7m-7 7V3"/></svg></span>
                </div>
                <p class="dash-kpi-v">{{ format_money($kpi['payables']['value'], null, $decimals) }}</p>
                <p class="dash-kpi-s">{{ $kpi['payables']['sub'] }}</p>
            </div>

            <div class="card dash-kpi">
                <div class="dash-kpi-top">
                    <h3>Cash &amp; Bank</h3>
                    <span class="d nt"><svg class="dash-arr" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 10l7-7m0 0l7 7m-7-7v18M5 14l7 7m0 0l7-7m-7 7V3"/></svg></span>
                </div>
                <p class="dash-kpi-v">{{ format_money($kpi['cash']['value'], null, $decimals) }}</p>
                <p class="dash-kpi-s">{{ $kpi['cash']['sub'] }}</p>
            </div>
        </section>

        @if ($empty)
            {{-- Fresh company: keep the QA tiles, show a single empty-state card --}}
            <section class="card dash-empty-hero">
                <div class="dash-empty-ic"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></div>
                <h3>Welcome to your dashboard</h3>
                <p class="dash-empty-t">Your numbers will appear here once you post your first transactions. Start with a quick action below.</p>
            </section>
        @else
            {{-- Chart + Cash --}}
            <section class="dash-row dash-chrow" aria-label="Revenue vs expenses and cash position">
                <div class="card dash-chart">
                    <div class="dash-card-h">
                        <h3>Revenue vs Expenses</h3>
                        <p class="dash-card-c"><svg class="dash-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6"/></svg>{{ $chart['caption'] }}</p>
                    </div>
                    <div class="dash-chart-g">
                        <div class="dash-chart-bars">
                            @foreach ($chart['labels'] as $i => $label)
                                <div class="dash-cg">
                                    <div class="dash-bar-rev" style="height: {{ $chart['rev_pct'][$i] }}%"></div>
                                    <div class="dash-bar-exp" style="height: {{ $chart['exp_pct'][$i] }}%"></div>
                                </div>
                            @endforeach
                        </div>
                        <div class="dash-chart-x">
                            @foreach ($chart['labels'] as $label)
                                <span>{{ $label }}</span>
                            @endforeach
                        </div>
                        <div class="dash-chart-leg">
                            <span class="dash-leg dash-leg--rev">Revenue</span>
                            <span class="dash-leg dash-leg--exp">Expenses</span>
                        </div>
                    </div>
                </div>

                <aside class="card dash-cash">
                    <div class="dash-card-h">
                        <h3>Cash Position</h3>
                        <a href="{{ route('accounting.banking.dashboard') }}" class="dash-link">Banking &rarr;</a>
                    </div>
                    <div class="dash-cash-t">
                        <div class="dash-cash-tt">Total Cash</div>
                        <div class="dash-cash-tv">{{ format_money($cash['total'], null, $decimals) }}</div>
                        <div class="dash-cash-tr">{{ count($cash['rows']) }} accounts</div>
                    </div>
                    <ul class="dash-cash-l">
                        @forelse ($cash['rows'] as $row)
                            <li>
                                <span class="dash-cash-ic {{ $row['badge'] === 'BANK' ? 'dash-cash-ic--bank' : 'dash-cash-ic--cash' }}"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 10h18M7 15h2m4 0h2m-8 5h10a2 2 0 002-2v-8a2 2 0 00-2-2H7a2 2 0 00-2 2v8a2 2 0 002 2zM7 5V3m10 2V3"/></svg></span>
                                <span class="dash-cash-n">{{ $row['name'] }}</span>
                                <span class="dash-cash-b badge">{{ $row['badge'] }}</span>
                                <span class="dash-cash-a">{{ format_money($row['value'], null, $decimals) }}</span>
                            </li>
                        @empty
                            <li class="dash-cash-empty">No cash or bank accounts yet.</li>
                        @endforelse
                    </ul>
                    @if ((int) $cash['cheques'] > 0)
                        <p class="dash-cash-f">· {{ $cash['cheques'] }} outstanding {{ $cash['cheques'] === 1 ? 'cheque' : 'cheques' }}</p>
                    @endif
                </aside>
            </section>

            {{-- Aging + Upcoming --}}
            <section class="dash-row dash-drow" aria-label="Receivables aging and upcoming bills">
                <div class="card dash-aging">
                    <div class="dash-card-h">
                        <h3>Receivables Aging</h3>
                        <a href="{{ route('accounting.aging.ar-summary') }}" class="dash-link">View AR Aging &rarr;</a>
                    </div>
                    <div class="dash-aging-b">
                        @foreach ($aging['buckets'] as $bucket)
                            <div class="dash-aging-r">
                                <div class="dash-aging-row">
                                    <span class="dash-aging-l">{{ $bucket['label'] }}</span>
                                    <span class="dash-aging-c">{{ $bucket['count'] }}</span>
                                    <span class="dash-aging-v">{{ format_money($bucket['amount'], null, $decimals) }}</span>
                                </div>
                                <div class="dash-aging-track"><span class="dash-aging-fill dash-aging-fill--{{ $bucket['tone'] }}" style="width: {{ $bucket['pct'] }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                    <div class="dash-total">
                        <span>Total Receivables</span>
                        <span>{{ format_money($aging['total'], null, $decimals) }}</span>
                    </div>
                </div>

                <aside class="card dash-upcoming">
                    <div class="dash-card-h">
                        <h3>Upcoming Bills &amp; Taxes</h3>
                        <a href="{{ route('accounting.bills.index') }}" class="dash-link">View all &rarr;</a>
                    </div>
                    <ul class="dash-upl">
                        @php $shown = 0; @endphp
                        @foreach ($upcoming['bills'] as $bill)
                            @php $shown++; @endphp
                            <li>
                                <span class="dash-upl-b"><svg class="dash-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                                <span class="dash-upl-tx">
                                    <span class="dash-upl-n">{{ $bill['name'] }}</span>
                                    <span class="dash-upl-r">{{ $bill['ref'] }}</span>
                                </span>
                                <span class="dash-upl-d {{ $bill['overdue'] ? 'ov' : '' }}">{{ $bill['overdue'] ? 'Overdue' : $bill['due'] }}</span>
                                <span class="dash-upl-a">{{ format_money($bill['amount'], null, $decimals) }}</span>
                            </li>
                        @endforeach
                        @foreach ($upcoming['taxes'] as $tax)
                            @php $shown++; @endphp
                            <li>
                                <span class="dash-upl-b dash-upl-b--tax"><svg class="dash-ico" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 3v18m-6-3l12-6m0 12l-12-6"/></svg></span>
                                <span class="dash-upl-tx">
                                    <span class="dash-upl-n">{{ $tax['name'] }} — {{ $tax['period'] }}</span>
                                    <span class="dash-upl-r">{{ $tax['ref'] }}</span>
                                </span>
                                <span class="dash-upl-d {{ $tax['overdue'] ? 'ov' : '' }}">{{ $tax['overdue'] ? 'Overdue' : $tax['due'] }}</span>
                                <span class="dash-upl-a">{{ format_money($tax['amount'], null, $decimals) }}</span>
                            </li>
                        @endforeach
                        @if ($shown === 0)
                            <li class="dash-empty-s">All clear — no bills or taxes due soon.</li>
                        @endif
                    </ul>
                </aside>
            </section>
        @endif

        {{-- Quick actions + tasks (always rendered, even before the first transaction) --}}
        <section class="dash-row dash-qtrow" aria-label="Quick actions and tasks">
                <div class="card dash-qa">
                    <div class="dash-card-h">
                        <h3>Quick Actions</h3>
                    </div>
                    <div class="dash-qa-g">
                        @if ($can['invoice'])
                        <a href="{{ route('accounting.invoices.create') }}" class="dash-qa-t dash-qa-t--prime">
                            <span class="dash-qa-ic "><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                            <span class="dash-qa-tx">New Invoice</span>
                            <span class="dash-qa-go">+</span>
                        </a>
                        @endif
                        @if ($can['bill'])
                        <a href="{{ route('accounting.bills.create') }}" class="dash-qa-t">
                            <span class="dash-qa-ic"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg></span>
                            <span class="dash-qa-tx">New Bill</span>
                            <span class="dash-qa-go">+</span>
                        </a>
                        @endif
                        @if ($can['payment'])
                        <a href="{{ route('accounting.customer-payments.create') }}" class="dash-qa-t">
                            <span class="dash-qa-ic"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-2.21 0-4 1.12-4 2.5S9.79 13 12 13s4-1.12 4-2.5S14.21 8 12 8zm-4 6c0 .99 2.21 2 4 2s4-1.01 4-2M4 5v14a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H6a2 2 0 00-2 2z"/></svg></span>
                            <span class="dash-qa-tx">Record Payment</span>
                            <span class="dash-qa-go">+</span>
                        </a>
                        @endif
                        @if ($can['journal'])
                        <a href="{{ route('accounting.journal-entries.create') }}" class="dash-qa-t">
                            <span class="dash-qa-ic"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></span>
                            <span class="dash-qa-tx">New Journal</span>
                            <span class="dash-qa-go">+</span>
                        </a>
                        @endif
                        @if ($can['customer'])
                        <a href="{{ route('accounting.customers.create') }}" class="dash-qa-t">
                            <span class="dash-qa-ic"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a4 4 0 00-3-3.87M9 20H4v-2a4 4 0 013-3.87m6-1.13a4 4 0 10-4 0m7-6a4 4 0 11-4-5 4 4 0 014 5z"/></svg></span>
                            <span class="dash-qa-tx">New Customer</span>
                            <span class="dash-qa-go">+</span>
                        </a>
                        @endif
                        @if ($can['reconcile'])
                        <a href="{{ route('accounting.bank-reconciliation.index') }}" class="dash-qa-t">
                            <span class="dash-qa-ic"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg></span>
                            <span class="dash-qa-tx">Reconcile</span>
                            <span class="dash-qa-go">+</span>
                        </a>
                        @endif
                    </div>
                </div>

                <aside class="card dash-tasks">
                    <div class="dash-card-h">
                        <h3>My Tasks</h3>
                        <a href="{{ route('todo.index') }}" class="dash-link">Open tasks</a>
                    </div>
                    <ul class="dash-tasl">
                        @forelse ($tasks as $task)
                            <li>
                                <span class="dash-tas-d {{ $task['overdue'] ? 'dash-tas-d--red' : '' }}"></span>
                                @if ($task['url'])
                                    <a href="{{ $task['url'] }}" class="dash-tas-l">{{ $task['label'] }}</a>
                                @else
                                    <span class="dash-tas-l">{{ $task['label'] }}</span>
                                @endif
                                <span class="dash-tas-ch {{ $task['overdue'] ? 'dash-tas-ch--ov' : ($task['pending'] ? 'dash-tas-ch--pd' : '') }}">{{ $task['due'] }}</span>
                            </li>
                        @empty
                            <li class="dash-empty-s">You're all caught up — no tasks due.</li>
                        @endforelse
                    </ul>
                </aside>
            </section>

            @if (! $empty)
        {{-- Recent activity --}}
        <section class="card dash-activity" aria-label="Recent activity">
                <div class="dash-card-h">
                    <h3>Recent Activity</h3>
                    <a href="{{ route('accounting.journal-entries.index') }}" class="dash-link">View all &rarr;</a>
                </div>
                <div class="dash-actl">
                    @forelse ($activity as $event)
                        <div class="dash-act">
                            <span class="dash-act-ic {{ $event['ic'] }}"><svg fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                            <div class="dash-act-tx">
                                @if ($event['url'])
                                    <p class="dash-act-d"><a href="{{ $event['url'] }}">{{ $event['desc'] }}</a></p>
                                @else
                                    <p class="dash-act-d">{{ $event['desc'] }}</p>
                                @endif
                                <p class="dash-act-w">{{ $event['when'] }}</p>
                            </div>
                            <span class="dash-act-a {{ $event['cls'] }}">{{ $event['cls'] === 'pos' ? '+' : '' }}{{ format_money($event['amount'], null, $decimals) }}</span>
                        </div>
                    @empty
                        <div class="dash-empty-pad">No activity yet — transactions will show up here.</div>
                    @endforelse
                </div>
            </section>
        @endif
    </main>
</x-app-layout>