<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Recurring Journals</h1>
                    <div class="sub">Automate rent, salaries, depreciation, interest, subscriptions and accruals — with full control and audit.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.settings') }}" class="btn btn-ghost btn-sm">⚙ Settings</a>
                    <a href="{{ route('accounting.rj.reports') }}" class="btn btn-ghost btn-sm">📊 View Reports</a>
                    <form method="POST" action="{{ route('accounting.rj.dashboard') }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-sec btn-sm">▶ Run Scheduled Journals</button>
                    </form>
                    <a href="{{ route('accounting.rj.create') }}" class="btn btn-cta btn-sm">➕ Create Recurring Journal</a>
                </div>
            </div>

            <div class="kpis" style="margin-bottom:12px">
                <div class="kpi hero"><div class="l">Total Recurring Journals</div><div class="v">{{ $total }}</div><div class="n">{{ $active }} active · {{ $paused }} paused · {{ $expired }} expired</div></div>
                <div class="kpi"><div class="l">Active Schedules</div><div class="v">{{ $active }}</div><div class="n">@if($daysUntilNextRun !== null) next run in {{ $daysUntilNextRun }} days @else no upcoming runs @endif</div></div>
                <div class="kpi warn"><div class="l">Pending Journals</div><div class="v">{{ $pendingRuns }}</div><div class="n"><a class="open-l" href="{{ route('accounting.rj.approvals') }}">Approval queue →</a></div></div>
                <div class="kpi red"><div class="l">Failed Generations</div><div class="v">{{ $failedRuns }}</div><div class="n"><a class="open-l" href="{{ route('accounting.rj.generated', ['status' => 'failed']) }}">View failure →</a></div></div>
            </div>
            <div class="kpis" style="margin-bottom:16px">
                <div class="kpi"><div class="l">Generated This Month</div><div class="v">{{ $generatedThisMonth }}</div><div class="n">{{ $postedThisMonth }} posted · {{ $pendingRuns }} pending</div></div>
                <div class="kpi"><div class="l">Total Amount Posted</div><div class="v">{{ number_format($totalPostedAmount, 2) }}</div><div class="n">FY{{ now()->format('Y') }} to date</div></div>
                <div class="kpi"><div class="l">Upcoming Runs (7d)</div><div class="v">{{ $nextRun }}</div><div class="n">{{ number_format($nextRunAmount, 2) }} scheduled</div></div>
                <div class="kpi"><div class="l">Auto-post Enabled</div><div class="v">{{ $autoPostCount }}</div><div class="n">of {{ $active }} active schedules</div></div>
            </div>

            <section class="card">
                <div class="card-h"><h2>Upcoming Journal Runs</h2><a class="open-l" href="{{ route('accounting.rj.scheduled') }}" style="margin-left:auto">Scheduled journals →</a></div>
                <div class="li-wrap" style="margin-top:0;border:none;border-radius:0">
                    <table>
                        <thead><tr><th>Next Run</th><th>Journal</th><th>Type</th><th>Frequency</th><th class="num">Amount</th><th>Mode</th><th></th></tr></thead>
                        <tbody>
                            @forelse($upcomingRuns as $t)
                            <tr>
                                <td class="em">{{ $t->next_run_date?->format('d M Y') ?? '—' }}</td>
                                <td style="font-weight:700;color:var(--ink)"><a href="{{ route('accounting.rj.show', $t) }}" style="color:inherit;text-decoration:none">{{ $t->name }}</a></td>
                                <td><span class="tchip {{ $t->typeChipClass() }}">{{ $t->journal_type }}</span></td>
                                <td class="em">{{ ucfirst(str_replace('_', ' ', $t->frequency)) }}</td>
                                <td class="numr bold">{{ number_format($t->total_amount, 2) }}</td>
                                <td><span class="tchip @if($t->generation_mode === 'auto_post') tchip-green @endif">{{ str_replace('_', ' ', $t->generation_mode) }}</span></td>
                                <td>
                                    <div class="row-act">
                                        <form method="POST" action="{{ route('accounting.rj.run-now', $t) }}" style="display:inline">@csrf<button type="submit" class="btn btn-sec btn-xs">Run Now</button></form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="7" class="em" style="text-align:center;padding:24px">No upcoming journal runs in the next 7 days.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
