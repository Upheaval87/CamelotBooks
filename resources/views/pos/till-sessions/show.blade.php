<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
        $variance = $session->variance ?? 0;
        $varClass = $variance > 0 ? 'pos-pos' : ($variance < 0 ? 'pos-neg' : '');
    @endphp

    <div class="pos">
        <div class="pos-wrap">
            <div class="pos-page-head">
                <div>
                    <h1 class="pos-h1">Till Session #{{ $session->id }}</h1>
                </div>
                <div style="display:flex;gap:8px;align-items:center">
                    @if($session->isOpen())
                        <span class="pos-badge pos-badge--open">
                            <span class="pos-dot" style="background:var(--pos-green)"></span> Open
                        </span>
                    @else
                        <span class="pos-badge pos-badge--closed">
                            <span class="pos-dot" style="background:var(--pos-faint)"></span> Closed
                        </span>
                    @endif
                    <a href="{{ route('pos.till-sessions.index') }}" class="pos-btn pos-btn-ghost" style="font-size:13px">
                        ← Back to Sessions
                    </a>
                </div>
            </div>

            <div class="pos-shell" style="grid-template-columns:1fr">
                <div class="pos-main">

                    {{-- Session Info --}}
                    <div class="pos-card" style="margin-bottom:20px">
                        <div class="pos-sec-head">
                            <div class="pos-sec-ic pos-sec-ic--steel">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 8v4l3 3"/><circle cx="12" cy="12" r="9"/></svg>
                            </div>
                            <span class="pos-sec-title">Session Details</span>
                        </div>
                        <div class="pos-kv-grid" style="padding:16px 24px;grid-template-columns:repeat(2, 1fr)">
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Terminal</span>
                                <span class="pos-kv-val">{{ $session->terminal?->identifier ?? '—' }}</span>
                            </div>
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Cashier</span>
                                <span class="pos-kv-val">{{ $session->user?->name ?? '—' }}</span>
                            </div>
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Opened At</span>
                                <span class="pos-kv-val">{{ $session->opened_at?->format('M d, Y H:i') ?? '—' }}</span>
                            </div>
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Closed At</span>
                                <span class="pos-kv-val">{{ $session->closed_at?->format('M d, Y H:i') ?? '—' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Cash Summary --}}
                    <div class="pos-card" style="margin-bottom:20px">
                        <div class="pos-sec-head">
                            <div class="pos-sec-ic pos-sec-ic--mint">
                                <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M12 1v22M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
                            </div>
                            <span class="pos-sec-title">Cash Summary</span>
                        </div>
                        <div class="pos-kv-grid" style="padding:16px 24px;grid-template-columns:repeat(2, 1fr)">
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Opening Float</span>
                                <span class="pos-kv-val">{{ $cs }}{{ format_number($session->opening_float) }}</span>
                            </div>
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Expected Cash</span>
                                <span class="pos-kv-val">{{ $session->expected_cash !== null ? $cs . format_number($session->expected_cash) : '—' }}</span>
                            </div>
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Actual Cash Count</span>
                                <span class="pos-kv-val">{{ $session->actual_cash_count !== null ? $cs . format_number($session->actual_cash_count) : '—' }}</span>
                            </div>
                            <div class="pos-kv">
                                <span class="pos-kv-lbl">Variance</span>
                                <span class="pos-kv-val {{ $varClass }}" style="font-size:18px;font-weight:800">
                                    @if($session->variance !== null)
                                        {{ $variance >= 0 ? '+' : '' }}{{ $cs }}{{ format_number($variance) }}
                                    @else
                                        —
                                    @endif
                                </span>
                            </div>
                        </div>
                    </div>

                    {{-- Journal Entry --}}
                    @if($session->journalEntry)
                        <div class="pos-card">
                            <div class="pos-sec-head">
                                <div class="pos-sec-ic pos-sec-ic--steel">
                                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                </div>
                                <span class="pos-sec-title">Journal Entry</span>
                                <a href="{{ route('accounting.journal-entries.show', $session->journalEntry) }}" class="pos-btn pos-btn-ghost pos-btn-sm" style="margin-left:auto;font-size:12px">
                                    View Entry →
                                </a>
                            </div>
                            <div class="overflow-x-auto">
                                <table class="record-datasheet">
                                    <thead>
                                        <tr>
                                            <th>Account</th>
                                            <th class="text-right">Debit ({{ $cs }})</th>
                                            <th class="text-right">Credit ({{ $cs }})</th>
                                            <th>Description</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($session->journalEntry->lines as $line)
                                            <tr>
                                                <td class="font-semibold">{{ $line->account?->code }} – {{ $line->account?->name }}</td>
                                                <td class="numeric">
                                                    {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                                </td>
                                                <td class="numeric">
                                                    {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                                </td>
                                                <td style="color:var(--pos-muted)">{{ $line->description }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
