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
            'fixed_amount' => 'pos-badge-pending',
            'buy_x_get_y' => 'pos-badge-muted',
            'customer_discount' => 'pos-badge-danger',
        ];
        $appliesLabels = [
            'all_items' => 'All Items',
            'specific_items' => 'Specific Items',
            'specific_categories' => 'Specific Categories',
        ];
    @endphp

    <div class="pos-wrap">
        <div class="pos-head">
            <div>
                <h1>Promotions</h1>
                <p class="pos-sub">{{ number_format($promotions->total()) }} promotion{{ $promotions->total() === 1 ? '' : 's' }} · discounts applied automatically at checkout</p>
            </div>
            <div class="pos-grow"></div>
            <div class="pos-actions">
                <a href="{{ route('pos.promotions.create') }}" class="pos-btn pos-btn-cta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Create Promotion
                </a>
            </div>
        </div>

        <div class="pos-card">
            <div class="pos-tbl-wrap">
                <table class="pos-tbl">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Type</th>
                            <th class="r">Discount</th>
                            <th>Applies To</th>
                            <th>Date Range</th>
                            <th>Status</th>
                            <th class="r">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($promotions as $promotion)
                            @php
                                if (! $promotion->is_active) {
                                    $statusLabel = 'Inactive';
                                    $statusBadge = 'pos-badge-muted';
                                    $statusDot = '';
                                } elseif (now()->lt($promotion->start_date)) {
                                    $statusLabel = 'Scheduled';
                                    $statusBadge = 'pos-badge-pending';
                                    $statusDot = 'amber';
                                } elseif ($promotion->end_date && now()->gt($promotion->end_date)) {
                                    $statusLabel = 'Expired';
                                    $statusBadge = 'pos-badge-danger';
                                    $statusDot = 'red';
                                } else {
                                    $statusLabel = 'Running';
                                    $statusBadge = 'pos-badge-active';
                                    $statusDot = 'green';
                                }
                            @endphp
                            <tr>
                                <td>
                                    <span style="font-weight:700">{{ $promotion->name }}</span>
                                    @if($promotion->description)
                                        <div class="mt-[2px] max-w-[280px] truncate text-[11px]" style="color:var(--faint)">{{ $promotion->description }}</div>
                                    @endif
                                </td>
                                <td><span class="pos-badge {{ $typeBadges[$promotion->type] ?? 'pos-badge-muted' }}">{{ $typeLabels[$promotion->type] ?? ucfirst($promotion->type) }}</span></td>
                                <td class="r" style="font-weight:700">
                                    @if($promotion->type === 'percentage')
                                        {{ format_number($promotion->discount_value) }}%
                                    @elseif($promotion->type === 'fixed_amount')
                                        -{{ format_money($promotion->discount_value) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>{{ $appliesLabels[$promotion->applies_to] ?? $promotion->applies_to }}</td>
                                <td>{{ $promotion->start_date?->format('d M Y') ?? '—' }} – {{ $promotion->end_date?->format('d M Y') ?? '—' }}</td>
                                <td><span class="pos-badge {{ $statusBadge }}"><span class="pos-badge-dot {{ $statusDot }}"></span>{{ $statusLabel }}</span></td>
                                <td class="r">
                                    <div class="flex justify-end gap-2">
                                        <a href="{{ route('pos.promotions.show', $promotion) }}" class="pos-btn pos-btn-xs pos-btn-sec">View</a>
                                        @if(\Illuminate\Support\Facades\Route::has('pos.promotions.edit'))
                                            <a href="{{ route('pos.promotions.edit', $promotion) }}" class="pos-btn pos-btn-xs pos-btn-sec">Edit</a>
                                        @endif
                                        <form method="POST" action="{{ route('pos.promotions.destroy', $promotion) }}"
                                            onsubmit="return fbConfirmSubmit(event, 'Delete promotion &quot;{{ $promotion->name }}&quot;?', { type: 'danger' })">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="pos-btn pos-btn-xs pos-btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="pos-tbl-empty">
                                    <h3 style="font-size:16px;font-weight:700;color:var(--ink);margin-bottom:6px">No promotions yet</h3>
                                    <p>Create a promotion to run percentage or fixed-amount discounts at the till.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="flex items-center justify-between border-t border-[color:var(--line)] px-5 py-3 text-[12.5px]" style="color:var(--muted)">
                <span>Showing {{ $promotions->firstItem() ?? 0 }}–{{ $promotions->lastItem() ?? 0 }} of {{ number_format($promotions->total()) }} promotions</span>
                {{ $promotions->withQueryString()->links() }}
            </div>
        </div>
    </div>
</x-app-layout>
