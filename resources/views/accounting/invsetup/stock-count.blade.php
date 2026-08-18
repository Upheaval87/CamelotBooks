<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <span>{{ __('Stock Count') }}</span>
        </div>
        <div class="inv-head">
            <div>
                <h1>{{ __('Stock Count') }}</h1>
                <div class="inv-sub">{{ __('Schedule physical counts, record variances, and reconcile inventory.') }}</div>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'counts'])

        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:16px;margin-bottom:24px">
            <div class="inv-card">
                <div style="padding:24px;display:flex;flex-direction:column;align-items:center;text-align:center">
                    <div class="inv-step-n">1</div>
                    <div class="inv-step-t">{{ __('Select Items') }}</div>
                    <div class="inv-step-s">{{ __('Choose which products to count') }}</div>
                </div>
            </div>
            <div class="inv-card">
                <div style="padding:24px;display:flex;flex-direction:column;align-items:center;text-align:center">
                    <div class="inv-step-n">2</div>
                    <div class="inv-step-t">{{ __('Record Counts') }}</div>
                    <div class="inv-step-s">{{ __('Enter physical count results') }}</div>
                </div>
            </div>
            <div class="inv-card">
                <div style="padding:24px;display:flex;flex-direction:column;align-items:center;text-align:center">
                    <div class="inv-step-n">3</div>
                    <div class="inv-step-t">{{ __('Post Adjustments') }}</div>
                    <div class="inv-step-s">{{ __('Reconcile variances to GL') }}</div>
                </div>
            </div>
        </div>

        <div class="inv-card">
            <div class="inv-card-h">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/><path d="M9 14l2 2 4-4"/></svg>
                </div>
                {{ __('Count History') }}
            </div>
            <div class="inv-card-body">
                @forelse($counts as $count)
                <div style="padding:12px 20px;border-bottom:1px solid var(--line);display:grid;grid-template-columns:2fr 1fr 1fr 1fr;gap:12px;align-items:center;font-size:13px">
                    <div>
                        <span style="font-weight:700;color:var(--ink)">{{ $count->count_number ?? $count->id }}</span>
                        <div style="color:var(--faint);font-size:12px;margin-top:2px">{{ $count->created_at->format('d M Y') }}</div>
                    </div>
                    <div>{{ $count->status ?? '—' }}</div>
                    <div class="tabular-nums">{{ $count->lines_count ?? 0 }} {{ __('items') }}</div>
                    <div style="text-align:right">
                        <span class="inv-pill-neutral">{{ $count->status ?? 'Draft' }}</span>
                    </div>
                </div>
                @empty
                <div class="inv-empty">
                    <div class="inv-empty-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                    </div>
                    <p>{{ __('No stock counts recorded.') }}</p>
                    <div class="inv-empty-sub">{{ __('Start your first stock count to track physical vs system quantities.') }}</div>
                </div>
                @endforelse
            </div>
            @if($counts->hasPages())
            <div style="padding:16px 20px">{{ $counts->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
