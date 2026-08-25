<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>{{ $priceList->name }}</h1>
                <div class="pos-sub">{{ ucfirst($priceList->type) }} Price List · {{ $priceList->items->count() }} items</div>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.pricelists.index') }}" class="pos-btn pos-btn-ghost">Back</a>
                <a href="{{ route('pos.pricelists.edit', $priceList) }}" class="pos-btn pos-btn-sec">Edit</a>
                <form method="POST" action="{{ route('pos.pricelists.destroy', $priceList) }}" style="display:inline" onsubmit="return fbConfirmSubmit(event, 'Delete this price list?', { type: 'danger' })">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="pos-btn pos-btn-danger-o">Delete</button>
                </form>
            </div>
        </div>

            {{-- Details --}}
            <div class="pos-card" style="margin-bottom:16px">
                <div class="pos-card-h">
                    <span class="pos-step">Details</span>
                </div>
                <div class="pos-pad">
                    <div class="pos-g3">
                        <div class="pos-f">
                            <label>Type</label>
                            <input type="text" class="pos-in" value="{{ ucfirst($priceList->type) }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Applies To</label>
                            <input type="text" class="pos-in" value="{{ $priceList->applies_to }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Status</label>
                            <div style="padding-top:8px">
                                @if($priceList->is_active)
                                    <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Active</span>
                                @else
                                    <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pos-g2" style="margin-top:12px">
                        <div class="pos-f">
                            <label>Effective From</label>
                            <input type="text" class="pos-in" value="{{ $priceList->effective_from?->format('d M Y') ?? 'No start date' }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Effective Until</label>
                            <input type="text" class="pos-in" value="{{ $priceList->effective_until?->format('d M Y') ?? 'No end date' }}" readonly>
                        </div>
                    </div>
                    @if($priceList->description)
                        <div class="pos-f" style="margin-top:12px">
                            <label>Description</label>
                            <div style="font-size:13px;color:var(--pos-muted);line-height:1.5">{{ $priceList->description }}</div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Line Items --}}
            <div class="pos-card">
                <div class="pos-card-h">
                    <span class="pos-step">Price List Items ({{ $priceList->items->count() }})</span>
                </div>
                <div class="pos-li-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>SKU</th>
                                <th class="num">List Price</th>
                                <th class="num">Min Qty</th>
                                <th class="num">Max Qty</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($priceList->items as $item)
                                <tr>
                                    <td class="pos-bold">{{ $item->product?->name ?? '—' }}</td>
                                    <td class="pos-mono pos-em">{{ $item->product?->sku ?? '—' }}</td>
                                    <td class="num pos-bold">{{ format_money($item->unit_price) }}</td>
                                    <td class="num">{{ $item->min_qty }}</td>
                                    <td class="num">{{ $item->max_qty ?? '∞' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5">
                                        <div class="pos-empty">
                                            <h3>No items in this price list</h3>
                                            <p>Edit the price list to add products.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
