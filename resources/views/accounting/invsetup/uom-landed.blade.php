<x-app-layout>
    <x-slot name="header">{{ __('UOM & Landed Costs') }}</x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="inv-hdr">
            <div>
                <h1 class="inv-hdr-t">{{ __('UOM & Landed Costs') }}</h1>
                <p class="inv-hdr-sub">{{ __('Unit-of-measure conversions and landed cost allocations.') }}</p>
            </div>
            <div class="inv-hdr-acts">
                <button class="inv-btn inv-btn-ghost" type="button" onclick="window.print()">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    {{ __('Export CSV') }}
                </button>
            </div>
        </div>

        <div class="inv-tabs">
            <a href="{{ route('accounting.invsetup.categories') }}" class="inv-tab">{{ __('Item Categories') }}</a>
            <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-tab">{{ __('Assemblies') }}</a>
            <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-tab">{{ __('Transfers & Adjustments') }}</a>
            <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-tab">{{ __('Stock Count') }}</a>
            <a href="{{ route('accounting.invsetup.uom') }}" class="inv-tab inv-tab-on">{{ __('UOM & Landed Costs') }}</a>
            <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-tab">{{ __('Valuation') }}</a>
            <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-tab">{{ __('Low Stock') }}</a>
        </div>

        <div class="inv-shell">
            <div class="inv-main">
                {{-- UOM Section --}}
                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                        <span>{{ __('Unit of Measure Conversions') }}</span>
                    </div>
                    <div class="inv-card-body inv-p-0">
                        <div class="inv-tbl-wrap">
                            <table class="inv-tbl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('From UOM') }}</th>
                                        <th>{{ __('To UOM') }}</th>
                                        <th class="inv-tbl-r">{{ __('Factor') }}</th>
                                        <th>{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($uomConversions as $conversion)
                                    <tr>
                                        <td>{{ $conversion->product?->name ?? '—' }}</td>
                                        <td class="inv-mono">{{ $conversion->from_uom ?? '—' }}</td>
                                        <td class="inv-mono">{{ $conversion->to_uom ?? '—' }}</td>
                                        <td class="inv-numr">{{ $conversion->factor ?? '—' }}</td>
                                        <td>
                                            <button class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Edit') }}</button>
                                            <button class="inv-btn inv-btn-sm inv-btn-ghost inv-btn-del">{{ __('Delete') }}</button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="inv-empty">{{ __('No UOM conversions found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Landed Costs Section --}}
                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M16 8l-8 8"/><path d="M8 8h8v8"/></svg>
                        <span>{{ __('Landed Cost Vouchers') }}</span>
                    </div>
                    <div class="inv-card-body inv-p-0">
                        <div class="inv-tbl-wrap">
                            <table class="inv-tbl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Voucher #') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Vendor') }}</th>
                                        <th>{{ __('Invoice #') }}</th>
                                        <th class="inv-tbl-r">{{ __('Amount') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($landedCosts as $voucher)
                                    <tr>
                                        <td class="inv-mono">{{ $voucher->voucher_number ?? '—' }}</td>
                                        <td>{{ $voucher->created_at?->format('M d, Y') ?? '—' }}</td>
                                        <td>{{ $voucher->vendor?->name ?? '—' }}</td>
                                        <td class="inv-mono">{{ $voucher->invoice_number ?? '—' }}</td>
                                        <td class="inv-numr">{{ number_format($voucher->total_amount ?? 0, 2) }}</td>
                                        <td>
                                            <span class="inv-pill inv-pill-{{ ($voucher->status ?? '') === 'posted' ? 'act' : 'wip' }}">
                                                {{ ucfirst($voucher->status ?? 'draft') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="6" class="inv-empty">{{ __('No landed cost vouchers found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="inv-rail">
                <div class="inv-rail-card">
                    <div class="inv-rail-sec">
                        <div class="inv-rail-sec-head">
                            <svg class="inv-rail-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                            <span class="inv-rail-sec-label">{{ __('Quick Nav') }}</span>
                        </div>
                        <div class="inv-rail-rule"></div>
                        <a href="{{ route('accounting.invsetup.categories') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 19a2 2 0 01-2 2H4a2 2 0 01-2-2V5a2 2 0 012-2h5l2 3h9a2 2 0 012 2z"/></svg>
                            {{ __('Categories') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M12 1v6m0 6v6M4.22 4.22l4.24 4.24m7.08 7.08l4.24 4.24M1 12h6m6 0h6M4.22 19.78l4.24-4.24m7.08-7.08l4.24-4.24"/></svg>
                            {{ __('Assemblies') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg>
                            {{ __('Transfers & Adjustments') }}
                        </a>
                        <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-rail-item">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                            {{ __('Valuation & Low Stock') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
