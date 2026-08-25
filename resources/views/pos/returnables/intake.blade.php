<x-app-layout>
    <div class="pos">
        <div class="wrap">
            <div class="pos-page-head">
                <div>
                    <h1>New Bottle Intake</h1>
                    <div class="pos-sub">Issue a BRR receipt and create container credits</div>
                </div>
            </div>

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
                            <span class="pos-step">Record Bottle/Container Intake</span>
                        </div>
                        <div class="pos-pad">
                            <form method="POST" action="{{ route('pos.returnables.store-intake') }}">
                                @csrf

                                <div class="pos-g2">
                                    <div class="pos-f">
                                        <label>Product / Container <span style="color:red">*</span></label>
                                        <select name="product_id" class="pos-in" required>
                                            <option value="">— Select Product —</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }} ({{ $product->sku }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <div style="font-size:11px;color:var(--pos-muted);margin-top:4px">Must have returnable packaging configured in Items</div>
                                    </div>
                                    <div class="pos-f">
                                        <label>Customer <span style="font-weight:normal;color:var(--pos-muted)">(optional)</span></label>
                                        <x-scoped-search-field entity="customer" name="customer_id" placeholder="Link to a customer..." />
                                    </div>
                                </div>

                                <div class="pos-g2" style="margin-top:12px">
                                    <div class="pos-f">
                                        <label>Bottle Count <span style="color:red">*</span></label>
                                        <input type="number" name="bottle_count" class="pos-in" min="1" max="9999" value="{{ old('bottle_count', 1) }}" required>
                                        <div style="font-size:11px;color:var(--pos-muted);margin-top:4px">Number of containers returned</div>
                                    </div>
                                    <div class="pos-f">
                                        <label>Notes <span style="font-weight:normal;color:var(--pos-muted)">(optional)</span></label>
                                        <input type="text" name="notes" class="pos-in" maxlength="500" value="{{ old('notes') }}" placeholder="Condition, reason, etc.">
                                    </div>
                                </div>

                                <div class="pos-note pos-note-info" style="margin-top:16px">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    <span>The system will compute the deposit value from the item's returnable packaging. Journal: <strong>Dr 1320 Returnable Containers</strong> / <strong>Cr 2350 Bottle Credits Liability</strong>.</span>
                                </div>

                                <div style="display:flex;gap:8px;margin-top:20px">
                                    <button type="submit" class="pos-btn pos-btn-sec">Issue BRR Receipt</button>
                                    <a href="{{ route('pos.returnables.index') }}" class="pos-btn pos-btn-ghost">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="pos-card">
                        <div class="pos-card-h">
                            <span class="pos-step">How it Works</span>
                        </div>
                        <div class="pos-pad">
                            <div class="pos-note pos-note-info" style="margin-bottom:12px">
                                <span><strong>Two-step workflow:</strong></span>
                            </div>
                            <div style="font-size:12.5px;color:var(--pos-muted);line-height:1.5">
                                <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">1.</strong> Customer returns empty containers → <strong>Issue BRR</strong> (this page)</p>
                                <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">2.</strong> Customer makes a purchase → <strong>Apply bottle credit</strong> at checkout</p>
                                <p style="margin-bottom:8px;margin-top:12px"><strong style="color:var(--pos-ink)">On intake:</strong> DR 1320 Container Assets / CR 2350 Liability</p>
                                <p style="margin-bottom:8px"><strong style="color:var(--pos-ink)">On redemption:</strong> DR 2350 Liability / CR 1320 Container Assets</p>
                                <p><strong style="color:var(--pos-ink)">On expiry:</strong> DR 1320 Container Assets / CR 4050 Deposit Revenue</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('pos.returnables.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/></svg>
                            Returnables Register
                        </a>
                        <a href="{{ route('pos.sales.checkout') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 002 1.61h9.72a2 2 0 002-1.61L23 6H6"/></svg>
                            POS Checkout
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
