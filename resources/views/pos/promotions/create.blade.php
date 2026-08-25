<x-app-layout>
    <div class="pos">
        <div class="wrap">
            <div class="pos-page-head">
                <div>
                    <h1>Create Promotion</h1>
                    <div class="pos-sub">Define a new discount or promotional offer</div>
                </div>
                <div class="pos-actions">
                    <a href="{{ route('pos.promotions.index') }}" class="pos-btn pos-btn-ghost">Cancel</a>
                    <button type="submit" form="promotion-form" class="pos-btn pos-btn-cta">Save Promotion</button>
                </div>
            </div>

            <form id="promotion-form" method="POST" action="{{ route('pos.promotions.store') }}">
                @csrf

                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">1 · Promotion Details</span>
                    </div>
                    <div class="pos-pad">
                        @if($errors->any())
                            <x-feedback.alert variant="error" class="mb-4">
                                @foreach($errors->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </x-feedback.alert>
                        @endif

                        <div class="pos-g3">
                            <div class="pos-f">
                                <label>Promotion Name <span style="color:var(--pos-red)">*</span></label>
                                <input type="text" name="name" class="pos-in" value="{{ old('name') }}" required placeholder="e.g. Summer Sale 20%">
                            </div>
                            <div class="pos-f">
                                <label>Type <span style="color:var(--pos-red)">*</span></label>
                                <select name="type" class="pos-in" required x-data="{ t: '{{ old('type', 'percentage') }}' }" x-model="t">
                                    <option value="percentage" :selected="t === 'percentage'">Percentage Discount</option>
                                    <option value="fixed_amount" :selected="t === 'fixed_amount'">Fixed Amount</option>
                                    <option value="buy_x_get_y" :selected="t === 'buy_x_get_y'">Buy X Get Y</option>
                                    <option value="customer_discount" :selected="t === 'customer_discount'">Customer Group Discount</option>
                                </select>
                            </div>
                            <div class="pos-f">
                                <label>Discount Value <span style="color:var(--pos-red)">*</span></label>
                                <input type="number" name="discount_value" class="pos-in" value="{{ old('discount_value', '0') }}" step="0.01" min="0" required>
                            </div>
                        </div>

                        <div class="pos-g3" style="margin-top:12px">
                            <div class="pos-f">
                                <label>Start Date <span style="color:var(--pos-red)">*</span></label>
                                <input type="date" name="start_date" class="pos-in" value="{{ old('start_date') }}" required>
                            </div>
                            <div class="pos-f">
                                <label>End Date <span style="color:var(--pos-red)">*</span></label>
                                <input type="date" name="end_date" class="pos-in" value="{{ old('end_date') }}" required>
                            </div>
                            <div class="pos-f">
                                <label>Applies To <span style="color:var(--pos-red)">*</span></label>
                                <select name="applies_to" class="pos-in" required>
                                    <option value="all_items" {{ old('applies_to') === 'all_items' ? 'selected' : '' }}>All Items</option>
                                    <option value="specific_items" {{ old('applies_to') === 'specific_items' ? 'selected' : '' }}>Specific Items</option>
                                    <option value="specific_categories" {{ old('applies_to') === 'specific_categories' ? 'selected' : '' }}>Specific Categories</option>
                                </select>
                            </div>
                        </div>

                        <div class="pos-g3" style="margin-top:12px">
                            <div class="pos-f">
                                <label>Minimum Quantity</label>
                                <input type="number" name="min_qty" class="pos-in" value="{{ old('min_qty', '1') }}" min="1">
                            </div>
                            <div class="pos-f">
                                <label>Maximum Quantity</label>
                                <input type="number" name="max_qty" class="pos-in" value="{{ old('max_qty') }}" min="1" placeholder="No limit">
                            </div>
                            <div class="pos-f">
                                <label>Customer Group</label>
                                <input type="text" name="customer_group" class="pos-in" value="{{ old('customer_group') }}" placeholder="e.g. VIP, Wholesale">
                            </div>
                        </div>

                        <div class="pos-f" style="margin-top:12px">
                            <label>Description</label>
                            <textarea name="description" class="pos-in" rows="3" placeholder="Internal notes about this promotion…">{{ old('description') }}</textarea>
                        </div>

                        <div class="pos-f" style="margin-top:12px">
                            <div style="display:flex;align-items:center;gap:8px">
                                <input type="checkbox" name="is_active" value="1" {{ old('is_active', '1') ? 'checked' : '' }} class="pos-toggle-input">
                                <label style="font-size:12px;color:var(--pos-muted)">Active — promotion will be applied at checkout</label>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
