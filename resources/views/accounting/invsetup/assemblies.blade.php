<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Assemblies') }}</h1>
                <div class="inv-sub">{{ __('Build, unbuild, and manage multi-component products.') }}</div>
            </div>
            <div style="display:flex;gap:10px">
                <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button">{{ __('Export CSV') }}</button>
                <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button" style="color:var(--sec);background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.3)">{{ __('＋ New Assembly') }}</button>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'assemblies'])

        <div class="inv-card mb">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                </div>
                <h2>{{ __('Assembly Products') }}</h2>
                <span class="inv-rule"></span>
                <div class="right" style="margin-left:auto">
                    <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button" style="color:var(--sec);background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.3)">{{ __('＋ New Assembly') }}</button>
                </div>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('SKU') }}</th>
                            <th>{{ __('Category') }}</th>
                            <th class="num">{{ __('Components') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assemblies as $assembly)
                        <tr>
                            <td style="font-weight:600;color:var(--ink)">
                                <a href="{{ route('accounting.inventory.items.show', $assembly) }}" class="inv-link">{{ $assembly->name }}</a>
                            </td>
                            <td class="inv-mono">{{ $assembly->sku }}</td>
                            <td class="em">{{ $assembly->itemCategory?->name ?? '—' }}</td>
                            <td class="num">{{ $assembly->bom_items_count ?? 0 }}</td>
                            <td class="inv-row-act">
                                <a href="{{ route('accounting.inventory.items.show', $assembly) }}" class="inv-ibtn" title="{{ __('View') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                                    </div>
                                    <p>{{ __('No assembly products found.') }}</p>
                                    <div class="inv-empty-sub">{{ __('Create composite products with bills of materials.') }}</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($assemblies->hasPages())
            <div style="padding:16px 20px">{{ $assemblies->links() }}</div>
            @endif
        </div>

        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
                </div>
                <h2>{{ __('Recent Build History') }}</h2>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Product') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th class="num">{{ __('Qty') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($assemblyHistory as $build)
                        <tr>
                            <td style="font-weight:600;color:var(--ink)">{{ $build->product?->name ?? '—' }}</td>
                            <td>
                                @if($build->type === 'build')
                                <span class="inv-badge inv-badge-teal"><span class="inv-badge-dot"></span>{{ __('Build') }}</span>
                                @else
                                <span class="inv-badge inv-badge-info"><span class="inv-badge-dot"></span>{{ __('Unbuild') }}</span>
                                @endif
                            </td>
                            <td class="num">{{ $build->quantity ?? 0 }}</td>
                            <td class="em">{{ $build->created_at?->format('d M Y') }}</td>
                            <td class="inv-row-act">
                                <a href="{{ route('accounting.reports.assembly-build-history') }}" class="inv-ibtn" title="{{ __('View') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/></svg>
                                    </div>
                                    <p>{{ __('No build history.') }}</p>
                                    <div class="inv-empty-sub">{{ __('Build history will appear here after your first assembly operation.') }}</div>
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
