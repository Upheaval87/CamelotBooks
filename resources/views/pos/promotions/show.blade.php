<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>{{ $promotion->name }}</h1>
                <div class="pos-sub">{{ ucfirst(str_replace('_', ' ', $promotion->type)) }} · {{ $promotion->start_date->format('d M Y') }} – {{ $promotion->end_date->format('d M Y') }}</div>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.promotions.index') }}" class="pos-btn pos-btn-ghost">Back</a>
                <form method="POST" action="{{ route('pos.promotions.destroy', $promotion) }}" style="display:inline" onsubmit="return fbConfirmSubmit(event, 'Delete this promotion?', { type: 'danger' })">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="pos-btn pos-btn-danger-o">Delete</button>
                </form>
            </div>
        </div>

            <div class="pos-card" style="margin-bottom:16px">
                <div class="pos-card-h">
                    <span class="pos-step">Promotion Details</span>
                </div>
                <div class="pos-pad">
                    <div class="pos-g3">
                        <div class="pos-f">
                            <label>Type</label>
                            <input type="text" class="pos-in" value="{{ ucwords(str_replace('_', ' ', $promotion->type)) }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Discount Value</label>
                            <input type="text" class="pos-in" value="{{ $promotion->discount_value }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Status</label>
                            <div style="padding-top:8px">
                                @if($promotion->is_active && $promotion->start_date->isPast() && $promotion->end_date->isFuture())
                                    <span class="pos-badge pos-badge-open"><span class="pos-bdot"></span>Running</span>
                                @elseif($promotion->is_active)
                                    <span class="pos-badge pos-badge-pend"><span class="pos-bdot"></span>Scheduled</span>
                                @else
                                    <span class="pos-badge pos-badge-mut"><span class="pos-bdot"></span>Inactive</span>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="pos-g3" style="margin-top:12px">
                        <div class="pos-f">
                            <label>Start Date</label>
                            <input type="text" class="pos-in" value="{{ $promotion->start_date->format('d M Y') }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>End Date</label>
                            <input type="text" class="pos-in" value="{{ $promotion->end_date->format('d M Y') }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Applies To</label>
                            <input type="text" class="pos-in" value="{{ ucwords(str_replace('_', ' ', $promotion->applies_to)) }}" readonly>
                        </div>
                    </div>
                    <div class="pos-g3" style="margin-top:12px">
                        <div class="pos-f">
                            <label>Minimum Quantity</label>
                            <input type="text" class="pos-in" value="{{ $promotion->min_qty }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Maximum Quantity</label>
                            <input type="text" class="pos-in" value="{{ $promotion->max_qty ?? 'No limit' }}" readonly>
                        </div>
                        <div class="pos-f">
                            <label>Customer Group</label>
                            <input type="text" class="pos-in" value="{{ $promotion->customer_group ?? 'All customers' }}" readonly>
                        </div>
                    </div>
                    @if($promotion->description)
                        <div class="pos-f" style="margin-top:12px">
                            <label>Description</label>
                            <div style="font-size:13px;color:var(--pos-muted);line-height:1.5">{{ $promotion->description }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
