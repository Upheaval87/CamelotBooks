<x-app-layout>
    <div class="pos">
        <div class="wrap">
            <div class="pos-page-head">
                <div>
                    <h1>Bottle Returnables</h1>
                    <div class="pos-sub">Intake · BRR issuance · redemption · expiry</div>
                </div>
                <a href="{{ route('pos.returnables.intake') }}" class="pos-btn pos-btn-sec">New Bottle Intake</a>
            </div>

            <div class="pos-kpis" style="grid-template-columns:repeat(4,1fr)">
                <div class="pos-kpi pos-kpi-hero">
                    <div class="pos-kpi-l">Total Intakes</div>
                    <div class="pos-kpi-v">{{ number_format($stats['total_count']) }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Pending</div>
                    <div class="pos-kpi-v">{{ number_format($stats['pending_count']) }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Redeemed</div>
                    <div class="pos-kpi-v">{{ number_format($stats['redeemed_count']) }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Total Credits</div>
                    <div class="pos-kpi-v">{{ format_money($stats['total_credit']) }}</div>
                </div>
            </div>

            <div class="pos-shell">
                <div class="pos-main-col">
                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">Bottle Return Register</span>
                        </div>
                        <div class="pos-pad">
                            {{-- Filters --}}
                            <form method="GET" style="margin-bottom:16px">
                                <div class="pos-g2" style="align-items:end">
                                    <div class="pos-f" style="flex:2">
                                        <input type="text" name="q" class="pos-in" placeholder="Search by BRR#, customer..." value="{{ request('q') }}">
                                    </div>
                                    <div class="pos-f" style="flex:1">
                                        <select name="status" class="pos-in">
                                            <option value="">All Statuses</option>
                                            <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pending</option>
                                            <option value="partially_redeemed" {{ request('status') === 'partially_redeemed' ? 'selected' : '' }}>Partially Redeemed</option>
                                            <option value="redeemed" {{ request('status') === 'redeemed' ? 'selected' : '' }}>Redeemed</option>
                                            <option value="expired" {{ request('status') === 'expired' ? 'selected' : '' }}>Expired</option>
                                            <option value="voided" {{ request('status') === 'voided' ? 'selected' : '' }}>Voided</option>
                                        </select>
                                    </div>
                                    <button type="submit" class="pos-btn pos-btn-sec">Filter</button>
                                </div>
                            </form>

                            @if($returnables->isEmpty())
                                <div class="pos-empty">
                                    <h3>No returnables recorded</h3>
                                    <p>Record a bottle intake to start issuing credits.</p>
                                    <a href="{{ route('pos.returnables.intake') }}" class="pos-btn pos-btn-sec" style="margin-top:12px">New Bottle Intake</a>
                                </div>
                            @else
                                <div class="pos-li-wrap">
                                    <table class="pos-tbl">
                                        <thead>
                                            <tr>
                                                <th>BRR #</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th>Product</th>
                                                <th class="num">Bottles</th>
                                                <th class="num">Credit</th>
                                                <th class="num">Remaining</th>
                                                <th>Expiry</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($returnables as $ret)
                                                <tr>
                                                    <td class="pos-bold pos-em">{{ $ret->brr_number }}</td>
                                                    <td class="pos-em">{{ $ret->created_at->format('d M Y') }}</td>
                                                    <td>{{ $ret->customer?->name ?? '—' }}</td>
                                                    <td>{{ $ret->product?->name ?? '—' }}</td>
                                                    <td class="num">{{ $ret->bottle_count }}</td>
                                                    <td class="num pos-bold">{{ format_money($ret->credit_amount) }}</td>
                                                    <td class="num">{{ format_money($ret->remaining_credit) }}</td>
                                                    <td class="pos-em">{{ $ret->expiry_date?->format('d M Y') ?? 'No limit' }}</td>
                                                    <td>
                                                        <span class="pos-badge pos-badge-{{ $ret->status_color }}">
                                                            <span class="pos-bdot"></span>
                                                            {{ $ret->status_label }}
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <a href="{{ route('pos.returnables.show', $ret->id) }}" class="pos-btn pos-btn-xs pos-btn-ghost">View</a>
                                                        <a href="{{ route('pos.returnables.print', $ret->id) }}" class="pos-btn pos-btn-xs pos-btn-ghost">Print</a>
                                                        @if($ret->isVoidable())
                                                            <form method="POST" action="{{ route('pos.returnables.void', $ret->id) }}" style="display:inline" onsubmit="return confirm('Void this BRR receipt? The journal entry will be reversed.')">
                                                                @csrf
                                                                <button type="submit" class="pos-btn pos-btn-xs pos-btn-danger">Void</button>
                                                            </form>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                                <div style="margin-top:16px">
                                    {{ $returnables->withQueryString()->links() }}
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('pos.returnables.intake') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            New Bottle Intake
                        </a>
                        <a href="{{ route('pos.sales.checkout') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                            POS Checkout
                        </a>
                    </div>
                    <div class="pos-rail-card">
                        <h3>How Credits Work</h3>
                        <div style="font-size:12.5px;color:var(--pos-muted);line-height:1.5">
                            <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">Intake:</strong> Customer returns bottles → BRR issued, credit created</p>
                            <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">Redeem:</strong> At checkout, credit auto-applies when customer is selected</p>
                            <p><strong style="color:var(--pos-ink)">Expiry:</strong> Unredeemed credits expire per the return window</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
