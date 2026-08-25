<x-app-layout>
    @php
        $typeBadges = [
            'retail' => 'pos-badge-active',
            'wholesale' => 'pos-badge-muted',
            'vip' => 'pos-badge-pending',
            'custom' => 'pos-badge-danger',
        ];
    @endphp

    <div class="pos-wrap">
        <div class="pos-head">
            <div>
                <h1>Price Lists</h1>
                <p class="pos-sub">{{ number_format($priceLists->total()) }} price list{{ $priceLists->total() === 1 ? '' : 's' }} · override checkout prices per channel or group</p>
            </div>
            <div class="pos-grow"></div>
            <div class="pos-actions">
                <a href="{{ route('pos.pricelists.create') }}" class="pos-btn pos-btn-cta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Create Price List
                </a>
            </div>
        </div>

        @if($priceLists->isEmpty())
            <div class="pos-card">
                <div class="pos-tbl-empty">
                    <h3 style="font-size:16px;font-weight:700;color:var(--ink);margin-bottom:6px">No price lists yet</h3>
                    <p>Create a price list to offer wholesale, VIP, or promotional pricing at the till.</p>
                </div>
            </div>
        @endif

        <div class="mb-1 grid gap-x-5 gap-y-2 md:grid-cols-2 xl:grid-cols-3">
            @foreach($priceLists as $priceList)
                <div class="pos-card [margin-bottom:0] flex flex-col">
                    <div class="pos-card-h">
                        <h2>{{ $priceList->name }}</h2>
                        <span class="pos-badge {{ $typeBadges[$priceList->type] ?? 'pos-badge-muted' }}">{{ ucfirst($priceList->type) }}</span>
                        <span class="ml-auto"></span>
                        @if($priceList->is_active)
                            <span class="pos-badge pos-badge-active"><span class="pos-badge-dot green"></span>Active</span>
                        @else
                            <span class="pos-badge pos-badge-muted"><span class="pos-badge-dot"></span>Inactive</span>
                        @endif
                    </div>
                    <div class="pos-card-b" style="flex:1">
                        @if($priceList->description)
                            <p class="mb-3 text-[12.5px] leading-relaxed" style="color:var(--muted)">{{ $priceList->description }}</p>
                        @endif
                        <div class="flex items-center justify-between border-b border-[color:var(--line)] py-2 text-[12.5px]" style="color:var(--muted)">
                            <span>Applies to</span>
                            <span class="font-bold" style="color:var(--ink)">{{ $priceList->applies_to }}</span>
                        </div>
                        <div class="flex items-center justify-between border-b border-[color:var(--line)] py-2 text-[12.5px]" style="color:var(--muted)">
                            <span>Effective</span>
                            <span class="font-bold" style="color:var(--ink)">
                                {{ $priceList->effective_from?->format('d M Y') ?? '—' }} – {{ $priceList->effective_until?->format('d M Y') ?? 'open' }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between py-2 text-[12.5px]" style="color:var(--muted)">
                            <span>Items</span>
                            <span class="font-bold" style="color:var(--ink)">{{ format_number($priceList->items->count(), 0) }}</span>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 border-t border-[color:var(--line)] px-5 py-3">
                        <a href="{{ route('pos.pricelists.show', $priceList) }}" class="pos-btn pos-btn-xs pos-btn-sec">View</a>
                        <a href="{{ route('pos.pricelists.edit', $priceList) }}" class="pos-btn pos-btn-xs pos-btn-sec">Edit</a>
                        <form method="POST" action="{{ route('pos.pricelists.destroy', $priceList) }}"
                            onsubmit="return fbConfirmSubmit(event, 'Delete price list &quot;{{ $priceList->name }}&quot; and all its item prices?', { type: 'danger' })">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="pos-btn pos-btn-xs pos-btn-danger">Delete</button>
                        </form>
                    </div>
                </div>
            @endforeach
        </div>

        @if($priceLists->hasPages())
            <div class="mt-4 flex items-center justify-between px-1 py-2 text-[12.5px]" style="color:var(--muted)">
                <span>Showing {{ $priceLists->firstItem() ?? 0 }}–{{ $priceLists->lastItem() ?? 0 }} of {{ number_format($priceLists->total()) }} price lists</span>
                {{ $priceLists->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
