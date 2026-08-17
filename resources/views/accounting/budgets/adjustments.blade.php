<x-app-layout>
    <div class="bu-wrap max-w-8xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
        <div class="page-head">
            <div>
                <h1 style="font-size:21px;font-weight:800;letter-spacing:-.02em;color:var(--ink)">Budget Adjustments</h1>
                <div class="sub">Review and manage budget adjustment requests.</div>
            </div>
        </div>

        <div class="bu-card" style="margin-top:20px">
            <div class="bu-pad">
                <form method="GET" action="{{ route('accounting.budgets.adjustments') }}" style="display:flex;gap:8px;flex-wrap:wrap;align-items:center">
                    <select name="status" class="in">
                        <option value="">All Statuses</option>
                        <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending_approval" {{ request('status') === 'pending_approval' ? 'selected' : '' }}>Pending</option>
                        <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Approved</option>
                        <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rejected</option>
                    </select>
                    <select name="adjustment_type" class="in">
                        <option value="">All Types</option>
                        <option value="increase" {{ request('adjustment_type') === 'increase' ? 'selected' : '' }}>Increase</option>
                        <option value="decrease" {{ request('adjustment_type') === 'decrease' ? 'selected' : '' }}>Decrease</option>
                        <option value="transfer" {{ request('adjustment_type') === 'transfer' ? 'selected' : '' }}>Transfer</option>
                    </select>
                    <button type="submit" class="bu-btn bu-btn-ghost">Filter</button>
                </form>
            </div>

            <div class="bu-pad" style="padding-top:6px">
                <div class="bu-li-wrap">
                    <table>
                        <thead><tr><th>Code</th><th>Budget</th><th>Type</th><th class="num">Amount</th><th>Reason</th><th>Status</th><th>Requested</th><th></th></tr></thead>
                        <tbody>
                            @forelse($adjustments as $adj)
                                <tr>
                                    <td style="font-family:var(--font-mono);font-size:12px">{{ $adj->code }}</td>
                                    <td>{{ $adj->budget?->name }}</td>
                                    <td><span class="bu-badge bu-b-{{ $adj->adjustment_type === 'increase' ? 'app' : ($adj->adjustment_type === 'decrease' ? 'pend' : 'lock') }}">{{ ucfirst($adj->adjustment_type) }}</span></td>
                                    <td class="num">{{ number_format($adj->amount, 2) }}</td>
                                    <td style="font-size:12px;color:var(--muted);max-width:200px">{{ Str::limit($adj->reason, 60) }}</td>
                                    <td><span class="bu-badge bu-b-{{ $adj->statusColor() }}">{{ $adj->statusLabel() }}</span></td>
                                    <td style="font-size:12px;color:var(--muted)">{{ $adj->created_at->format('M d, Y') }}</td>
                                    <td>
                                        @if($adj->status === 'pending_approval' && $canApprove)
                                            <form method="POST" action="{{ route('accounting.budgets.adjustments.approve', $adj) }}" style="display:inline">
                                                @csrf
                                                <button type="submit" class="bu-btn bu-btn-cta bu-btn-sm">Approve</button>
                                            </form>
                                            <form method="POST" action="{{ route('accounting.budgets.adjustments.reject', $adj) }}" style="display:inline">
                                                @csrf
                                                <button type="submit" class="bu-btn bu-btn-danger-o bu-btn-sm">Reject</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="8" class="bu-empty">No adjustments found.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:12px">{{ $adjustments->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
