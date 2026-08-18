<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <span>{{ __('Assemblies') }}</span>
        </div>
        <div class="inv-head">
            <div>
                <h1>{{ __('Assemblies') }}</h1>
                <div class="inv-sub">{{ __('Build or unbuild composite products from component parts.') }}</div>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'assemblies'])

        <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px">

            {{-- Assembly Items --}}
            <div class="inv-card">
                <div class="inv-card-h">
                    <div class="inv-sec-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14.7 6.3a1 1 0 000 1.4l1.6 1.6a1 1 0 001.4 0l3.77-3.77a6 6 0 01-7.94 7.94l-6.91 6.91a2.12 2.12 0 01-3-3l6.91-6.91a6 6 0 017.94-7.94l-3.76 3.76z"/></svg>
                    </div>
                    {{ __('Assembly Items') }}
                </div>
                <div class="inv-card-body">
                    @forelse($assemblies as $assembly)
                    <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-weight:700;color:var(--ink);font-size:13px">{{ $assembly->name }}</div>
                            <div style="color:var(--faint);font-size:12px;margin-top:2px">{{ $assembly->sku }}</div>
                        </div>
                        <span class="inv-chip">{{ $assembly->bom_items_count ?? 0 }} {{ __('components') }}</span>
                    </div>
                    @empty
                    <div class="inv-empty" style="padding:32px 20px">
                        <p>{{ __('No assembly items.') }}</p>
                        <div class="inv-empty-sub">{{ __('Create composite products with bills of materials.') }}</div>
                    </div>
                    @endforelse
                </div>
            </div>

            {{-- Recent Builds --}}
            <div class="inv-card">
                <div class="inv-card-h">
                    <div class="inv-sec-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="16 3 21 3 21 8"/><line x1="4" y1="20" x2="21" y2="3"/><polyline points="21 16 21 21 16 21"/><line x1="15" y1="15" x2="21" y2="21"/></svg>
                    </div>
                    {{ __('Recent Builds') }}
                </div>
                <div class="inv-card-body">
                    @forelse($assemblyHistory as $build)
                    <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:flex;align-items:center;justify-content:space-between">
                        <div>
                            <div style="font-weight:700;color:var(--ink);font-size:13px">{{ $build->product->name ?? '—' }}</div>
                            <div style="color:var(--faint);font-size:12px;margin-top:2px">{{ $build->created_at->format('d M Y') }}</div>
                        </div>
                        <div style="text-align:right">
                            <div class="tabular-nums" style="font-weight:700;font-size:13px">{{ $build->quantity }} {{ __('units') }}</div>
                            <span class="inv-pill-neutral">{{ $build->type }}</span>
                        </div>
                    </div>
                    @empty
                    <div class="inv-empty" style="padding:32px 20px">
                        <p>{{ __('No build activity.') }}</p>
                        <div class="inv-empty-sub">{{ __('Build history will appear here after your first assembly operation.') }}</div>
                    </div>
                    @endforelse
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
