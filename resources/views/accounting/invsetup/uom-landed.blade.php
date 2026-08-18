<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <span>{{ __('Units of Measure & Landed Costs') }}</span>
        </div>
        <div class="inv-head">
            <div>
                <h1>{{ __('Units of Measure & Landed Costs') }}</h1>
                <div class="inv-sub">{{ __('Manage UOM conversions and landed cost allocations.') }}</div>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'uom'])

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

            {{-- Units of Measure --}}
            <div class="inv-card">
                <div class="inv-card-h">
                    <div class="inv-sec-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
                    </div>
                    {{ __('Units of Measure') }}
                </div>
                <div class="inv-card-body">
                    @forelse($uomConversions as $uom)
                    <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-weight:700;color:var(--ink);font-size:13px">{{ $uom->name }}</div>
                            <div style="color:var(--faint);font-size:12px;margin-top:2px">{{ $uom->code }}</div>
                        </div>
                        <span class="inv-chip">{{ $uom->products_count }} {{ __('products') }}</span>
                    </div>
                    @empty
                    <div class="inv-empty" style="padding:32px 20px">
                        <p>{{ __('No UOM conversions defined.') }}</p>
                        <div class="inv-empty-sub">{{ __('Create units of measure to handle multi-unit products.') }}</div>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Landed Costs --}}
            <div class="inv-card">
                <div class="inv-card-h">
                    <div class="inv-sec-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                    </div>
                    {{ __('Landed Cost Vouchers') }}
                </div>
                <div class="inv-card-body">
                    @forelse($landedCosts as $vc)
                    <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-weight:700;color:var(--ink);font-size:13px">{{ $vc->voucher_number }}</div>
                            <div style="color:var(--faint);font-size:12px;margin-top:2px">{{ $vc->created_at->format('d M Y') }}</div>
                        </div>
                        <div style="text-align:right">
                            <div class="tabular-nums" style="font-weight:700;font-size:13px">K {{ number_format($vc->total_cost, 2) }}</div>
                            <span class="inv-pill-neutral">{{ $vc->status }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="inv-empty" style="padding:32px 20px">
                        <p>{{ __('No landed cost vouchers.') }}</p>
                        <div class="inv-empty-sub">{{ __('Allocate freight, insurance, and duties to received goods.') }}</div>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
