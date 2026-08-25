<x-app-layout>
    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>Edit Price List</h1>
                <div class="pos-sub">{{ $priceList->name }}</div>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.pricelists.show', $priceList) }}" class="pos-btn pos-btn-ghost">Cancel</a>
                <button type="submit" form="pricelist-form" class="pos-btn pos-btn-cta">Save Changes</button>
            </div>
        </div>

        <form id="pricelist-form" method="POST" action="{{ route('pos.pricelists.update', $priceList) }}">
            @csrf
            @method('PATCH')

            @if($errors->any())
                <div class="pos-card" style="margin-bottom:16px;border:1px solid var(--pos-red)">
                    <div class="pos-pad">
                        @foreach($errors->all() as $error)
                            <div style="color:var(--pos-red);font-size:13px">{{ $error }}</div>
                        @endforeach
                    </div>
                </div>
            @endif

                <div class="pos-card" style="margin-bottom:16px">
                    <div class="pos-card-h">
                        <span class="pos-step">Details</span>
                    </div>
                    <div class="pos-pad">
                        <div class="pos-g3">
                            <div class="pos-f">
                                <label>Name <span style="color:var(--pos-red)">*</span></label>
                                <input type="text" name="name" class="pos-in" value="{{ old('name', $priceList->name) }}" required>
                            </div>
                            <div class="pos-f">
                                <label>Type <span style="color:var(--pos-red)">*</span></label>
                                <select name="type" class="pos-in" required>
                                    @foreach(['retail', 'wholesale', 'vip', 'custom'] as $type)
                                        <option value="{{ $type }}" {{ old('type', $priceList->type) === $type ? 'selected' : '' }}>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="pos-f">
                                <label>Applies To <span style="color:var(--pos-red)">*</span></label>
                                <input type="text" name="applies_to" class="pos-in" value="{{ old('applies_to', $priceList->applies_to) }}" required>
                            </div>
                        </div>
                        <div class="pos-g3" style="margin-top:12px">
                            <div class="pos-f">
                                <label>Effective From</label>
                                <input type="date" name="effective_from" class="pos-in" value="{{ old('effective_from', $priceList->effective_from?->format('Y-m-d')) }}">
                            </div>
                            <div class="pos-f">
                                <label>Effective Until</label>
                                <input type="date" name="effective_until" class="pos-in" value="{{ old('effective_until', $priceList->effective_until?->format('Y-m-d')) }}">
                            </div>
                            <div class="pos-f">
                                <label style="display:block;margin-bottom:8px">&nbsp;</label>
                                <div style="display:flex;align-items:center;gap:8px">
                                    <input type="checkbox" name="is_active" value="1" {{ old('is_active', $priceList->is_active) ? 'checked' : '' }} class="pos-toggle-input">
                                    <label style="font-size:12px;color:var(--pos-muted)">Active</label>
                                </div>
                            </div>
                        </div>
                        <div class="pos-f" style="margin-top:12px">
                            <label>Description</label>
                            <textarea name="description" class="pos-in" rows="2">{{ old('description', $priceList->description) }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Line Items --}}
                <div class="pos-card" x-data="priceListItems()">
                    <div class="pos-card-h">
                        <span class="pos-step">Price List Items</span>
                        <div class="pos-right">
                            <button type="button" @click="addItem()" class="pos-btn pos-btn-ghost pos-btn-sm">+ Add Item</button>
                        </div>
                    </div>
                    <div class="pos-li-wrap">
                        <table class="pos-tbl">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>SKU</th>
                                    <th class="num">Unit Price</th>
                                    <th class="num">Min Qty</th>
                                    <th class="num">Max Qty</th>
                                    <th class="num">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="(item, idx) in items" :key="idx">
                                    <tr>
                                        <td>
                                            <select :name="'items[' + idx + '][product_id]'" class="pos-in" x-model="item.product_id" @change="onProductChange(idx)" required>
                                                <option value="">— Select —</option>
                                                @foreach($products as $product)
                                                    <option value="{{ $product->id }}" data-price="{{ $product->sales_price }}">{{ $product->name }} ({{ $product->sku }})</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td class="pos-mono pos-em" x-text="getSku(idx)">—</td>
                                        <td>
                                            <input type="number" :name="'items[' + idx + '][unit_price]'" class="pos-in" x-model="item.unit_price" step="0.01" min="0" required>
                                        </td>
                                        <td>
                                            <input type="number" :name="'items[' + idx + '][min_qty]'" class="pos-in" x-model="item.min_qty" min="1" style="width:80px">
                                        </td>
                                        <td>
                                            <input type="number" :name="'items[' + idx + '][max_qty]'" class="pos-in" x-model="item.max_qty" min="1" placeholder="∞" style="width:80px">
                                        </td>
                                        <td class="num">
                                            <button type="button" @click="removeItem(idx)" class="pos-ibtn" title="Remove">✕</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="6" class="pos-empty">
                                        <h3>No items</h3>
                                        <p>Click "Add Item" to add products to this price list.</p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>
    function priceListItems() {
        return {
            items: @json($priceList->items->map(fn($i) => ['product_id' => $i->product_id, 'unit_price' => $i->unit_price, 'min_qty' => $i->min_qty, 'max_qty' => $i->max_qty])),

            addItem() {
                this.items.push({ product_id: '', unit_price: 0, min_qty: 1, max_qty: null });
            },
            removeItem(idx) {
                this.items.splice(idx, 1);
            },
            onProductChange(idx) {
                const select = document.querySelector(`[name="items[${idx}][product_id]"]`);
                const opt = select.options[select.selectedIndex];
                if (opt && opt.dataset.price) {
                    this.items[idx].unit_price = parseFloat(opt.dataset.price) || 0;
                }
            },
            getSku(idx) {
                const select = document.querySelector(`[name="items[${idx}][product_id]"]`);
                if (!select) return '—';
                const opt = select.options[select.selectedIndex];
                if (!opt || !opt.text) return '—';
                const match = opt.text.match(/\((.+)\)$/);
                return match ? match[1] : '—';
            }
        };
    }
    </script>
</x-app-layout>
