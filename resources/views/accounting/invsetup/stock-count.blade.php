<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-head">
            <div>
                <h1>{{ __('Stock Count') }}</h1>
                <div class="inv-sub">{{ __('Freeze movements, count, adjust, and compare.') }}</div>
            </div>
            <div style="display:flex;gap:10px">
                <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button">{{ __('Export CSV') }}</button>
                <button class="inv-btn inv-btn-ghost inv-btn-sm" type="button" style="color:var(--sec);background:rgba(18,143,142,.08);border-color:rgba(18,143,142,.3)">{{ __('＋ Start New Count') }}</button>
            </div>
        </div>

        @include('accounting.invsetup._tabs', ['activeTab' => 'counts'])

        <div class="inv-steps">
            <div class="inv-step">
                <div class="inv-step-n">1</div>
                <div class="inv-step-t">{{ __('Freeze') }}</div>
                <div class="inv-step-s">{{ __('Start a count to freeze all stock movements and snapshot current quantities.') }}</div>
            </div>
            <div class="inv-step">
                <div class="inv-step-n">2</div>
                <div class="inv-step-t">{{ __('Count') }}</div>
                <div class="inv-step-s">{{ __('Enter what you actually counted, per item and warehouse.') }}</div>
            </div>
            <div class="inv-step">
                <div class="inv-step-n">3</div>
                <div class="inv-step-t">{{ __('Adjust') }}</div>
                <div class="inv-step-s">{{ __('The variance is computed automatically and posted on completion.') }}</div>
            </div>
        </div>

        <div class="inv-card">
            <div class="inv-sec-head">
                <div class="inv-sec-ic">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                </div>
                <h2>{{ __('Counts') }}</h2>
            </div>
            <div class="inv-tbl-wrap">
                <table class="inv-tbl">
                    <thead>
                        <tr>
                            <th>{{ __('Count №') }}</th>
                            <th>{{ __('Date') }}</th>
                            <th>{{ __('Warehouse') }}</th>
                            <th class="num">{{ __('Items') }}</th>
                            <th class="num">{{ __('Variance') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($counts as $count)
                        @php
                            $variance = $count->variance_total ?? null;
                        @endphp
                        <tr>
                            <td class="inv-mono">{{ $count->count_number ?? 'SC-' . str_pad($count->id, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="em">{{ $count->created_at->format('d M Y') }}</td>
                            <td class="em">{{ $count->warehouse?->name ?? $warehouses->firstWhere('id', $count->warehouse_id)?->name ?? '—' }}</td>
                            <td class="num">{{ $count->lines_count ?? 0 }}</td>
                            <td class="num" @if($variance !== null && $variance < 0) style="color:var(--red-2)" @endif>
                                {{ $variance !== null ? number_format($variance) : '—' }}
                            </td>
                            <td>
                                @if($count->status === 'completed' || $count->status === 'posted')
                                <span class="inv-badge inv-badge-active"><span class="inv-badge-dot"></span>{{ __('Complete') }}</span>
                                @elseif($count->status === 'in_progress' || $count->status === 'open')
                                <span class="inv-badge inv-badge-warning"><span class="inv-badge-dot"></span>{{ __('Open') }}</span>
                                @else
                                <span class="inv-badge inv-badge-info"><span class="inv-badge-dot"></span>{{ ucfirst($count->status ?? 'Draft') }}</span>
                                @endif
                            </td>
                            <td class="inv-row-act">
                                <a href="{{ route('accounting.reports.stock-count-variance') }}" class="inv-ibtn" title="{{ __('View') }}">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="14" height="14"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="7">
                                <div class="inv-empty">
                                    <div class="inv-empty-ic">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/></svg>
                                    </div>
                                    <p>{{ __('No stock counts recorded.') }}</p>
                                    <div class="inv-empty-sub">{{ __('Start your first stock count to track physical vs system quantities.') }}</div>
                                </div>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($counts->hasPages())
            <div style="padding:16px 20px">{{ $counts->links() }}</div>
            @endif
        </div>
    </div>
</x-app-layout>
