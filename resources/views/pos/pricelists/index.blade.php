<x-app-layout>
    @php
        $typeBadges = [
            'retail' => 'pos-badge-active',
            'wholesale' => 'pos-badge-muted',
            'vip' => 'pos-badge-pend',
            'custom' => 'pos-badge-rev',
        ];
    @endphp

    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>Price Lists</h1>
                <p class="pos-sub">{{ number_format($priceLists->total()) }} price list{{ $priceLists->total() === 1 ? '' : 's' }} · override checkout prices per channel or group</p>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.pricelists.create') }}" class="pos-btn pos-btn-cta">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                    Create Price List
                </a>
            </div>
        </div>

        <div class="pos-shell">
            <div>
                @if($priceLists->isEmpty())
                    <div class="pos-card">
                        <div class="pos-empty">
                            <h3>No price lists yet</h3>
                            <p>Create a price list to offer wholesale, VIP, or promotional pricing at the till.</p>
                            <a href="{{ route('pos.pricelists.create') }}" class="pos-btn pos-btn-cta" style="margin-top:12px">Create Price List</a>
                        </div>
                    </div>
                @endif

                <div class="mb-1 grid gap-x-5 gap-y-2 md:grid-cols-2 xl:grid-cols-3">
                    @foreach($priceLists as $priceList)
                        <div class="pos-card" style="display:flex;flex-direction:column">
                            <div class="pos-card-h">
                                <h2>{{ $priceList->name }}</h2>
                                <span class="pos-badge {{ $typeBadges[$priceList->type] ?? 'pos-badge-muted' }}">{{ ucfirst($priceList->type) }}</span>
                                <span class="ml-auto"></span>
                                @if($priceList->is_active)
                                    <span class="pos-badge pos-badge-active"><span class="pos-bdot"></span>Active</span>
                                @else
                                    <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                @endif
                            </div>
                            <div class="pos-card-b" style="flex:1">
                                @if($priceList->description)
                                    <p class="pos-sub" style="margin-bottom:12px">{{ $priceList->description }}</p>
                                @endif
                                <div class="pos-row-meta">
                                    <span>Applies to</span>
                                    <span class="pos-bold">{{ $priceList->applies_to }}</span>
                                </div>
                                <div class="pos-row-meta">
                                    <span>Effective</span>
                                    <span class="pos-bold">{{ $priceList->effective_from?->format('d M Y') ?? '—' }} – {{ $priceList->effective_until?->format('d M Y') ?? 'open' }}</span>
                                </div>
                                <div class="pos-row-meta">
                                    <span>Items</span>
                                    <span class="pos-bold">{{ format_number($priceList->items->count(), 0) }}</span>
                                </div>
                            </div>
                            <div class="pos-card-f">
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
                    <div class="pos-pag">
                        <span>Showing {{ $priceLists->firstItem() ?? 0 }}–{{ $priceLists->lastItem() ?? 0 }} of {{ number_format($priceLists->total()) }} price lists</span>
                        {{ $priceLists->withQueryString()->links() }}
                    </div>
                @endif
            </div>

            <div class="pos-rail">
                <div class="pos-rail-card">
                    <h3>Quick Nav</h3>
                    <a href="{{ route('pos.pricelists.create') }}" class="pos-rail-link">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><path d="M12 5v14M5 12h14"/></svg>
                        New Price List
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
