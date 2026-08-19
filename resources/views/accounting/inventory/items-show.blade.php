<x-app-layout>
    <div class="inv-wrap pt-10 pb-6">
        <div class="inv-head">
            <div>
                <h1>{{ $product->name }}</h1>
                <div class="chips" style="display:flex;gap:8px;margin-top:8px;flex-wrap:wrap">
                    @if($product->sku)
                    <span class="mchip" style="display:inline-flex;padding:4px 11px;border-radius:999px;font-size:11px;font-weight:700;background:rgba(17,69,75,.06);border:1px solid rgba(17,69,75,.16);color:var(--muted);font-family:ui-monospace,Menlo,monospace">{{ $product->sku }}</span>
                    @endif
                    <span class="inv-badge inv-badge-{{ $product->is_active ? 'active' : 'inactive' }}"><span class="inv-badge-dot"></span>{{ $product->is_active ? __('Active') : __('Inactive') }}</span>
                    <span class="inv-chip" style="background:rgba(18,143,142,.10);border:1px solid rgba(18,143,142,.35);color:var(--sec,#128F8E)">{{ ucfirst($product->type) }}</span>
                </div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.inventory.items.edit', $product) }}" class="inv-btn inv-btn-ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                    {{ __('Edit') }}
                </a>
                <a href="{{ route('accounting.inventory.items') }}" class="inv-btn inv-btn-ghost">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="15" height="15"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
                    {{ __('Back to Items') }}
                </a>
            </div>
        </div>

        <div class="work" style="display:grid;grid-template-columns:1fr 300px;gap:18px;align-items:start">
            <div>
                {{-- Item Information --}}
                <div class="inv-card mb" style="margin-bottom:16px">
                    <div class="inv-card-h"><h2>{{ __('Item Information') }}</h2></div>
                    <div class="pad" style="padding:20px 24px">
                        <div class="dg" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px 24px">
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Type') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ ucfirst($product->type) }}</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Category') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ $product->itemCategory?->name ?? '—' }}</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Unit of Measure') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ $product->unit_of_measure ?? '—' }}</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Barcode') }}</div><div class="v inv-mono" style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ $product->barcode ?? '—' }}</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Tax Rate') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ $product->tax_rate ?? 0 }}%</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Track Inventory') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:{{ $product->tracked_as_inventory ? 'var(--green,#15803d)' : 'var(--ink)' }}">{{ $product->tracked_as_inventory ? __('Yes') : __('No') }}</div></div>
                            <div class="span3" style="grid-column:1/-1"><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Description') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ $product->description ?: '—' }}</div></div>
                        </div>
                    </div>
                </div>

                {{-- Pricing & GL --}}
                <div class="inv-card mb" style="margin-bottom:16px">
                    <div class="inv-card-h"><h2>{{ __('Pricing & GL') }}</h2></div>
                    <div class="pad" style="padding:20px 24px">
                        <div class="dg" style="display:grid;grid-template-columns:repeat(3,1fr);gap:18px 24px">
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Sales Price') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums">{{ format_money($salesPrice) }}</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Purchase Price') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink);font-variant-numeric:tabular-nums">{{ format_money($purchasePrice) }}</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Reorder Point') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">{{ number_format($reorderPoint, 0) }}</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Income Account') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">@if($product->incomeAccount) <span class="acct inc" style="display:inline-flex;padding:4px 10px;border-radius:9px;font-size:12px;font-weight:700;background:rgba(18,143,142,.10);color:var(--sec,#128F8E)">{{ $product->incomeAccount->code }} &middot; {{ $product->incomeAccount->name }}</span> @else <span style="color:var(--muted)">—</span> @endif</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Expense Account') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:var(--ink)">@if($product->expenseAccount) <span class="acct exp" style="display:inline-flex;padding:4px 10px;border-radius:9px;font-size:12px;font-weight:700;background:rgba(17,69,75,.06);color:var(--muted)">{{ $product->expenseAccount->code }} &middot; {{ $product->expenseAccount->name }}</span> @else <span style="color:var(--muted)">—</span> @endif</div></div>
                            <div><div class="l" style="font-size:10px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint);margin-bottom:5px">{{ __('Margin') }}</div><div class="v" style="font-size:13.5px;font-weight:600;color:{{ $margin >= 0 ? 'var(--green,#15803d)' : 'var(--red-2,#b91c1c)' }}">{{ format_money($margin) }} ({{ $marginPct }}%)</div></div>
                        </div>
                    </div>
                </div>

                {{-- Recent Movements --}}
                <div class="inv-card">
                    <div class="inv-card-h"><h2>{{ __('Recent Movements') }}</h2></div>
                    @if($recentMovements->isEmpty())
                    <div class="pad" style="padding:24px;text-align:center">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" style="width:36px;height:36px;color:var(--faint);margin-bottom:8px"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                        <p style="font-size:13px;font-weight:600;color:var(--muted)">{{ __('No movements recorded yet.') }}</p>
                        <div style="font-size:12px;color:var(--faint);margin-top:4px">{{ __('Movements appear here once stock is received or consumed.') }}</div>
                    </div>
                    @else
                    <div class="inv-tbl-wrap">
                        <table class="inv-tbl">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Type') }}</th>
                                    <th>{{ __('Ref') }}</th>
                                    <th class="num">{{ __('Qty') }}</th>
                                    <th class="num">{{ __('Value') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach($recentMovements as $m)
                                @php
                                    $srcType = class_basename($m->source_type ?? '');
                                    $isIn = in_array($srcType, ['Bill', 'GoodsReceivedNote', 'SalesReceipt', 'PosReturnable', 'InventoryAdjustment', 'StockCount', 'AssemblyBuild']);
                                    $typeLabel = $isIn ? 'IN' : 'OUT';
                                    $typeDesc = match($srcType) {
                                        'Bill' => 'Purchase',
                                        'GoodsReceivedNote' => 'GRN',
                                        'SalesReceipt' => 'Sale',
                                        'PosReturnable' => 'Return',
                                        'InventoryAdjustment' => 'Adjustment',
                                        'InventoryTransfer' => 'Transfer',
                                        'StockCount' => 'Stock Count',
                                        'AssemblyBuild' => 'Build',
                                        default => $srcType,
                                    };
                                    $qty = $m->quantity_remaining ?? 0;
                                    $value = $qty * ($m->unit_cost ?? 0);
                                @endphp
                                <tr>
                                    <td style="color:var(--muted)">{{ \Carbon\Carbon::parse($m->date)->format('d M Y') }}</td>
                                    <td><span class="inv-pill {{ $isIn ? 'inv-pill-act' : '' }}" @unless($isIn) style="background:rgba(185,28,28,.08);color:var(--red-2,#b91c1c)" @endunless>{{ $typeLabel }} &middot; {{ $typeDesc }}</span></td>
                                    <td class="inv-mono">{{ $srcType }}</td>
                                    <td class="inv-num" style="color:{{ $isIn ? 'var(--green,#15803d)' : 'var(--red-2,#b91c1c)' }}">{{ $isIn ? '+' : '−' }}{{ number_format($qty, 0) }}</td>
                                    <td class="inv-num">{{ format_money($value) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endif
                </div>
            </div>

            {{-- Side panel --}}
            <aside>
                {{-- Stock Summary --}}
                <div class="inv-card mb" style="margin-bottom:16px">
                    <div class="pad" style="padding:20px 24px">
                        <div class="inv-card-h" style="padding:0 0 12px;border-bottom:none"><h2 style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--faint)">{{ __('Stock Summary') }}</h2></div>
                        <div class="statbox" @if($isOut) style="border-left:3px solid var(--red-2,#b91c1c);border:1px solid var(--border);border-left:3px solid var(--red-2,#b91c1c);border-radius:13px;padding:14px 16px;background:rgba(255,255,255,.92)" @elseif($isLow) style="border:1px solid var(--border);border-left:3px solid var(--amber-2,#b45309);border-radius:13px;padding:14px 16px;background:rgba(255,255,255,.92)" @else style="border:1px solid var(--border);border-radius:13px;padding:14px 16px;background:rgba(255,255,255,.92)" @endif>
                            <div class="l" style="font-size:9.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint)">{{ __('On Hand') }}</div>
                            <div class="v" style="margin-top:5px;font-size:1.3rem;font-weight:800;font-variant-numeric:tabular-nums;color:{{ $isOut ? 'var(--red-2,#b91c1c)' : ($isLow ? 'var(--amber-2,#b45309)' : 'var(--ink)') }}">{{ number_format($stockOnHand, 0) }}</div>
                        </div>
                        <div class="statbox" style="margin-top:12px;border:1px solid var(--border);border-radius:13px;padding:14px 16px;background:rgba(255,255,255,.92)">
                            <div class="l" style="font-size:9.5px;font-weight:800;letter-spacing:.1em;text-transform:uppercase;color:var(--faint)">{{ __('Reorder Point') }}</div>
                            <div class="v" style="margin-top:5px;font-size:1.3rem;font-weight:800;font-variant-numeric:tabular-nums;color:var(--ink)">{{ number_format($reorderPoint, 0) }}</div>
                        </div>
                        @if($isOut)
                        <div style="margin-top:12px"><span class="inv-badge inv-badge-danger"><span class="inv-badge-dot"></span>{{ __('Out of stock') }}</span></div>
                        @elseif($isLow)
                        <div style="margin-top:12px"><span class="inv-badge inv-badge-warning"><span class="inv-badge-dot"></span>{{ __('Low stock') }}</span></div>
                        @endif
                    </div>
                </div>

                {{-- Quick Actions --}}
                <div class="inv-card">
                    <div class="pad" style="padding:20px 24px">
                        <div class="inv-card-h" style="padding:0 0 10px;border-bottom:none"><h2 style="font-size:11px;letter-spacing:.12em;text-transform:uppercase;color:var(--faint)">{{ __('Quick Actions') }}</h2></div>
                        <div class="qa">
                            <a href="{{ route('accounting.inventory.items.edit', $product) }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                {{ __('Edit Item') }}
                            </a>
                            <a href="{{ route('accounting.invsetup.transfers') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/><rect x="8" y="2" width="8" height="4" rx="1" ry="1"/></svg>
                                {{ __('Transfers & Adjustments') }}
                            </a>
                            <a href="{{ route('accounting.inventory.items') }}">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                                {{ __('All Items') }}
                            </a>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </div>

    <style>
        .work{display:grid;grid-template-columns:1fr 300px;gap:18px;align-items:start}
        @media(max-width:1100px){.work{grid-template-columns:1fr}}
        .dg{display:grid;grid-template-columns:repeat(3,1fr);gap:18px 24px}
        @media(max-width:900px){.dg{grid-template-columns:1fr 1fr}}
        @media(max-width:600px){.dg{grid-template-columns:1fr}}
        .span3{grid-column:1/-1}
        .qa a{display:flex;align-items:center;gap:10px;padding:11px 6px;border-radius:9px;font-size:13px;font-weight:600;color:var(--ink);text-decoration:none;border-bottom:1px solid var(--line)}
        .qa a:last-child{border-bottom:none}
        .qa a:hover{background:rgba(17,69,75,.06);color:var(--ink)}
        .qa a svg{color:var(--faint);flex-shrink:0}
        .statbox{border:1px solid var(--border);border-radius:13px;padding:14px 16px;background:rgba(255,255,255,.92)}
    </style>
</x-app-layout>
