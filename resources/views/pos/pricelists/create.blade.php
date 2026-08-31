<x-app-layout>
    @php
        $oldItems = old('items', []);
        $rowSeed = collect($oldItems)->map(fn ($item) => [
            'product_id' => $item['product_id'] ?? '',
            'unit_price' => $item['unit_price'] ?? '',
            'min_qty' => $item['min_qty'] ?? '',
            'max_qty' => $item['max_qty'] ?? '',
        ])->values()->all();

        if (empty($rowSeed)) {
            $rowSeed = [['product_id' => '', 'unit_price' => '', 'min_qty' => '', 'max_qty' => '']];
        }

        $productOptions = $products->map(fn ($p) => [
            'id' => $p->id,
            'name' => $p->name . ($p->sku ? ' (' . $p->sku . ')' : ''),
            'price' => (float) $p->sales_price,
        ])->values();
    @endphp

    <div class="pos">
        <div class="pos-page-head">
            <div>
                <h1>Create Price List</h1>
                <p class="pos-sub">Define alternate pricing for a channel, customer group, or period.</p>
            </div>
            <div class="pos-actions">
                <a href="{{ route('pos.pricelists.index') }}" class="pos-btn pos-btn-ghost">Cancel</a>
                <button type="submit" form="pricelist-form" class="pos-btn pos-btn-cta">Save Price List</button>
            </div>
        </div>

        @if($errors->any())
            <x-feedback.alert variant="error" class="mb-5" title="Please fix the following:">
                {{ $errors->first() }}
            </x-feedback.alert>
        @endif

        <form id="pricelist-form" method="POST" action="{{ route('pos.pricelists.store') }}"
            x-data="{
                rows: {{ Js::from($rowSeed) }},
                products: {{ Js::from($productOptions) }},
                addRow() { this.rows.push({ product_id: '', unit_price: '', min_qty: '', max_qty: '' }) },
                removeRow(i) { this.rows.splice(i, 1) },
                priceOf(id) { const p = this.products.find(p => p.id == id); return p ? p.price : '' },
                onProductChange(row) { if (!row.unit_price && row.product_id) row.unit_price = this.priceOf(row.product_id) }
            }">
            @csrf

            <div class="pos-card">
                <div class="pos-card-h"><h2>Details</h2></div>
                <div class="pos-card-b">
                    <div class="grid gap-x-4 gap-y-4 md:grid-cols-2">
                        <div>
                            <label for="name" class="mb-[7px] block text-[10.5px] font-extrabold uppercase tracking-[0.09em]" style="color:var(--muted)">Name <span style="color:#b91c1c">*</span></label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" required
                                class="h-[42px] w-full rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[13.5px] focus:border-[color:var(--sec)] focus:outline-none"
                                placeholder="e.g. Wholesale Q3">
                        </div>
                        <div>
                            <label for="type" class="mb-[7px] block text-[10.5px] font-extrabold uppercase tracking-[0.09em]" style="color:var(--muted)">Type <span style="color:#b91c1c">*</span></label>
                            <select id="type" name="type" required
                                class="h-[42px] w-full rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[13.5px] focus:border-[color:var(--sec)] focus:outline-none">
                                @foreach(['retail' => 'Retail', 'wholesale' => 'Wholesale', 'vip' => 'VIP', 'custom' => 'Custom'] as $value => $label)
                                    <option value="{{ $value }}" @selected(old('type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="applies_to" class="mb-[7px] block text-[10.5px] font-extrabold uppercase tracking-[0.09em]" style="color:var(--muted)">Applies To <span style="color:#b91c1c">*</span></label>
                            <input id="applies_to" type="text" name="applies_to" value="{{ old('applies_to') }}" required maxlength="255"
                                class="h-[42px] w-full rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[13.5px] focus:border-[color:var(--sec)] focus:outline-none"
                                placeholder="e.g. wholesale_customers">
                        </div>
                        <div>
                            <label class="mb-[7px] block text-[10.5px] font-extrabold uppercase tracking-[0.09em]" style="color:var(--muted)">Description</label>
                            <textarea name="description" rows="1"
                                class="min-h-[42px] w-full resize-y rounded-[10px] border border-[color:var(--border)] bg-white px-3 py-[10px] text-[13.5px] focus:border-[color:var(--sec)] focus:outline-none"
                                placeholder="Optional notes">{{ old('description') }}</textarea>
                        </div>
                        <div>
                            <label for="effective_from" class="mb-[7px] block text-[10.5px] font-extrabold uppercase tracking-[0.09em]" style="color:var(--muted)">Effective From</label>
                            <input id="effective_from" type="date" name="effective_from" value="{{ old('effective_from') }}"
                                class="h-[42px] w-full rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[13.5px] focus:border-[color:var(--sec)] focus:outline-none">
                        </div>
                        <div>
                            <label for="effective_until" class="mb-[7px] block text-[10.5px] font-extrabold uppercase tracking-[0.09em]" style="color:var(--muted)">Effective Until</label>
                            <input id="effective_until" type="date" name="effective_until" value="{{ old('effective_until') }}"
                                class="h-[42px] w-full rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[13.5px] focus:border-[color:var(--sec)] focus:outline-none">
                        </div>
                    </div>
                    <input type="hidden" name="is_active" value="0">
                    <label class="mt-4 flex cursor-pointer items-center gap-3">
                        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', true))
                            class="h-4 w-4 rounded border-[color:var(--border)]">
                        <span class="text-[12.5px] font-bold" style="color:var(--ink)">Active — available at checkout immediately</span>
                    </label>
                </div>
            </div>

            <div class="pos-card">
                <div class="pos-card-h">
                    <h2>Item Prices</h2>
                    <button type="button" @click="addRow()">+ Add Item</button>
                </div>
                <div class="pos-tbl-wrap">
                    <table class="pos-tbl">
                        <thead>
                            <tr>
                                <th style="width:40%">Product</th>
                                <th class="r">Unit Price</th>
                                <th class="r">Min Qty</th>
                                <th class="r">Max Qty</th>
                                <th class="r"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="(row, idx) in rows" :key="idx">
                                <tr>
                                    <td>
                                        <select x-model="row.product_id"
                                            :name="'items[' + idx + '][product_id]'"
                                            @change="onProductChange(row)"
                                            class="h-[38px] w-full rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-[12.5px] focus:border-[color:var(--sec)] focus:outline-none">
                                            <option value="">Select a product…</option>
                                            <template x-for="option in products" :key="option.id">
                                                <option :value="option.id" x-text="option.name"></option>
                                            </template>
                                        </select>
                                    </td>
                                    <td class="r">
                                        <input type="number" step="0.01" min="0" x-model="row.unit_price"
                                            :name="'items[' + idx + '][unit_price]'"
                                            class="h-[38px] w-[120px] rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-right text-[12.5px] tabular-nums focus:border-[color:var(--sec)] focus:outline-none"
                                            placeholder="0.00">
                                    </td>
                                    <td class="r">
                                        <input type="number" step="1" min="1" x-model="row.min_qty"
                                            :name="'items[' + idx + '][min_qty]'"
                                            class="h-[38px] w-[90px] rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-right text-[12.5px] tabular-nums focus:border-[color:var(--sec)] focus:outline-none"
                                            placeholder="—">
                                    </td>
                                    <td class="r">
                                        <input type="number" step="1" min="1" x-model="row.max_qty"
                                            :name="'items[' + idx + '][max_qty]'"
                                            class="h-[38px] w-[90px] rounded-[10px] border border-[color:var(--border)] bg-white px-3 text-right text-[12.5px] tabular-nums focus:border-[color:var(--sec)] focus:outline-none"
                                            placeholder="—">
                                    </td>
                                    <td class="r">
                                        <button type="button" @click="removeRow(idx)" title="Remove item"
                                            class="grid h-[30px] w-[30px] place-items-center rounded-lg text-[15px] hover:bg-[rgba(185,28,28,.06)]" style="color:#b91c1c">×</button>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div class="flex items-center justify-between px-5 py-3 text-[12px]" style="color:var(--faint)">
                    <span>Leave unit price empty to fall back to the product's default sales price at checkout.</span>
                    <span x-text="rows.length + ' item' + (rows.length === 1 ? '' : 's')"></span>
                </div>
            </div>
        </form>
    </div>
</x-app-layout>
