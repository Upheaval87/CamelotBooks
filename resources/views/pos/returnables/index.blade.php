<x-app-layout>
    <div class="pos">
        <div class="wrap">
            <div class="pos-page-head">
                <div>
                    <h1>Bottle Returnables</h1>
                    <div class="pos-sub">Intake · BRR issuance · settlement</div>
                </div>
            </div>

            <div class="pos-kpis" style="grid-template-columns:repeat(4,1fr)">
                <div class="pos-kpi pos-kpi-hero">
                    <div class="pos-kpi-l">Today's Intakes</div>
                    <div class="pos-kpi-v">{{ $stats['today_count'] }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Today's Credits</div>
                    <div class="pos-kpi-v">{{ format_money($stats['today_total']) }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Pending Settlement</div>
                    <div class="pos-kpi-v">{{ $stats['pending_count'] }}</div>
                </div>
                <div class="pos-kpi">
                    <div class="pos-kpi-l">Settled</div>
                    <div class="pos-kpi-v">{{ $stats['settled_count'] }}</div>
                </div>
            </div>

            <div class="pos-shell">
                <div class="pos-grid2">
                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">1 · Record Bottle Return</span>
                        </div>
                        <div class="pos-pad">
                            <form method="POST" action="{{ route('pos.returnables.store') }}" id="returnable-form">
                                @csrf
                                <div class="pos-g2">
                                    <div class="pos-f">
                                        <label>Product / Bottle</label>
                                        <select name="product_id" class="pos-in" required>
                                            <option value="">— Select Product —</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="pos-f">
                                        <label>Quantity</label>
                                        <input type="number" name="quantity" class="pos-in" min="1" max="9999" value="{{ old('quantity', 1) }}" required>
                                    </div>
                                </div>
                                <div class="pos-g2" style="margin-top:12px">
                                    <div class="pos-f">
                                        <label>Credit Amount</label>
                                        <input type="number" name="credit_amount" class="pos-in" min="0" step="0.01" value="{{ old('credit_amount', '0.00') }}" required>
                                        <div style="font-size:11px;color:var(--pos-muted);margin-top:4px">Dr 1320 / Cr 2300 on settlement</div>
                                    </div>
                                    <div class="pos-f">
                                        <label>Notes</label>
                                        <input type="text" name="notes" class="pos-in" maxlength="500" value="{{ old('notes') }}" placeholder="Optional notes">
                                    </div>
                                </div>
                                <div style="display:flex;gap:8px;margin-top:16px">
                                    <button type="submit" class="pos-btn pos-btn-sec">Issue BRR</button>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">2 · Bottle Return Register</span>
                        </div>
                        <div class="pos-pad">
                            <div class="pos-note pos-note-info" style="margin-bottom:16px">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                <span>Each intake records a bottle/container return. On settlement the system posts <strong>Dr 1320 (Inventory)</strong> / <strong>Cr 2300 (Bottle Returnables)</strong>.</span>
                            </div>
                            @if($recentReturnables->isEmpty())
                                <div class="pos-empty">
                                    <h3>No returnables yet</h3>
                                    <p>Record bottle returns using the form on the left.</p>
                                </div>
                            @else
                                <div class="pos-li-wrap">
                                    <table class="pos-tbl" style="min-width:0">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Product</th>
                                                <th class="num">Qty</th>
                                                <th class="num">Credit</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($recentReturnables as $ret)
                                                <tr>
                                                    <td class="pos-em">{{ \Carbon\Carbon::parse($ret->created_at)->format('d M H:i') }}</td>
                                                    <td class="pos-bold">{{ $ret->product_id }}</td>
                                                    <td class="num">{{ $ret->quantity }}</td>
                                                    <td class="num pos-bold">{{ format_money($ret->credit_amount) }}</td>
                                                    <td>
                                                        @if($ret->status === 'settled')
                                                            <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Settled</span>
                                                        @elseif($ret->status === 'pending')
                                                            <span class="pos-badge pos-badge-pend"><span class="pos-bdot"></span>Pending</span>
                                                        @else
                                                            <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>{{ ucfirst($ret->status) }}</span>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('pos.receipts.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><polyline points="14,2 14,8 20,8"/></svg>
                            Receipts Register
                        </a>
                        <a href="{{ route('pos.register.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="4" width="20" height="16" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                            View Register
                        </a>
                    </div>
                    <div class="pos-rail-card">
                        <h3>How it Works</h3>
                        <div style="font-size:12.5px;color:var(--pos-muted);line-height:1.5">
                            <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">1.</strong> Record bottle return → BRR issued</p>
                            <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">2.</strong> Customer brings back bottles</p>
                            <p><strong style="color:var(--pos-ink)">3.</strong> Settle → Dr 1320 / Cr 2300</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>