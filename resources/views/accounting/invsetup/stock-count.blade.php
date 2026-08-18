<x-app-layout>
    <x-slot name="header">{{ __('Stock Count') }}</x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        <div class="inv-hdr">
            <div>
                <h1 class="inv-hdr-t">{{ __('Stock Count') }}</h1>
                <p class="inv-hdr-sub">{{ __('Freeze movements, count, adjust, and compare.') }}</p>
            </div>
            <div class="inv-hdr-acts">
                <button class="inv-btn inv-btn-ghost" type="button" onclick="window.print()">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 9V2h12v7"/><path d="M6 18H4a2 2 0 01-2-2v-5a2 2 0 012-2h16a2 2 0 012 2v5a2 2 0 01-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
                    {{ __('Export CSV') }}
                </button>
                <a href="#" class="inv-btn inv-btn-cta">
                    <svg class="inv-btn-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    {{ __('Start New Count') }}
                </a>
            </div>
        </div>

        <div class="inv-tabs">
            <a href="{{ route('accounting.invsetup.categories') }}" class="inv-tab">{{ __('Item Categories') }}</a>
            <a href="{{ route('accounting.invsetup.assemblies') }}" class="inv-tab">{{ __('Assemblies') }}</a>
            <a href="{{ route('accounting.invsetup.transfers') }}" class="inv-tab">{{ __('Transfers & Adjustments') }}</a>
            <a href="{{ route('accounting.invsetup.stockcount') }}" class="inv-tab inv-tab-on">{{ __('Stock Count') }}</a>
            <a href="{{ route('accounting.invsetup.uom') }}" class="inv-tab">{{ __('UOM & Landed Costs') }}</a>
            <a href="{{ route('accounting.invsetup.valuation') }}" class="inv-tab">{{ __('Valuation') }}</a>
            <a href="{{ route('accounting.invsetup.lowstock') }}" class="inv-tab">{{ __('Low Stock') }}</a>
        </div>

        <div class="inv-shell">
            <div class="inv-main">
                <div class="inv-callout inv-callout-info">
                    <svg class="inv-callout-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <div>
                        <div class="inv-callout-t">{{ __('How It Works') }}</div>
                        <p class="inv-callout-desc">{{ __('Start a count to freeze all stock movements. The system takes a snapshot of current quantities, then you enter what you actually counted. The variance is computed automatically.') }}</p>
                    </div>
                </div>

                <div class="inv-sgrid inv-sgrid-3">
                    <div class="inv-sbox">
                        <div class="inv-sbox-ic inv-sbox-ic-ink">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg>
                        </div>
                        <div class="inv-sbox-lbl">{{ __('Total Counts') }}</div>
                        <div class="inv-sbox-v">{{ $counts->total() }}</div>
                    </div>
                    <div class="inv-sbox">
                        <div class="inv-sbox-ic inv-sbox-ic-amber">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        </div>
                        <div class="inv-sbox-lbl">{{ __('In Progress') }}</div>
                        <div class="inv-sbox-v">{{ $counts->where('status', 'in_progress')->count() }}</div>
                    </div>
                    <div class="inv-sbox">
                        <div class="inv-sbox-ic inv-sbox-ic-steel">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
                        </div>
                        <div class="inv-sbox-lbl">{{ __('Completed') }}</div>
                        <div class="inv-sbox-v">{{ $counts->where('status', 'completed')->count() }}</div>
                    </div>
                </div>

                <div class="inv-card">
                    <div class="inv-card-h">
                        <svg class="inv-sec-ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 4h2a2 2 0 012 2v14a2 2 0 01-2 2H6a2 2 0 01-2-2V6a2 2 0 012-2h2"/></svg>
                        <span>{{ __('Stock Counts') }}</span>
                    </div>
                    <div class="inv-card-body inv-p-0">
                        <div class="inv-tbl-wrap">
                            <table class="inv-tbl">
                                <thead>
                                    <tr>
                                        <th>{{ __('Count #') }}</th>
                                        <th>{{ __('Date') }}</th>
                                        <th>{{ __('Warehouse') }}</th>
                                        <th class="inv-tbl-c">{{ __('Lines') }}</th>
                                        <th>{{ __('Status') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($counts as $count)
                                    <tr>
                                        <td class="inv-mono">{{ $count->count_number ?? '—' }}</td>
                                        <td>{{ $count->created_at?->format('M d, Y') ?? '—' }}</td>
                                        <td>{{ $count->warehouse?->name ?? '—' }}</td>
                                        <td class="inv-tbl-c">{{ $count->lines_count ?? 0 }}</td>
                                        <td>
                                            <span class="inv-pill inv-pill-{{ ($count->status ?? '') === 'completed' ? 'act' : (($count->status ?? '') === 'cancelled' ? 'inact' : 'wip') }}">
                                                {{ ucfirst($count->status ?? 'pending') }}
                                            </span>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="5" class="inv-empty">{{ __('No stock counts found.') }}</td></tr>
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
