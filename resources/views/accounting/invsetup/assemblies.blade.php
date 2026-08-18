<x-app-layout>
    <x-slot name="header">{{ __('Assemblies') }}</x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="inv-hdr">
            <div>
                <h1 class="inv-hdr-t">{{ __('Assemblies') }}</h1>
                <p class="inv-hdr-sub">{{ __('Build, unbuild, and manage multi-component products.') }}</p>
            </div>
            <div class="inv-hdr-acts">
                <button class="inv-btn inv-btn-ghost" type="button" onclick="window.print()">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    {{ __('Export CSV') }}
                </button>
                <a href="#" class="inv-btn inv-btn-cta">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('New Assembly') }}
                </a>
            </div>
        </div>

        <div class="inv-tabs">
            <a href="{{ route('accounting.invsetup.categories') }}" class="inv-tab">{{ __('Item Categories') }}</a>
            <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-tab inv-tab-on">{{ __('Assemblies') }}</a>
            <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-tab">{{ __('Transfers & Adjustments') }}</a>
            <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-tab">{{ __('Stock Count') }}</a>
            <a href="{{ route('accounting.invsetup.uom') }}" class="inv-tab">{{ __('UOM & Landed Costs') }}</a>
            <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-tab">{{ __('Valuation') }}</a>
            <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-tab">{{ __('Low Stock') }}</a>
        </div>

        <div class="inv-shell">
            <div class="inv-main">
                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        <span>{{ __('Assembly Products') }}</span>
                    </div>
                    <div class="inv-card-body inv-p-0">
                        <div class="inv-tbl-wrap">
                            <table class="inv-tbl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Product') }}</th>
                                        <th>{{ __('SKU') }}</th>
                                        <th>{{ __('Category') }}</th>
                                        <th class="inv-tbl-r">{{ __('Cost') }}</th>
                                        <th class="inv-tbl-r">{{ __('Actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assemblies as $assembly)
                                    <tr>
                                        <td>
                                            <div class="inv-flex-1">
                                                <a href="{{ route('accounting.inventory.items.show', $assembly) }}" class="inv-link">{{ $assembly->name }}</a>
                                            </div>
                                        </td>
                                        <td class="inv-mono">{{ $assembly->sku }}</td>
                                        <td>{{ $assembly->itemCategory?->name ?? '—' }}</td>
                                        <td class="inv-numr">{{ number_format($assembly->cost ?? 0, 2) }}</td>
                                        <td class="inv-tbl-r">
                                            <div class="inv-flex-1 inv-justify-end">
                                                <a href="#" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Build') }}</a>
                                                <a href="#" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('Unbuild') }}</a>
                                                <a href="{{ route('accounting.inventory.items.show', $assembly) }}" class="inv-btn inv-btn-sm inv-btn-ghost">{{ __('View') }}</a>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="inv-empty">{{ __('No assembly products found.') }}</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>
                        <span>{{ __('Recent Build History') }}</span>
                    </div>
                    <div class="inv-card-body inv-p-0">
                        <div class="inv-tbl-wrap">
                            <table class="inv-tbl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Product') }}</th>
                                        <th class="inv-tbl-c">{{ __('Quantity') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($assemblyHistory as $build)
                                    <tr>
                                        <td>{{ $build->created_at?->format('M d, Y H:i') ?? '—' }}</td>
                                        <td>{{ $build->product?->name ?? '—' }}</td>
                                        <td class="inv-tbl-c">{{ $build->quantity ?? '—' }}</td>
                                        <td>
                                            <span class="inv-pill inv-pill-{{ ($build->status ?? '') === 'completed' ? 'act' : (($build->status ?? '') === 'cancelled' ? 'inact' : 'wip') }}">
                                                {{ ucfirst($build->status ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="inv-empty">{{ __('No build history.') }}</td></tr>
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
                        <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-rail-item inv-rail-item-on">
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
