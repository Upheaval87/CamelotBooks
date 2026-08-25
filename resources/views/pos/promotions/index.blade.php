<x-app-layout>
    @php
        $typeLabels = [
            'percentage' => 'Percentage',
            'fixed_amount' => 'Fixed Amount',
            'buy_x_get_y' => 'Buy X Get Y',
            'customer_discount' => 'Customer Discount',
        ];
        $typeBadges = [
            'percentage' => 'pos-badge-active',
            'fixed_amount' => 'pos-badge-pend',
            'buy_x_get_y' => 'pos-badge-muted',
            'customer_discount' => 'pos-badge-rev',
        ];
        $appliesLabels = [
            'all_items' => 'All Items',
            'specific_items' => 'Specific Items',
            'specific_categories' => 'Specific Categories',
        ];
    @endphp

    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>Promotions</h1>
                <p class="pos-sub">{{ number_format($promotions->total()) }} promotion{{ $promotions->total() === 1 ? '' : 's' }} · discounts applied automatically at checkout</p>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.promotions.create') }}" class="pos-btn pos-btn-cta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Create Promotion
                </a>
            </div>
        </div>

        <div class="pos-shell">
            <div>
                <div class="pos-card">
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th class="num">Discount</th>
                                    <th>Applies To</th>
                                    <th>Date Range</th>
                                    <th>Status</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($promotions as $promotion)
                                    @php
                                        if (! $promotion->is_active) {
                                            $statusLabel = 'Inactive';
                                            $statusBadge = 'pos-badge-mut';
                                            $statusDot = '';
                                        } elseif (now()->lt($promotion->start_date)) {
                                            $statusLabel = 'Scheduled';
                                            $statusBadge = 'pos-badge-pend';
                                            $statusDot = 'amber';
                                        } elseif ($promotion->end_date && now()->gt($promotion->end_date)) {
                                            $statusLabel = 'Expired';
                                            $statusBadge = 'pos-badge-rev';
                                            $statusDot = 'red';
                                        } else {
                                            $statusLabel = 'Running';
                                            $statusBadge = 'pos-badge-active';
                                            $statusDot = 'green';
                                        }
                                    @endphp
                                    <tr>
                                        <td>
                                            <span class="pos-bold">{{ $promotion->name }}</span>
                                            @if($promotion->description)
                                                <div class="pos-sub" style="margin-top:2px;max-width:280px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $promotion->description }}</div>
                                            @endif
                                        </td>
                                        <td><span class="pos-badge {{ $typeBadges[$promotion->type] ?? 'pos-badge-muted' }}">{{ $typeLabels[$promotion->type] ?? ucfirst($promotion->type) }}</span></td>
                                        <td class="num pos-bold">
                                            @if($promotion->type === 'percentage')
                                                {{ format_number($promotion->discount_value) }}%
                                            @elseif($promotion->type === 'fixed_amount')
                                                -{{ format_money($promotion->discount_value) }}
                                            @else
                                                —
                                            @endif
                                        </td>
                                        <td>{{ $appliesLabels[$promotion->applies_to] ?? $promotion->applies_to }}</td>
                                        <td class="pos-em">{{ $promotion->start_date?->format('d M Y') ?? '—' }} – {{ $promotion->end_date?->format('d M Y') ?? '—' }}</td>
                                        <td><span class="pos-badge {{ $statusBadge }}"><span class="pos-bdot {{ $statusDot }}"></span>{{ $statusLabel }}</span></td>
                                        <td class="num">
                                            <div class="pos-row-act">
                                                <a href="{{ route('pos.promotions.show', $promotion) }}" class="pos-ibtn" title="View">
                                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                                </a>
                                                @if(\Illuminate\Support\Facades\Route::has('pos.promotions.edit'))
                                                    <a href="{{ route('pos.promotions.edit', $promotion) }}" class="pos-ibtn" title="Edit">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                                    </a>
                                                @endif
                                                <form method="POST" action="{{ route('pos.promotions.destroy', $promotion) }}"
                                                    onsubmit="return fbConfirmSubmit(event, 'Delete promotion &quot;{{ $promotion->name }}&quot;?', { type: 'danger' })">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="pos-ibtn" title="Delete" style="color:var(--pos-red)">
                                                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6m3 0V4a2 2 0 012-2h4a2 2 0 012 2v2"/></svg>
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7">
                                            <div class="pos-empty">
                                                <h3>No promotions yet</h3>
                                                <p>Create a promotion to run percentage or fixed-amount discounts at the till.</p>
                                                <a href="{{ route('pos.promotions.create') }}" class="pos-btn pos-btn-cta" style="margin-top:12px">Create Promotion</a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="pos-pag">
                        <span>Showing {{ $promotions->firstItem() ?? 0 }}–{{ $promotions->lastItem() ?? 0 }} of {{ number_format($promotions->total()) }} promotions</span>
                        {{ $promotions->withQueryString()->links() }}
                    </div>
                </div>
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.promotions.create') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        New Promotion
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
