<x-app-layout>
    <div class="pos">
        <div class="wrap">
            <div class="pos-page-head">
                <div>
                    <h1>BRR-{{ $returnable->brr_number }}</h1>
                    <div class="pos-sub">Bottle Return Receipt · {{ $returnable->created_at->format('d M Y H:i') }}</div>
                </div>
                <div style="display:flex;gap:8px">
                    <a href="{{ route('pos.returnables.print', $returnable->id) }}" class="pos-btn pos-btn-ghost" target="_blank">Print</a>
                    @if($returnable->isVoidable())
                        <form method="POST" action="{{ route('pos.returnables.void', $returnable->id) }}" style="display:inline" onsubmit="return confirm('Void this BRR receipt? The journal entry will be reversed.')">
                            @csrf
                            <button type="submit" class="pos-btn pos-btn-danger">Void Receipt</button>
                        </form>
                    @endif
                    <a href="{{ route('pos.returnables.index') }}" class="pos-btn pos-btn-ghost">Back to Register</a>
                </div>
            </div>

            @if(session('success'))
                <div class="pos-note pos-note-success mb-4">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M9 12l2 2 4-4" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    <span>{{ session('success') }}</span>
                </div>
            @endif

            @if(session('error'))
                <div class="pos-note pos-note-error mb-4">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                    <span>{{ session('error') }}</span>
                </div>
            @endif

            <div class="pos-shell">
                <div class="pos-grid2">
                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">Receipt Details</span>
                            <span class="pos-badge pos-badge-{{ $returnable->status_color }}">
                                <span class="pos-bdot"></span>
                                {{ $returnable->status_label }}
                            </span>
                        </div>
                        <div class="pos-pad">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">BRR Number</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">BRR-{{ $returnable->brr_number }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Intake Number</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">{{ $returnable->intake_number }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Product</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">{{ $returnable->product?->name ?? '—' }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Customer</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">{{ $returnable->customer?->name ?? 'Not linked' }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Branch</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">{{ $returnable->branch?->name ?? '—' }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Recorded By</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">{{ $returnable->createdBy?->name ?? '—' }}</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">Credit Summary</span>
                        </div>
                        <div class="pos-pad">
                            <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px">
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Bottle Count</div>
                                    <div style="font-size:22px;font-weight:700;color:var(--pos-ink)">{{ $returnable->bottle_count }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Value Each</div>
                                    <div style="font-size:22px;font-weight:700;color:var(--pos-ink)">{{ format_money($returnable->value_each) }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Total Credit</div>
                                    <div style="font-size:22px;font-weight:700;color:var(--pos-ink)">{{ format_money($returnable->credit_amount) }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Redeemed</div>
                                    <div style="font-size:22px;font-weight:700;color:var(--pos-ink)">{{ format_money($returnable->remaining_credit) }} remaining</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Expiry Date</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">{{ $returnable->expiry_date?->format('d M Y') ?? 'No expiry' }}</div>
                                </div>
                                <div>
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Redeemed At</div>
                                    <div style="font-size:15px;font-weight:600;color:var(--pos-ink)">{{ $returnable->redeemed_at?->format('d M Y H:i') ?? 'Not yet' }}</div>
                                </div>
                            </div>
                            @if($returnable->notes)
                                <div style="margin-top:16px;padding-top:16px;border-top:1px solid var(--pos-line)">
                                    <div style="font-size:11px;color:var(--pos-muted);text-transform:uppercase;letter-spacing:0.05em;font-weight:600">Notes</div>
                                    <div style="font-size:13px;color:var(--pos-ink);margin-top:4px">{{ $returnable->notes }}</div>
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
                        <a href="{{ route('pos.returnables.print', $returnable->id) }}" class="pos-rail-link" target="_blank">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                            Print BRR Receipt
                        </a>
                        <a href="{{ route('pos.returnables.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                            Back to Register
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
