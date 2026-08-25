@php
    $isEdit = $isEdit ?? false;
    $product = $product ?? null;
    $categories = $categories ?? collect();
    $incomeAccounts = $incomeAccounts ?? collect();
    $expenseAccounts = $expenseAccounts ?? collect();
    $inventoryAccounts = $inventoryAccounts ?? collect();
    $suppliers = $suppliers ?? collect();
    $warehouses = $warehouses ?? collect();
    $hasStockMovements = $hasStockMovements ?? false;
    $hasTransactions = $hasTransactions ?? false;
@endphp

@php
    $ret = $product?->returnable;
    $trackOn = old('tracked_as_inventory', $product?->tracked_as_inventory ?? true);
    $retOn = old('is_returnable', $ret ? true : false);
    $activeOn = old('is_active', $product?->is_active ?? true);
    $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
@endphp

<form method="POST" action="{{ $isEdit ? route('accounting.inventory.items.update', $product) : route('accounting.inventory.items.store') }}"
      id="inv-item-form" x-data="itemForm()" x-cloak>
    @csrf
    @if($isEdit) @method('PUT') @endif

    {{-- ═══ 1 · BASIC INFORMATION ═══ --}}
    <div class="inv-card" style="margin-bottom:16px">
        <div class="inv-sec-head">
            <div class="inv-sec-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
            </div>
            <h2>{{ __('Basic Information') }}</h2>
            <span class="inv-rule"></span>
            <span style="font-size:11px;color:var(--muted);font-weight:700">{{ __('Identity & classification') }}</span>
        </div>
        <div class="inv-g4">
            <div class="inv-form-group inv-span2">
                <label class="inv-form-label">{{ __('Item Name') }} <span style="color:var(--red-2)">*</span></label>
                <input type="text" name="name" value="{{ old('name', $product?->name) }}" class="inv-input"
                       placeholder="{{ __('e.g. Coca-Cola 500ml Filled') }}" required>
                @error('name') <div class="inv-form-err">{{ $message }}</div> @enderror
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Item Type') }} <span style="color:var(--red-2)">*</span></label>
                <select name="type" class="inv-input" required>
                    <option value="goods" {{ old('type', $product?->type ?? 'goods') === 'goods' ? 'selected' : '' }}>{{ __('Inventory Item') }}</option>
                    <option value="service" {{ old('type', $product?->type) === 'service' ? 'selected' : '' }}>{{ __('Service') }}</option>
                    <option value="bundle" {{ old('type', $product?->type) === 'bundle' ? 'selected' : '' }}>{{ __('Bundle') }}</option>
                </select>
                @error('type') <div class="inv-form-err">{{ $message }}</div> @enderror
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Item Code / SKU') }} <span style="color:var(--red-2)">*</span></label>
                <div style="display:flex;gap:8px">
                    <input type="text" name="sku" id="inv-sku" value="{{ old('sku', $product?->sku) }}"
                           class="inv-input" placeholder="{{ __('e.g. SKU-0001') }}" style="flex:1"
                           {{ $isEdit && $hasTransactions ? 'readonly style="flex:1;background:rgba(17,69,75,.04)"' : '' }} required>
                    <button type="button" class="inv-btn-sm inv-btn-ghost" style="width:44px;padding:0;justify-content:center" title="{{ __('Generate SKU') }}" onclick="invGenSKU()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17"/></svg>
                    </button>
                </div>
                @error('sku') <div class="inv-form-err">{{ $message }}</div> @enderror
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Barcode / QR') }}</label>
                <div style="display:flex;gap:8px">
                    <input type="text" name="barcode" id="inv-barcode" value="{{ old('barcode', $product?->barcode) }}"
                           class="inv-input" placeholder="{{ __('Scan or generate') }}" style="flex:1">
                    <button type="button" class="inv-btn-sm inv-btn-ghost" style="width:44px;padding:0;justify-content:center" title="{{ __('Scan with camera') }}" onclick="invOpenScanner()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M23 19a2 2 0 01-2 2H3a2 2 0 01-2-2V8a2 2 0 012-2h4l2-3h6l2 3h4a2 2 0 012 2z"/><circle cx="12" cy="13" r="4"/></svg>
                    </button>
                    <button type="button" class="inv-btn-sm inv-btn-ghost" style="width:44px;padding:0;justify-content:center" title="{{ __('Generate barcode') }}" onclick="invGenBar()">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.621 2.485A2 2 0 004.561 21h14.878a2 2 0 001.94-1.515L22 17"/></svg>
                    </button>
                </div>
                <div class="inv-form-hint">{{ __('Scan with camera or USB scanner · EAN/UPC/QR supported') }}</div>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Category') }}</label>
                <select name="item_category_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($categories as $cat)
                    <option value="{{ $cat->id }}" {{ old('item_category_id', $product?->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Brand') }} <span style="color:var(--faint)">{{ __('Optional') }}</span></label>
                <input type="text" name="brand" value="{{ old('brand', $product?->brand) }}" class="inv-input" placeholder="{{ __('e.g. Coca-Cola') }}">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Unit of Measure') }}</label>
                <input type="text" name="unit_of_measure" value="{{ old('unit_of_measure', $product?->unit_of_measure) }}" class="inv-input" placeholder="{{ __('e.g. pcs, kg, box') }}">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Tax Rate (%)') }}</label>
                <input type="number" name="tax_rate" value="{{ old('tax_rate', $product?->tax_rate ?? 0) }}" class="inv-input" min="0" max="100" step="0.01">
                @error('tax_rate') <div class="inv-form-err">{{ $message }}</div> @enderror
            </div>
            <div class="inv-form-group inv-span4">
                <label class="inv-form-label">{{ __('Description') }} <span style="color:var(--faint)">{{ __('Optional') }}</span></label>
                <textarea name="description" class="inv-input" rows="3" style="min-height:80px;resize:vertical;padding-top:10px"
                          placeholder="{{ __('Item description, specifications or notes…') }}">{{ old('description', $product?->description) }}</textarea>
            </div>
        </div>
        <div style="padding:0 20px 4px">
            <div class="inv-toggle-row">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Track Inventory') }}</div>
                    <div class="inv-toggle-desc">{{ __('Maintain stock levels, COGS and stock movements for this item') }}</div>
                </div>
                <input type="hidden" name="tracked_as_inventory" value="0">
                <input type="checkbox" name="tracked_as_inventory" value="1" x-model="trackInventory"
                       {{ $trackOn ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track" :class="trackInventory ? 'on' : ''" @click="trackInventory = !trackInventory"></span>
            </div>
            <div class="inv-toggle-row">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Is Returnable') }}</div>
                    <div class="inv-toggle-desc">{{ __('Item carries a refundable container deposit (bottle / crate / keg / cylinder)') }}</div>
                </div>
                <input type="hidden" name="is_returnable" value="0">
                <input type="checkbox" name="is_returnable" value="1" x-model="isReturnable"
                       {{ $retOn ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track" :class="isReturnable ? 'on' : ''"
                      @click="isReturnable = !isReturnable"></span>
            </div>
        </div>
    </div>

    {{-- ═══ 2 · PRICING & GL ═══ --}}
    <div class="inv-card" style="margin-bottom:16px">
        <div class="inv-sec-head">
            <div class="inv-sec-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 000 7h5a3.5 3.5 0 010 7H6"/></svg>
            </div>
            <h2>{{ __('Pricing & GL') }}</h2>
            <span class="inv-rule"></span>
            <span style="font-size:11px;color:var(--muted);font-weight:700">{{ __('Margins & posting accounts') }}</span>
        </div>
        <div class="inv-g4">
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Purchase Price (cost)') }}</label>
                <input type="number" name="purchase_price" id="inv-cost" value="{{ old('purchase_price', $product?->purchase_price) }}"
                       class="inv-input" min="0" step="0.01" placeholder="0.00" x-on:input="calcMargin()">
                <div class="inv-form-hint">{{ $cs }}</div>
                @error('purchase_price') <div class="inv-form-err">{{ $message }}</div> @enderror
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Sales Price') }}</label>
                <input type="number" name="sales_price" id="inv-price" value="{{ old('sales_price', $product?->sales_price) }}"
                       class="inv-input" min="0" step="0.01" placeholder="0.00" x-on:input="calcMargin()">
                <div class="inv-form-hint">{{ $cs }}</div>
                @error('sales_price') <div class="inv-form-err">{{ $message }}</div> @enderror
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Margin') }}</label>
                <input type="text" id="inv-margin" class="inv-input" :value="marginDisplay" readonly
                       style="background:rgba(17,69,75,.04)">
                <div class="inv-form-hint">{{ __('Live: (price − cost) ÷ price') }}</div>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Reorder Point') }}</label>
                <input type="number" name="reorder_point" value="{{ old('reorder_point', $product?->reorder_point) }}"
                       class="inv-input" min="0" placeholder="0">
                @error('reorder_point') <div class="inv-form-err">{{ $message }}</div> @enderror
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Income Account') }}</label>
                <select name="income_account_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($incomeAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('income_account_id', $product?->income_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->code }} · {{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Expense Account') }}</label>
                <select name="expense_account_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($expenseAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('expense_account_id', $product?->expense_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->code }} · {{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Inventory Account') }}</label>
                <select name="inventory_asset_account_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($inventoryAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('inventory_asset_account_id', $product?->inventory_asset_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->code }} · {{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Price List') }}</label>
                <select name="price_list" class="inv-input">
                    <option value="retail" {{ old('price_list', $product?->price_list ?? 'retail') === 'retail' ? 'selected' : '' }}>{{ __('Retail (default)') }}</option>
                    <option value="wholesale" {{ old('price_list', $product?->price_list) === 'wholesale' ? 'selected' : '' }}>{{ __('Wholesale') }}</option>
                    <option value="vip" {{ old('price_list', $product?->price_list) === 'vip' ? 'selected' : '' }}>{{ __('VIP') }}</option>
                </select>
            </div>
        </div>
    </div>

    {{-- ═══ 3 · STOCK & REORDERING (conditional) ═══ --}}
    <div class="inv-card" style="margin-bottom:16px" x-show="trackInventory" x-transition x-cloak>
        <div class="inv-sec-head">
            <div class="inv-sec-ic">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12.01l8.73-5.05M12 22.08V12"/></svg>
            </div>
            <h2>{{ __('Stock & Reordering') }}</h2>
            <span class="inv-rule"></span>
            <span style="font-size:11px;color:var(--muted);font-weight:700">{{ __('Visible when Track Inventory is on') }}</span>
        </div>
        <div class="inv-g4">
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Opening Stock') }}</label>
                <input type="number" name="opening_stock" value="{{ old('opening_stock', $product?->opening_stock) }}"
                       class="inv-input" min="0" step="0.01" placeholder="0"
                       {{ $isEdit && $hasStockMovements ? 'readonly style="background:rgba(17,69,75,.04)"' : '' }}>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('As At Date') }}</label>
                <input type="date" name="opening_as_at" value="{{ old('opening_as_at', $product?->opening_as_at?->format('Y-m-d')) }}"
                       class="inv-input" {{ $isEdit && $hasStockMovements ? 'readonly style="background:rgba(17,69,75,.04)"' : '' }}>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Warehouse / Location') }}</label>
                <select name="warehouse_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($warehouses as $wh)
                    <option value="{{ $wh->id }}" {{ old('warehouse_id', $product?->warehouse_id) == $wh->id ? 'selected' : '' }}>{{ $wh->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Max Stock Level') }}</label>
                <input type="number" name="max_stock" value="{{ old('max_stock', $product?->max_stock) }}"
                       class="inv-input" min="0" placeholder="0">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Reorder Qty') }}</label>
                <input type="number" name="reorder_qty" value="{{ old('reorder_qty', $product?->reorder_qty) }}"
                       class="inv-input" min="0" placeholder="0">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Lead Time (days)') }}</label>
                <input type="number" name="lead_time_days" value="{{ old('lead_time_days', $product?->lead_time_days) }}"
                       class="inv-input" min="0" placeholder="0">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Default Supplier') }}</label>
                <select name="default_supplier_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($suppliers as $sup)
                    <option value="{{ $sup->id }}" {{ old('default_supplier_id', $product?->default_supplier_id) == $sup->id ? 'selected' : '' }}>{{ $sup->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Costing Method') }}</label>
                <select name="costing_method" class="inv-input">
                    <option value="weighted_average" {{ old('costing_method', $product?->costing_method ?? 'weighted_average') === 'weighted_average' ? 'selected' : '' }}>{{ __('Weighted Average') }}</option>
                    <option value="fifo" {{ old('costing_method', $product?->costing_method) === 'fifo' ? 'selected' : '' }}>{{ __('FIFO') }}</option>
                </select>
            </div>
        </div>
        <div style="padding:0 20px 4px">
            <div class="inv-toggle-row">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Low-stock alerts') }}</div>
                    <div class="inv-toggle-desc">{{ __('Notify when stock falls below reorder point') }}</div>
                </div>
                <input type="hidden" name="low_stock_alerts" value="0">
                <input type="checkbox" name="low_stock_alerts" value="1"
                       {{ old('low_stock_alerts', $product?->low_stock_alerts ?? true) ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track {{ old('low_stock_alerts', $product?->low_stock_alerts ?? true) ? 'on' : '' }}"
                      onclick="this.previousElementSibling.checked=!this.previousElementSibling.checked;this.classList.toggle('on')"></span>
            </div>
            <div class="inv-toggle-row">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Batch / expiry tracking') }}</div>
                    <div class="inv-toggle-desc">{{ __('Capture batch numbers and expiry dates on receipts') }}</div>
                </div>
                <input type="hidden" name="batch_expiry_tracking" value="0">
                <input type="checkbox" name="batch_expiry_tracking" value="1"
                       {{ old('batch_expiry_tracking', $product?->batch_expiry_tracking) ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track {{ old('batch_expiry_tracking', $product?->batch_expiry_tracking) ? 'on' : '' }}"
                      onclick="this.previousElementSibling.checked=!this.previousElementSibling.checked;this.classList.toggle('on')"></span>
            </div>
            <div class="inv-toggle-row">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Serial-number tracking') }}</div>
                    <div class="inv-toggle-desc">{{ __('Track individual serials on receipt and sale') }}</div>
                </div>
                <input type="hidden" name="serial_tracking" value="0">
                <input type="checkbox" name="serial_tracking" value="1"
                       {{ old('serial_tracking', $product?->serial_tracking) ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track {{ old('serial_tracking', $product?->serial_tracking) ? 'on' : '' }}"
                      onclick="this.previousElementSibling.checked=!this.previousElementSibling.checked;this.classList.toggle('on')"></span>
            </div>
        </div>
    </div>

    {{-- ═══ 4 · RETURNABLE PARAMETERS (conditional) ═══ --}}
    <div class="inv-card" style="margin-bottom:16px" x-show="isReturnable && trackInventory" x-transition x-cloak>
        <div class="inv-sec-head">
            <div class="inv-sec-ic" style="background:linear-gradient(135deg, var(--sec, #128F8E), var(--sec-2, #149897))">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="17" height="17"><path d="M8 2h8l2 18H6L8 2z"/><path d="M12 6v4"/><circle cx="12" cy="15" r="2"/></svg>
            </div>
            <h2>{{ __('Returnable Parameters') }}</h2>
            <span class="inv-rule"></span>
            <span style="font-size:11px;color:var(--muted);font-weight:700">{{ __('Deposit & redemption settings') }}</span>
        </div>
        <div class="inv-g4">
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Container Type') }}</label>
                <select name="container_type" class="inv-input">
                    <option value="bottle" {{ old('container_type', $ret?->container_type ?? 'bottle') === 'bottle' ? 'selected' : '' }}>{{ __('Bottle') }}</option>
                    <option value="crate" {{ old('container_type', $ret?->container_type) === 'crate' ? 'selected' : '' }}>{{ __('Crate (24)') }}</option>
                    <option value="keg" {{ old('container_type', $ret?->container_type) === 'keg' ? 'selected' : '' }}>{{ __('Keg') }}</option>
                    <option value="cylinder" {{ old('container_type', $ret?->container_type) === 'cylinder' ? 'selected' : '' }}>{{ __('Cylinder') }}</option>
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Deposit Value') }} <span style="color:var(--red-2)">*</span></label>
                <input type="number" name="deposit_value" value="{{ old('deposit_value', $ret?->deposit_value) }}"
                       class="inv-input" min="0" step="0.01" placeholder="{{ __('e.g. 200') }}">
                <div class="inv-form-hint">{{ $cs }}</div>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Deposit Tax Handling') }}</label>
                <select name="deposit_tax_handling" class="inv-input">
                    <option value="excluded" {{ old('deposit_tax_handling', $ret?->deposit_tax_handling ?? 'excluded') === 'excluded' ? 'selected' : '' }}>{{ __('Deposit excluded from tax') }}</option>
                    <option value="taxed" {{ old('deposit_tax_handling', $ret?->deposit_tax_handling) === 'taxed' ? 'selected' : '' }}>{{ __('Deposit taxed at item rate') }}</option>
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Return Window (days)') }}</label>
                <input type="number" name="return_window_days" value="{{ old('return_window_days', $ret?->return_window_days ?? 30) }}"
                       class="inv-input" min="1" placeholder="30">
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Linked Empty Container') }}</label>
                <select name="linked_empty_item_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @php $allProducts = \App\Models\Product::forCompany(session('current_company_id'))->where('id', '!=', $product?->id ?? 0)->orderBy('name')->get(['id', 'name', 'sku']); @endphp
                    @foreach($allProducts as $p)
                    <option value="{{ $p->id }}" {{ old('linked_empty_item_id', $ret?->linked_empty_item_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}{{ $p->sku ? " ({$p->sku})" : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Linked Filled Product') }}</label>
                <select name="linked_filled_item_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($allProducts as $p)
                    <option value="{{ $p->id }}" {{ old('linked_filled_item_id', $ret?->linked_filled_item_id) == $p->id ? 'selected' : '' }}>{{ $p->name }}{{ $p->sku ? " ({$p->sku})" : '' }}</option>
                    @endforeach
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Required Return') }}</label>
                <select name="required_return" class="inv-input">
                    <option value="one_to_one" {{ old('required_return', $ret?->required_return ?? 'one_to_one') === 'one_to_one' ? 'selected' : '' }}>{{ __('Yes — 1:1 exchange') }}</option>
                    <option value="free" {{ old('required_return', $ret?->required_return) === 'free' ? 'selected' : '' }}>{{ __('No — free return') }}</option>
                </select>
            </div>
            <div class="inv-form-group">
                <label class="inv-form-label">{{ __('Container Stock Account') }}</label>
                <select name="container_stock_account_id" class="inv-input">
                    <option value="">{{ __('None') }}</option>
                    @foreach($inventoryAccounts as $acc)
                    <option value="{{ $acc->id }}" {{ old('container_stock_account_id', $ret?->container_stock_account_id) == $acc->id ? 'selected' : '' }}>{{ $acc->code }} · {{ $acc->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div style="padding:0 20px 4px">
            <div class="inv-toggle-row">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Container stock tracking') }}</div>
                    <div class="inv-toggle-desc">{{ __('Track empties on hand (intake +N, redemption −N)') }}</div>
                </div>
                <input type="hidden" name="container_stock_tracking" value="0">
                <input type="checkbox" name="container_stock_tracking" value="1"
                       {{ old('container_stock_tracking', $ret?->container_stock_tracking ?? true) ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track {{ old('container_stock_tracking', $ret?->container_stock_tracking ?? true) ? 'on' : '' }}"
                      onclick="this.previousElementSibling.checked=!this.previousElementSibling.checked;this.classList.toggle('on')"></span>
            </div>
            <div class="inv-toggle-row">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Allow cash refund on return') }}</div>
                    <div class="inv-toggle-desc">{{ __('Pay cash instead of store credit at intake') }}</div>
                </div>
                <input type="hidden" name="allow_cash_refund" value="0">
                <input type="checkbox" name="allow_cash_refund" value="1"
                       {{ old('allow_cash_refund', $ret?->allow_cash_refund) ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track {{ old('allow_cash_refund', $ret?->allow_cash_refund) ? 'on' : '' }}"
                      onclick="this.previousElementSibling.checked=!this.previousElementSibling.checked;this.classList.toggle('on')"></span>
            </div>
        </div>
        <div style="padding:0 20px 20px">
            <div class="gl-note">GL wiring: intake posts Dr 1320 Returnable Containers / Cr 2300 Customer Bottle Credits · redemption at checkout cancels the filled-product deposit (Dr 2300 / Cr deposit revenue) · unused credit carries on the BRR (valid per return window).</div>
        </div>
    </div>

    {{-- ═══ 5 · STATUS ═══ --}}
    <div class="inv-card" style="margin-bottom:16px">
        <div style="padding:16px 20px">
            <div class="inv-toggle-row" style="border:none;padding:4px 0">
                <div class="inv-toggle-info">
                    <div class="inv-toggle-title">{{ __('Active') }}</div>
                    <div class="inv-toggle-desc">{{ __('Inactive items are hidden from transactions') }}</div>
                </div>
                <input type="hidden" name="is_active" value="0">
                <input type="checkbox" name="is_active" value="1"
                       {{ old('is_active', $activeOn) ? 'checked' : '' }} style="display:none">
                <span class="inv-toggle-track {{ old('is_active', $activeOn) ? 'on' : '' }}"
                      onclick="this.previousElementSibling.checked=!this.previousElementSibling.checked;this.classList.toggle('on')"></span>
            </div>
        </div>
    </div>
</form>

{{-- ═══ SCANNER MODAL ═══ --}}
<div id="inv-scanner-overlay" style="display:none;position:fixed;inset:0;background:rgba(8,40,44,.85);place-items:center;z-index:90;padding:20px"
     onclick="if(event.target===this)invCloseScanner()">
    <div style="width:min(420px,100%);background:#000;border-radius:20px;overflow:hidden;box-shadow:0 30px 60px -20px rgba(0,0,0,.6)">
        <div style="display:flex;justify-content:space-between;align-items:center;padding:16px 20px;color:#fff">
            <h3 style="font-size:14px;font-weight:800">{{ __('Scan QR / Barcode') }}</h3>
            <button type="button" onclick="invCloseScanner()" style="border:none;background:none;color:#fff;font-size:20px;cursor:pointer">✕</button>
        </div>
        <div style="position:relative;height:340px;background:#1a1a1a">
            <div id="inv-scanner-reader" style="width:100%;height:100%"></div>
            <div style="position:absolute;inset:36px;border:3px solid rgba(18,143,142,.5);border-radius:16px;pointer-events:none"></div>
            <div class="inv-scanner-line"></div>
            <div style="position:absolute;bottom:52px;left:0;right:0;text-align:center;color:#fff;font-size:13px;font-weight:700;text-shadow:0 2px 8px rgba(0,0,0,.6)">{{ __('Align code within frame') }}</div>
        </div>
        <div style="padding:16px 20px;background:rgba(0,0,0,.8);display:flex;gap:10px;justify-content:center">
            <button type="button" class="inv-btn inv-btn-ghost" onclick="invCloseScanner()">{{ __('Cancel') }}</button>
            <button type="button" class="inv-btn inv-btn-cta" id="inv-scanner-start-btn" onclick="invStartCamera()">{{ __('Start Camera') }}</button>
        </div>
    </div>
</div>

@push('styles')
<style>
    .inv-scanner-line {
        position: absolute; left: 36px; right: 36px; height: 2px;
        background: var(--sec, #128F8E); box-shadow: 0 0 12px var(--sec, #128F8E);
        animation: invScan 2s ease-in-out infinite; pointer-events: none;
    }
    @keyframes invScan { 0%,100% { top: 36px } 50% { top: calc(100% - 38px) } }
    .gl-note {
        border: 1px dashed rgba(18,143,142,.5); background: rgba(18,143,142,.06);
        border-radius: 12px; padding: 10px 14px; font-size: 11.5px; color: var(--sec);
        font-weight: 700;
    }
</style>
@endpush

@push('scripts')
<script>
function itemForm() {
    return {
        trackInventory: @js((bool) $trackOn),
        isReturnable: @js((bool) $retOn),
        marginDisplay: '—',
        init() {
            this.calcMargin();
            this.$watch('trackInventory', (v) => { if (!v) this.isReturnable = false; });
        },
        calcMargin() {
            const cost = parseFloat(document.getElementById('inv-cost')?.value) || 0;
            const price = parseFloat(document.getElementById('inv-price')?.value) || 0;
            this.marginDisplay = price > 0 ? (((price - cost) / price) * 100).toFixed(1) + '%' : '—';
        }
    };
}

function invGenSKU() {
    const el = document.getElementById('inv-sku');
    if (el && !el.readOnly) el.value = 'SKU-' + String(Math.floor(1000 + Math.random() * 9000));
}
function invGenBar() {
    const el = document.getElementById('inv-barcode');
    if (el) el.value = '600' + String(Math.floor(1000000000 + Math.random() * 9000000000));
}
function invOpenScanner() {
    document.getElementById('inv-scanner-overlay').style.display = 'grid';
}
function invCloseScanner() {
    document.getElementById('inv-scanner-overlay').style.display = 'none';
    if (window._invHtml5Qr) { try { window._invHtml5Qr.stop(); } catch(e){} }
}
function invStartCamera() {
    const btn = document.getElementById('inv-scanner-start-btn');
    btn.textContent = '{{ __("Starting…") }}';
    const script = document.createElement('script');
    script.src = 'https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js';
    script.onload = function() {
        if (!window._invHtml5Qr) window._invHtml5Qr = new Html5Qrcode('inv-scanner-reader');
        window._invHtml5Qr.start(
            { facingMode: 'environment' },
            { fps: 10, qrbox: { width: 250, height: 150 } },
            function(decoded) {
                document.getElementById('inv-barcode').value = decoded;
                invCloseScanner();
            },
            function() {}
        ).catch(function() {
            btn.textContent = '{{ __("Camera unavailable") }}';
        });
    };
    document.head.appendChild(script);
}
</script>
@endpush
