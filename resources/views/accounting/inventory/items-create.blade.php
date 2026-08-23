<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('accounting.inventory.items') }}">{{ __('Items') }}</a>
            <span class="sep">/</span>
            <span>{{ __('Add Item') }}</span>
        </div>

        <div class="inv-sticky-head">
            <div>
                <h1>{{ __('Add New Inventory Item') }}</h1>
                <div class="inv-sub">{{ __('Products, stock items and services with pricing, costing, stock control, barcode scanning & returnables.') }}</div>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <a href="{{ route('accounting.inventory.items') }}" class="inv-btn inv-btn-ghost inv-btn-sm">{{ __('Cancel') }}</a>
                <div class="inv-seg">
                    <button type="submit" name="action" value="save_and_new" form="inv-item-form" class="inv-btn inv-btn-ghost inv-btn-sm">{{ __('Save & Add Another') }}</button>
                    <button type="submit" name="action" value="save" form="inv-item-form" class="inv-btn inv-btn-cta inv-btn-sm">{{ __('Save Item') }}</button>
                </div>
            </div>
        </div>

        @include('accounting.inventory._form', [
            'isEdit' => false,
            'product' => null,
        ])

        <div class="inv-actionbar">
            <div style="margin-right:auto;font-size:12px;color:var(--muted);font-weight:700">{{ __('Fields marked * are required') }}</div>
            <a href="{{ route('accounting.inventory.items') }}" class="inv-btn inv-btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" name="action" value="save_and_new" form="inv-item-form" class="inv-btn inv-btn-ghost">{{ __('Save & Add Another') }}</button>
            <button type="submit" name="action" value="save" form="inv-item-form" class="inv-btn inv-btn-cta">{{ __('Save Item') }}</button>
        </div>
    </div>
</x-app-layout>
