<x-app-layout>
    <div class="pd max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        <nav class="pd-crumbs">
            <a href="{{ route('accounting.payroll.dashboard') }}">Payroll</a> ›
            <a href="{{ route('accounting.payroll.runs.show', $run) }}">Run {{ $run->run_number }}</a> ›
            <span class="here">Validate & Send</span>
        </nav>

        <div class="pd-page-head">
            <div>
                <h1>Pre-Distribution Validation</h1>
                <div class="pd-sub">Run {{ $run->run_number }} — {{ $run->period_label }}</div>
            </div>
            <div class="pd-cluster">
                <a href="{{ route('accounting.payroll.distribution.status', $run) }}" class="pd-btn pd-btn-ghost pd-btn-sm">← Back to Status</a>
            </div>
        </div>

        @if($allValid)
            <div class="pd-callout pd-callout-success">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                <div>
                    <strong>All {{ $payslips->count() }} payslips are ready for distribution.</strong>
                    <div class="pd-callout-sub">All employees have valid email addresses and email delivery is enabled.</div>
                </div>
            </div>

            <form method="POST" action="{{ route('accounting.payroll.distribution.bulk-send', $run) }}">
                @csrf
                <div class="pd-actions">
                    <button type="submit" class="pd-btn pd-btn-cta" onclick="return confirm('Send payslips to all {{ $payslips->count() }} employees?')">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                        Send All {{ $payslips->count() }} Payslips
                    </button>
                </div>
            </form>
        @else
            <div class="pd-callout pd-callout-warn">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                <div>
                    <strong>{{ count($issues) }} employee(s) have issues that may prevent delivery.</strong>
                    <div class="pd-callout-sub">Review the issues below. You can still send to valid employees only.</div>
                </div>
            </div>
        @endif

        {{-- Issues table --}}
        @if(!empty($issues))
        <div class="pd-card" style="margin-top:16px">
            <table class="pd-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Number</th>
                        <th>Issues</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($issues as $item)
                    <tr class="pd-row-warn">
                        <td class="pd-bold">{{ $item['employee']->full_name ?? '—' }}</td>
                        <td class="pd-mono">{{ $item['employee']->employee_number ?? '—' }}</td>
                        <td>
                            @foreach($item['issues'] as $issue)
                                <span class="pd-issue">{{ $issue }}</span>
                            @endforeach
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        {{-- Ready payslips --}}
        <div class="pd-card" style="margin-top:16px">
            <div class="pd-card-h">Payslips Ready to Send ({{ $payslips->where('status', 'finalized')->count() }})</div>
            <table class="pd-table">
                <thead>
                    <tr>
                        <th>Employee</th>
                        <th>Payslip #</th>
                        <th class="num">Net Pay</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($payslips as $p)
                    <tr>
                        <td class="pd-bold">{{ $p->employee->full_name ?? '—' }}</td>
                        <td class="pd-mono">{{ $p->payslip_number }}</td>
                        <td class="num">{{ format_number($p->net_pay) }}</td>
                        <td>
                            @if($p->status === 'finalized')
                                <span class="pd-badge pd-badge-pending">Ready</span>
                            @elseif($p->status === 'sent')
                                <span class="pd-badge pd-badge-sent">Sent</span>
                            @else
                                <span class="pd-badge">{{ $p->status }}</span>
                            @endif
                        </td>
                        <td>
                            @if($p->status === 'finalized')
                                <form method="POST" action="{{ route('accounting.payroll.distribution.send', $p) }}" style="display:inline">
                                    @csrf
                                    <button type="submit" class="pd-btn pd-btn-ghost pd-btn-xs">Send Now</button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="pd-empty">No finalized payslips to send.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>
