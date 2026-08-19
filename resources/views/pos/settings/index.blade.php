<x-app-layout>
    <div class="pos">
        <div class="wrap">
            {{-- Page Head --}}
            <div class="pos-page-head">
                <div>
                    <h1>POS Settings</h1>
                    <div class="pos-sub">Store · devices · payment methods · preferences</div>
                </div>
            </div>

            {{-- Main Content + Rail --}}
            <div class="pos-shell">
                <div>
                    {{-- Section: Registers / Terminals --}}
                    <div class="pos-card" style="margin-bottom:16px">
                        <div class="pos-card-h">
                            <span class="pos-step">1 · Registers &amp; Terminals</span>
                            <div class="pos-right">
                                <a href="{{ route('pos.terminals.index') }}" class="pos-btn pos-btn-ghost pos-btn-xs">Manage Terminals</a>
                            </div>
                        </div>
                <div class="pos-li-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>Identifier</th>
                                <th>Name</th>
                                <th>Branch</th>
                                <th class="num">PIN Timeout</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($terminals as $terminal)
                                <tr>
                                    <td class="pos-mono pos-em">{{ $terminal->identifier }}</td>
                                    <td class="pos-bold">{{ $terminal->name }}</td>
                                    <td class="pos-em">{{ $terminal->branch?->name ?? '—' }}</td>
                                    <td class="num">
                                        {{ $terminal->cashier_pin_timeout_minutes > 0 ? $terminal->cashier_pin_timeout_minutes . ' min' : 'Disabled' }}
                                    </td>
                                    <td>
                                        @if($terminal->is_active)
                                            <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Active</span>
                                        @else
                                            <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="pos-empty">
                                            <h3>No terminals configured</h3>
                                            <p>Add a terminal to start processing POS sales.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Section: Payment Methods --}}
            <div class="pos-card" style="margin-bottom:16px">
                <div class="pos-card-h">
                    <span class="pos-step">2 · Payment Methods</span>
                    <div class="pos-right">
                        <a href="{{ route('pos.payment-methods.index') }}" class="pos-btn pos-btn-ghost pos-btn-xs">Manage Methods</a>
                    </div>
                </div>
                <div class="pos-li-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Type</th>
                                <th>Clearing Account</th>
                                <th>Ref Required</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($paymentMethods as $pm)
                                <tr>
                                    <td class="pos-bold">{{ $pm->name }}</td>
                                    <td><span class="pos-tchip pos-tchip-pay">{{ $pm->type }}</span></td>
                                    <td class="pos-em">{{ $pm->clearingAccount?->name ?? '—' }}</td>
                                    <td>{{ $pm->requires_reference ? 'Yes' : 'No' }}</td>
                                    <td>
                                        @if($pm->is_active)
                                            <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Active</span>
                                        @else
                                            <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="pos-empty">
                                            <h3>No payment methods configured</h3>
                                            <p>Add payment methods to accept different payment types.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Section: Store Preferences --}}
            <div class="pos-card" style="margin-bottom:16px">
                <div class="pos-card-h">
                    <span class="pos-step">3 · Store Preferences</span>
                </div>
                <div class="pos-pad">
                    <div class="pos-g3">
                        <div class="pos-f">
                            <label>Receipt Format</label>
                            <select class="pos-in">
                                <option>Thermal 80mm</option>
                                <option>A5</option>
                            </select>
                        </div>
                        <div class="pos-f">
                            <label>Receipt Numbering</label>
                            <input class="pos-in" value="RCP-{seq}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Approval Limits</label>
                            <input class="pos-in" value="Discount > 10% → Supervisor" readonly>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Section: GL Accounts --}}
            <div class="pos-card" style="margin-bottom:16px">
                <div class="pos-card-h">
                    <span class="pos-step">4 · GL Account Mapping</span>
                </div>
                <div class="pos-pad">
                    <div class="pos-g2">
                        <div class="pos-f">
                            <label>Sales Revenue Account</label>
                            <input class="pos-in" value="4000 · Sales Revenue" readonly>
                        </div>
                        <div class="pos-f">
                            <label>COGS Account</label>
                            <input class="pos-in" value="5000 · Cost of Goods Sold" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Inventory Account</label>
                            <input class="pos-in" value="1300 · Inventory" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Cash-in-Drawer Account</label>
                            <input class="pos-in" value="1060 · Cash-in-Drawer" readonly>
                        </div>
                    </div>
                    <div class="pos-note pos-note-info" style="margin-top:16px">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none"><circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/><path d="M12 8v4M12 16h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                        <span>GL account mapping is managed in the Accounting Settings module. <a href="{{ route('system-settings.index') }}" style="color:var(--pos-sec);font-weight:700;text-decoration:none">Open Settings →</a></span>
                    </div>
                </div>
            </div>

            {{-- Section: Hardware --}}
            <div class="pos-card">
                <div class="pos-card-h">
                    <span class="pos-step">5 · Hardware</span>
                </div>
                <div class="pos-pad">
                    <div class="pos-g3">
                        <div class="pos-toggle-row">
                            <div style="width:100%">
                                <div style="font-size:13px;font-weight:700;color:var(--pos-ink)">Barcode Scanner</div>
                                <div style="font-size:11px;color:var(--pos-muted)">USB / Bluetooth scanner</div>
                            </div>
                        </div>
                        <div class="pos-toggle-row">
                            <div style="width:100%">
                                <div style="font-size:13px;font-weight:700;color:var(--pos-ink)">Receipt Printer</div>
                                <div style="font-size:11px;color:var(--pos-muted)">Thermal 80mm / 58mm</div>
                            </div>
                        </div>
                        <div class="pos-toggle-row">
                            <div style="width:100%">
                                <div style="font-size:13px;font-weight:700;color:var(--pos-ink)">Cash Drawer</div>
                                <div style="font-size:11px;color:var(--pos-muted)">Auto-open on receipt print</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="pos-rail">
                    <div class="pos-rail-card">
                        <h3>Quick Nav</h3>
                        <a href="{{ route('pos.terminals.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/></svg>
                            Terminals
                        </a>
                        <a href="{{ route('pos.payment-methods.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><rect x="1" y="4" width="22" height="16" rx="2"/><line x1="1" y1="10" x2="23" y2="10"/></svg>
                            Payment Methods
                        </a>
                        <a href="{{ route('system-settings.index') }}" class="pos-rail-link">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 11-2.83 2.83l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 11-4 0v-.09A1.65 1.65 0 009 19.4a1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 11-2.83-2.83l.06-.06A1.65 1.65 0 004.68 15a1.65 1.65 0 00-1.51-1H3a2 2 0 110-4h.09A1.65 1.65 0 004.6 9a1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 112.83-2.83l.06.06A1.65 1.65 0 009 4.68a1.65 1.65 0 001-1.51V3a2 2 0 114 0v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 112.83 2.83l-.06.06A1.65 1.65 0 0019.32 9a1.65 1.65 0 001.51 1H21a2 2 0 110 4h-.09a1.65 1.65 0 00-1.51 1z"/></svg>
                            Accounting Settings
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
