<x-app-layout>
    <div class="inv-wrap py-6">
        <div class="inv-crumbs">
            <a href="{{ route('accounting.inventory.dashboard') }}">{{ __('Dashboard') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('accounting.inventory.items') }}">{{ __('Items') }}</a>
            <span class="sep">/</span>
            <a href="{{ route('accounting.inventory.items.show', $product) }}">{{ $product->name }}</a>
            <span class="sep">/</span>
            <span>{{ __('Edit') }}</span>
        </div>

        <div class="inv-sticky-head">
            <div style="display:flex;align-items:center;gap:12px">
                <h1>{{ __('Edit Item') }} — {{ $product->name }}</h1>
                <span class="inv-mono" style="font-size:12px;font-weight:600;color:var(--muted);background:rgba(17,69,75,.06);padding:3px 10px;border-radius:8px;border:1px solid rgba(17,69,75,.12)">{{ $product->sku }}</span>
            </div>
            <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center">
                <a href="{{ route('accounting.inventory.items.show', $product) }}" class="inv-btn inv-btn-ghost inv-btn-sm">{{ __('Cancel') }}</a>
                <button type="submit" form="inv-item-form" name="action" value="save" class="inv-btn inv-btn-cta inv-btn-sm">{{ __('Save Changes') }}</button>
            </div>
        </div>

        <form method="POST" action="{{ route('accounting.inventory.items.update', $product) }}" id="inv-item-form">
            @csrf @method('PUT')

            {{-- Basic Information --}}
            <div class="inv-card" style="margin-bottom:16px">
                <div class="inv-sec-head">
                    <div class="inv-sec-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                    </div>
                    <h2>{{ __('Basic Information') }}</h2>
                    <span class="inv-rule"></span>
                </div>
                <div class="inv-g4">
                    <div class="inv-form-group inv-span2">
                        <label class="inv-form-label">{{ __('Item Name') }} <span style="color:var(--red-2)">*</span></label>
                        <input type="text" name="name" value="{{ old('name', $product->name) }}" class="inv-input" required>
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Item Code / SKU') }}</label>
                        <input type="text" value="{{ $product->sku }}" class="inv-input" disabled style="background:rgba(17,69,75,.04)">
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Barcode') }}</label>
                        <input type="text" name="barcode" value="{{ old('barcode', $product->barcode) }}" class="inv-input">
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Item Type') }} <span style="color:var(--red-2)">*</span></label>
                        <select name="type" class="inv-input" required>
                            <option value="goods" {{ old('type', $product->type) === 'goods' ? 'selected' : '' }}>{{ __('Inventory Item') }}</option>
                            <option value="service" {{ old('type', $product->type) === 'service' ? 'selected' : '' }}>{{ __('Service') }}</option>
                        </select>
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Category') }}</label>
                        <select name="item_category_id" class="inv-input">
                            <option value="">{{ __('None') }}</option>
                            @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ old('item_category_id', $product->item_category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Unit of Measure') }}</label>
                        <input type="text" name="unit_of_measure" value="{{ old('unit_of_measure', $product->unit_of_measure) }}" class="inv-input">
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Tax Rate (%)') }}</label>
                        <input type="number" name="tax_rate" value="{{ old('tax_rate', $product->tax_rate) }}" class="inv-input" min="0" max="100" step="0.01">
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Track Inventory') }}</label>
                        <select name="tracked_as_inventory" class="inv-input">
                            <option value="0" {{ old('tracked_as_inventory', $product->tracked_as_inventory) == 0 ? 'selected' : '' }}>{{ __('No') }}</option>
                            <option value="1" {{ old('tracked_as_inventory', $product->tracked_as_inventory) == 1 ? 'selected' : '' }}>{{ __('Yes') }}</option>
                        </select>
                    </div>
                    <div class="inv-form-group inv-span4">
                        <label class="inv-form-label">{{ __('Description') }}</label>
                        <textarea name="description" class="inv-input" rows="3" style="min-height:80px;resize:vertical;padding-top:10px">{{ old('description', $product->description) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Pricing & GL --}}
            <div class="inv-card" style="margin-bottom:16px">
                <div class="inv-sec-head">
                    <div class="inv-sec-ic">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
                    </div>
                    <h2>{{ __('Pricing & GL') }}</h2>
                    <span class="inv-rule"></span>
                </div>
                <div class="inv-g4">
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Sales Price') }}</label>
                        <input type="number" name="sales_price" value="{{ old('sales_price', $product->sales_price) }}" class="inv-input" min="0" step="0.01">
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Purchase Price') }}</label>
                        <input type="number" name="purchase_price" value="{{ old('purchase_price', $product->purchase_price) }}" class="inv-input" min="0" step="0.01">
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">{{ __('Reorder Point') }}</label>
                        <input type="number" name="reorder_point" value="{{ old('reorder_point', $product->reorder_point) }}" class="inv-input" min="0">
                    </div>
                    <div class="inv-form-group">
                        <label class="inv-form-label">&nbsp;</label>
                    </div>
                    <div class="inv-form-group inv-span2">
                        <label class="inv-form-label">{{ __('Income Account') }}</label>
                        <select name="income_account_id" class="inv-input">
                            <option value="">{{ __('None') }}</option>
                            @foreach($incomeAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('income_account_id', $product->income_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="inv-form-group inv-span2">
                        <label class="inv-form-label">{{ __('Expense Account') }}</label>
                        <select name="expense_account_id" class="inv-input">
                            <option value="">{{ __('None') }}</option>
                            @foreach($expenseAccounts as $acc)
                            <option value="{{ $acc->id }}" {{ old('expense_account_id', $product->expense_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->code }} — {{ $acc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>

            {{-- Active --}}
            <div class="inv-card" style="margin-bottom:16px">
                <div style="padding:16px 20px">
                    <div class="inv-toggle-row">
                        <div class="inv-toggle-info">
                            <div class="inv-toggle-title">{{ __('Active') }}</div>
                            <div class="inv-toggle-desc">{{ __('Inactive items are hidden from transactions.') }}</div>
                        </div>
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" name="is_active" value="1" {{ old('is_active', $product->is_active) ? 'checked' : '' }} class="inv-toggle-track" style="display:none">
                        <label class="inv-toggle-track {{ old('is_active', $product->is_active) ? 'on' : '' }}" onclick="this.previousElementSibling.checked=!this.previousElementSibling.checked;this.classList.toggle('on')"></label>
                    </div>
                </div>
            </div>
        </form>

        <div class="inv-actionbar">
            <a href="{{ route('accounting.inventory.items.show', $product) }}" class="inv-btn inv-btn-ghost">{{ __('Cancel') }}</a>
            <button type="submit" form="inv-item-form" name="action" value="save" class="inv-btn inv-btn-cta">{{ __('Save Changes') }}</button>
        </div>
    </div>
</x-app-layout>
