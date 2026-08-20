<x-app-layout>
    <div class="pd max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

        <nav class="pd-crumbs">
            <a href="{{ route('accounting.payroll.dashboard') }}">Payroll</a> ›
            <a href="{{ route('accounting.payroll.runs.show', $run) }}">Run {{ $run->run_number }}</a> ›
            <span class="here">Distribution</span>
        </nav>

        <div class="pd-page-head">
            <div>
                <h1>Pay Run Distribution</h1>
                <div class="pd-sub">Run {{ $run->run_number }} — {{ $run->period_label }} · {{ $run->pay_date?->format('d M Y') ?? '—' }}</div>
            </div>
            <div class="pd-cluster">
                <a href="{{ route('accounting.payroll.runs.show', $run) }}" class="pd-btn pd-btn-ghost pd-btn-sm">← Back to Run</a>
                @if($status['draft'] > 0)
                    <form method="POST" action="{{ route('accounting.payroll.distribution.generate', $run) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="pd-btn pd-btn-sec pd-btn-sm">Generate Payslips</button>
                    </form>
                @endif
                @if($status['draft'] === 0 && $status['finalized'] > 0)
                    <form method="POST" action="{{ route('accounting.payroll.distribution.finalize', $run) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="pd-btn pd-btn-sec pd-btn-sm" onclick="return confirm('Finalize all payslips? They cannot be edited after this.')">Finalize All</button>
                    </form>
                @endif
                @if($status['finalized'] > 0)
                    <a href="{{ route('accounting.payroll.distribution.validate', $run) }}" class="pd-btn pd-btn-cta pd-btn-sm">Send All Payslips</a>
                @endif
            </div>
        </div>

        {{-- Status chips --}}
        <div class="pd-chips">
            <div class="pd-chip">
                <div class="pd-chip-l">Total Payslips</div>
                <div class="pd-chip-v">{{ $status['total'] }}</div>
            </div>
            <div class="pd-chip">
                <div class="pd-chip-l">Draft</div>
                <div class="pd-chip-v">{{ $status['draft'] }}</div>
            </div>
            <div class="pd-chip">
                <div class="pd-chip-l">Finalized</div>
                <div class="pd-chip-v">{{ $status['finalized'] }}</div>
            </div>
            <div class="pd-chip">
                <div class="pd-chip-l">Sent</div>
                <div class="pd-chip-v pd-chip-v-teal">{{ $status['sent'] }}</div>
            </div>
            <div class="pd-chip">
                <div class="pd-chip-l">Viewed</div>
                <div class="pd-chip-v pd-chip-v-mint">{{ $status['viewed'] }}</div>
            </div>
            @if($status['failed'] > 0)
                <div class="pd-chip pd-chip-warn">
                    <div class="pd-chip-l">Failed</div>
                    <div class="pd-chip-v">{{ $status['failed'] }}</div>
                </div>
            @endif
        </div>

        {{-- Action cards --}}
        <div class="pd-grid">
            <div class="pd-card">
                <div class="pd-card-ic pd-card-ic-teal">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                </div>
                <h3>Generate Payslips</h3>
                <p>Create payslip records from payroll run calculations for each employee.</p>
                <div class="pd-card-meta">{{ $status['draft'] }} draft · {{ $status['skipped'] ?? 0 }} existing</div>
            </div>

            <div class="pd-card">
                <div class="pd-card-ic pd-card-ic-amber">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Finalize</h3>
                <p>Lock payslips from further editing and prepare for distribution.</p>
                <div class="pd-card-meta">{{ $status['finalized'] }} finalized</div>
            </div>

            <div class="pd-card">
                <div class="pd-card-ic pd-card-ic-mint">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                </div>
                <h3>Send via Email</h3>
                <p>Email finalized payslips to employees with password-protected PDFs.</p>
                <div class="pd-card-meta">{{ $status['sent'] + $status['viewed'] }} delivered</div>
            </div>

            <div class="pd-card">
                <div class="pd-card-ic pd-card-ic-steel">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                </div>
                <h3>Audit Trail</h3>
                <p>Review the complete distribution history for this pay run.</p>
                <a href="{{ route('accounting.payroll.distribution.audit', ['run_id' => $run->id]) }}" class="pd-card-link">View audit log →</a>
            </div>
        </div>

        {{-- Payslip list --}}
        <div class="pd-card" style="margin-top:20px">
            <h3 style="margin-bottom:16px">Payslips ({{ $payslips->count() }})</h3>
            <div class="pd-table-wrap">
                <table class="pd-table">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Payslip #</th>
                            <th>Status</th>
                            <th class="num">Gross</th>
                            <th class="num">Deductions</th>
                            <th class="num">Net Pay</th>
                            <th style="text-align:right">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($payslips as $p)
                            <tr>
                                <td style="font-weight:700">{{ $p->employee?->full_name ?? '—' }}</td>
                                <td class="pd-mono">{{ $p->payslip_number }}</td>
                                <td>
                                    <span class="pd-badge pd-badge-{{ $p->status }}">{{ $p->status_label }}</span>
                                </td>
                                <td class="pd-numr">{{ format_number($p->gross_pay) }}</td>
                                <td class="pd-numr">{{ format_number($p->total_deductions) }}</td>
                                <td class="pd-numr pd-green">{{ format_number($p->net_pay) }}</td>
                                <td class="pd-actions">
                                    @if($p->status === 'finalized')
                                        <form method="POST" action="{{ route('accounting.payroll.distribution.send', $p) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="pd-ibtn" title="Send">
                                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/><polyline points="22,6 12,13 2,6"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="pd-empty">No payslips generated yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
