<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('UOM & Landed Costs') }}</h1>
                <div class="inv-sub">{{ __('Unit-of-measure conversions and landed cost allocations.') }}</div>
            </div>
            <div style="display:flex;gap:10px">
                <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button">{{ __('Export CSV') }}</button>
                <a href="{{ route('accounting.landed-costs.create') }}" class="inv-btn inv-btn-ghost inv-btn-sm" style="color:var(--sec);background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.3)">{{ __('＋ New Voucher') }}</a>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'uom'])

        <div class="inv-card mb">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
                </div>
                <h2>{{ __('Unit of Measure Conversions') }}</h2>
                <span class="inv-rule"></span>
                <div class="right" style="margin-left:auto">
                    <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button" style="color:var(--sec);background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.3)">{{ __('＋ Add Conversion') }}</button>
                </div>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('From UOM') }}</th>
                            <th>{{ __('To UOM') }}</th>
                            <th class="num">{{ __('Factor') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($uomConversions as $uom)
                        <tr>
                            <td style="font-weight:600;color:var(--ink)">{{ $uom->product?->name ?? '—' }}</td>
                            <td class="em">{{ $uom->from_uom ?? $uom->name ?? '—' }}</td>
                            <td class="em">{{ $uom->to_uom ?? $uom->code ?? '—' }}</td>
                            <td class="num">{{ number_format($uom->conversion_factor ?? $uom->factor ?? 1, 2) }}</td>
                            <td class="inv-row-act">
                                <button class="inv-ibtn" title="{{ __('Edit') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                </button>
                                <button class="inv-ibtn" title="{{ __('Delete') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><polyline points="3 6 5 6 21 6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                </button>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="12" y1="3" x2="12" y2="21"/></svg>
                                    </div>
                                    <p>{{ __('No UOM conversions defined.') }}</p>
                                    <div class="inv-empty-sub">{{ __('Create units of measure to handle multi-unit products.') }}</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                </div>
                <h2>{{ __('Landed Cost Vouchers') }}</h2>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Voucher') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th class="num">{{ __('Total Cost') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($landedCosts as $vc)
                        <tr>
                            <td style="font-weight:600;color:var(--ink)">{{ $vc->voucher_number ?? 'LCV-' . str_pad($vc->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="em">{{ $vc->created_at->format('d M Y') }}</td>
                            <td class="num">K {{ number_format($vc->total_cost ?? 0, 2) }}</td>
                            <td>
                                <span class="inv-badge inv-badge-info"><span class="inv-badge-dot"></span>{{ ucfirst($vc->status ?? 'Draft') }}</span>
                            </td>
                            <td class="inv-row-act">
                                <a href="{{ route('accounting.landed-costs.show', $vc) }}" class="inv-ibtn" title="{{ __('View') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                                    </div>
                                    <p>{{ __('No landed cost vouchers found.') }}</p>
                                    <div class="inv-empty-sub">{{ __('Allocate freight, insurance, and duties to received goods.') }}</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-app-layout>
