<x-app-layout>
    <div class="pd max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        <nav class="pd-crumbs">
            <a href="{{ route('accounting.payroll.dashboard') }}">Payroll</a> ›
            <a href="{{ route('accounting.payroll.runs.show', $run) }}">Run {{ $run->run_number }}</a> ›
            <span class="here">Distribution Status</span>
        </nav>

        <div class="pd-page-head">
            <div>
                <h1>Distribution Status</h1>
                <div class="pd-sub">Run {{ $run->run_number }} — {{ $run->period_label }}</div>
            </div>
            <div class="pd-cluster">
                <a href="{{ route('accounting.payroll.distribution.validate', $run) }}" class="pd-btn pd-btn-ghost pd-btn-sm">Validate & Send</a>
                <a href="{{ route('accounting.payroll.distribution.audit', ['run_id' => $run->id]) }}" class="pd-btn pd-btn-ghost pd-btn-sm">Audit Trail</a>
            </div>
        </div>

        {{-- Progress bar --}}
        @php
            $pct = $status['total'] > 0 ? round(($status['sent'] + $status['viewed']) / $status['total'] * 100) : 0;
        @endphp
        <div class="pd-progress-wrap">
            <div class="pd-progress-bar">
                <div class="pd-progress-fill" style="width:{{ $pct }}%"></div>
            </div>
            <div class="pd-progress-label">{{ $pct }}% delivered ({{ $status['sent'] + $status['viewed'] }} of {{ $status['total'] }})</div>
        </div>

        {{-- Status chips --}}
        <div class="pd-chips">
            <div class="pd-chip">
                <div class="pd-chip-l">Total</div>
                <div class="pd-chip-v">{{ $status['total'] }}</div>
            </div>
            <div class="pd-chip">
                <div class="pd-chip-l">Sent</div>
                <div class="pd-chip-v pd-chip-v-teal">{{ $status['sent'] }}</div>
            </div>
            <div class="pd-chip">
                <div class="pd-chip-l">Viewed by Employee</div>
                <div class="pd-chip-v pd-chip-v-mint">{{ $status['viewed'] }}</div>
            </div>
            <div class="pd-chip">
                <div class="pd-chip-l">Pending Delivery</div>
                <div class="pd-chip-v">{{ $status['pending_delivery'] }}</div>
            </div>
            @if($status['failed'] > 0)
                <div class="pd-chip pd-chip-warn">
                    <div class="pd-chip-l">Failed</div>
                    <div class="pd-chip-v">{{ $status['failed'] }}</div>
                </div>
            @endif
        </div>

        {{-- Employee distribution table --}}
        <div class="pd-card">
            <div class="pd-card-h">Employee Distribution Details</div>
            <div class="pd-table-wrap">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Employee #</th>
                            <th>Status</th>
                            <th>Email</th>
                            <th>Sent At</th>
                            <th class="num">Net Pay</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $p)
                            @php
                                $lastDist = $p->distributions->sortByDesc('created_at')->first();
                            @endphp
                            <tr>
                                <td class="pd-bold">{{ $p->employee?->full_name ?? '—' }}</td>
                                <td class="pd-mono">{{ $p->employee?->employee_number ?? '—' }}</td>
                                <td>
                                    <span class="pd-badge pd-badge-{{ $p->status }}">{{ $p->status_label }}</span>
                                </td>
                                <td class="pd-mono pd-em">{{ $lastDist?->email_address ?? $p->employee?->email ?? '—' }}</td>
                                <td class="pd-em">{{ $lastDist?->sent_at?->format('d M H:i') ?? '—' }}</td>
                                <td class="num">{{ format_number($p->net_pay) }}</td>
                                <td class="pd-actions">
                                    @if($p->status === 'draft')
                                        <form method="POST" action="{{ route('accounting.payroll.distribution.send', $p) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="pd-ibtn" title="Send">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                    @if($lastDist && $lastDist->status === 'failed')
                                        <form method="POST" action="{{ route('accounting.payroll.distribution.resend', $lastDist) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="pd-ibtn pd-ibtn-warn" title="Resend">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="pd-empty">No payslips for this run.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
