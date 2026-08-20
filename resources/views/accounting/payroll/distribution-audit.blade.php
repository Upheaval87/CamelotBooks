<x-app-layout>
    <div class="pd max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <nav class="pd-crumbs">
            <a href="{{ route('accounting.payroll.dashboard') }}">Payroll</a> ›
            <span class="here">Distribution Audit Trail</span>
        </nav>
        <div class="pd-page-head">
            <div>
                <h1>Distribution Audit Trail</h1>
                <div class="pd-sub">Complete history of payslip generation, sending, and viewing events.</div>
            </div>
            <div class="pd-cluster">
                <a href="{{ route('accounting.payroll.distribution.audit.export', request()->query()) }}" class="pd-btn pd-btn-ghost pd-btn-sm">Export CSV</a>
            </div>
        </div>

        <div class="pd-card" style="margin-bottom:16px">
            <form method="GET" action="{{ route('accounting.payroll.distribution.audit') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
                <div class="pd-field" style="margin-bottom:0">
                    <label class="pd-label">Pay Run</label>
                    <select name="run_id" class="pd-select">
                        <option value="">All Runs</option>
                        @foreach($runs as $r)
                            <option value="{{ $r->id }}" {{ request('run_id') == $r->id ? 'selected' : '' }}>{{ $r->run_number }} — {{ $r->period_label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pd-field" style="margin-bottom:0">
                    <label class="pd-label">Employee</label>
                    <select name="employee_id" class="pd-select">
                        <option value="">All Employees</option>
                        @foreach($employees as $emp)
                            <option value="{{ $emp->id }}" {{ request('employee_id') == $emp->id ? 'selected' : '' }}>{{ $emp->full_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="pd-field" style="margin-bottom:0">
                    <label class="pd-label">Action</label>
                    <select name="action" class="pd-select">
                        <option value="">All Actions</option>
                        @foreach(['generated','finalized','sent','bulk_sent','resent','portal_viewed','send_failed','bulk_send_failed'] as $a)
                            <option value="{{ $a }}" {{ request('action') === $a ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $a)) }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="pd-btn pd-btn-sec pd-btn-sm">Filter</button>
                @if(request()->hasAny(['run_id', 'employee_id', 'action']))
                    <a href="{{ route('accounting.payroll.distribution.audit') }}" class="pd-btn pd-btn-ghost pd-btn-sm">Clear</a>
                @endif
            </form>
        </div>

        <div class="pd-card">
            <div class="pd-table-wrap">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th>Date & Time</th>
                            <th>Employee</th>
                            <th>Payslip</th>
                            <th>Action</th>
                            <th>User</th>
                            <th>IP Address</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                        <tr>
                            <td class="pd-mono pd-em">{{ $log->created_at?->format('d M Y H:i:s') ?? '—' }}</td>
                            <td class="pd-bold">{{ $log->employee?->full_name ?? '—' }}</td>
                            <td class="pd-mono">{{ $log->payslip?->payslip_number ?? '—' }}</td>
                            <td>
                                @php
                                    $color = match(true) {
                                        str_contains($log->action, 'failed') => 'danger',
                                        str_contains($log->action, 'sent') => 'sent',
                                        str_contains($log->action, 'viewed') => 'mint',
                                        default => 'pending',
                                    };
                                @endphp
                                <span class="pd-badge pd-badge-{{ $color }}">{{ ucwords(str_replace('_', ' ', $log->action)) }}</span>
                            </td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td class="pd-mono pd-em">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" class="pd-empty">No audit log entries found.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="pd-pagi">
                    <span class="t">Showing {{ $logs->firstItem() }}–{{ $logs->lastItem() }} of {{ $logs->total() }}</span>
                    <div style="display:flex;gap:8px">{{ $logs->links() }}</div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
