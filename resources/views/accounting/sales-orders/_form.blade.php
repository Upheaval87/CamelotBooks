@php
    $order = $order ?? null;
    $isEdit = $isEdit ?? (bool) $order;
    $formAction = $formAction ?? ($isEdit ? route('accounting.sales-orders.update', $order) : route('accounting.sales-orders.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.sales-orders.show', $order) : route('accounting.sales-orders.index'));
    $title = $title ?? ($isEdit ? 'Edit Sales Order' : 'Create Sales Order');
    $submitLabel = $submitLabel ?? ($isEdit ? 'Save Changes' : 'Save');

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $defaultIncomeAccountId = $defaultIncomeAccountId ?? ($incomeAccounts->first()?->id ?? '');

    $oldCustomerId = old('customer_id', $order?->customer_id ?? ($selectedCustomerId ?? ''));
    $selectedCustomer = $customers->firstWhere('id', (int) $oldCustomerId);

    $currencyCodes = $currencies->pluck('code')->all();
    $defaultCurrency = in_array('MWK', $currencyCodes) ? 'MWK' : ($currencies->first()?->code ?? 'USD');
    $selectedCurrency = old('currency', $order?->currency ?? $defaultCurrency);
    $selectedBranchId = old('branch_id', $order?->branch_id ?? $branches->first()?->id ?? '');
    $selectedCostCenterId = old('cost_center_id', $order?->cost_center_id ?? '');
    $selectedCostCenterLabel = $selectedCostCenterId ? ($costCenters->firstWhere('id', (int) $selectedCostCenterId)?->name ?? '') : '';

    $orderAttachments = $orderAttachments ?? ($order?->attachments ?? collect());

    if ($isEdit) {
        $customerLine = $order?->customer?->name ?? __('No customer');
        $orderedDate = $order?->order_date?->format('M d, Y') ?? '—';
        $deliveryDate = $order?->expected_delivery_date?->format('M d, Y') ?? '—';
        $subtitle = "{$customerLine} · " . __('ordered') . " {$orderedDate} · " . __('delivery') . " {$deliveryDate}";
    } else {
        $subtitle = $subtitle ?? __('Capture customer orders & track fulfilment.');
    }

    $linesData = [];
    if (old('lines')) {
        foreach (array_values(old('lines')) as $l) {
            $gross = ((float) ($l['quantity'] ?? 0)) * ((float) ($l['unit_price'] ?? 0));
            $flat = (float) ($l['discount'] ?? 0);
            $linesData[] = [
                'product_id' => $l['product_id'] ?? '',
                'label' => $products->firstWhere('id', (int) ($l['product_id'] ?? 0))?->name ?? '',
                'sku' => $products->firstWhere('id', (int) ($l['product_id'] ?? 0))?->sku ?? '',
                'description' => $l['description'] ?? '',
                'quantity' => (float) ($l['quantity'] ?? 1),
                'unit_price' => (float) ($l['unit_price'] ?? 0),
                'discount' => $flat,
                'discount_pct' => $gross > 0 ? round($flat / $gross * 100, 2) : 0,
                'tax_rate' => (float) ($l['tax_rate'] ?? 0),
                'income_account_id' => $l['income_account_id'] ?? $defaultIncomeAccountId,
            ];
        }
    } elseif ($order) {
        $linesData = $order->lines->map(function ($l) {
            $gross = ((float) $l->quantity) * ((float) $l->unit_price);
            $flat = (float) $l->discount;

            return [
                'product_id' => $l->product_id ?? '',
                'label' => $l->product?->name ?? '',
                'sku' => $l->product?->sku ?? '',
                'description' => $l->description,
                'quantity' => (float) $l->quantity,
                'unit_price' => (float) $l->unit_price,
                'discount' => $flat,
                'discount_pct' => $gross > 0 ? round($flat / $gross * 100, 2) : 0,
                'tax_rate' => (float) $l->tax_rate,
                'income_account_id' => $l->income_account_id,
            ];
        })->all();
    }
@endphp

<div class="q2">
    {{-- §2/§3 sticky page head --}}
    <div class="q2-head q2-head--sticky">
        <div class="min-w-0">
            <div class="flex items-center gap-2.5 flex-wrap">
                <h1 class="q2-title">{{ $title }}</h1>
                @if ($isEdit)
                    <span class="q2-mono" style="padding:.25rem .625rem;border:1px solid var(--line,#E2ECEC);border-radius:.5rem;background:#fff">{{ $order->sales_order_number }}</span>
                    <span class="q2-badge q2-badge--{{ $order->status }}"><span class="q2-dot"></span>{{ __(ucfirst($order->status)) }}</span>
                @endif
            </div>
            <p class="q2-sub">{{ $subtitle }}</p>
        </div>
        <div class="q2-head-actions">
            <a href="{{ $cancelRoute }}" class="q2-btn q2-btn--ghost">{{ __('Cancel') }}</a>
            @if($isEdit)
                @if($order->created_by && (int) $order->created_by !== (int) auth()->id())
                    <button type="submit" form="sales-order-delete-form" class="q2-btn q2-btn--danger">{{ __('Delete') }}</button>
                @endif
                <div class="q2-seg">
                    <button type="submit" name="action" value="save" form="sales-order-form" class="q2-btn q2-btn--sec">{{ $submitLabel }}</button>
                    <button type="submit" name="action" value="send" form="sales-order-form" class="q2-btn q2-btn--cta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('Save & Send') }}
                    </button>
                </div>
            @else
                <button type="submit" name="action" value="save_and_new" form="sales-order-form" class="q2-btn q2-btn--ghost">{{ __('Save & New') }}</button>
                <div class="q2-seg">
                    <button type="submit" name="action" value="save_draft" form="sales-order-form" class="q2-btn q2-btn--ghost">{{ __('Save Draft') }}</button>
                    <button type="submit" name="action" value="save" form="sales-order-form" class="q2-btn q2-btn--sec">{{ $submitLabel }}</button>
                    <button type="submit" name="action" value="send" form="sales-order-form" class="q2-btn q2-btn--cta">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M22 2L11 13M22 2l-7 20-4-9-9-4 20-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        {{ __('Save & Send') }}
                    </button>
                </div>
            @endif
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="sales-order-form" class="q2-form" enctype="multipart/form-data" novalidate data-customer-name="{{ $selectedCustomer?->name ?? '' }}">
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="q2-shell q2-shell--form">
            <div class="q2-main">

                {{-- (a) customer --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Customer Information') }}</h2>
                    </div>
                    <div class="q2-g4 mt-5">
                        <div class="q2-field" style="grid-column: span 2">
                            <label for="customer_id" class="q2-label">{{ __('Customer') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <x-scoped-search-field
                                name="customer_id"
                                entity="customer"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                :value="old('customer_id', $oldCustomerId)"
                                :label="old('customer_name', $selectedCustomer?->name ?? '')"
                                placeholder="{{ __('Search customers…') }}"
                                on-select="soCustomerSelected"
                                required
                            />
                            <x-input-error :messages="$errors->get('customer_id')" />
                        </div>
                        <div class="q2-field">
                            <label for="expected_delivery_date" class="q2-label">{{ __('Expected Delivery') }}</label>
                            <input id="expected_delivery_date" name="expected_delivery_date" type="date" class="q2-input" value="{{ old('expected_delivery_date', $order?->expected_delivery_date?->format('Y-m-d') ?? '') }}" />
                            <x-input-error :messages="$errors->get('expected_delivery_date')" />
                        </div>
                        <div class="q2-field">
                            <label for="currency" class="q2-label">{{ __('Currency') }}</label>
                            <select id="currency" name="currency" class="q2-select">
                                @forelse ($currencies as $curOption)
                                    <option value="{{ $curOption->code }}" @selected($selectedCurrency === $curOption->code)>{{ $curOption->code }} - {{ $curOption->name }}</option>
                                @empty
                                    <option value="USD" @selected($selectedCurrency === 'USD')>USD - US Dollar</option>
                                @endforelse
                            </select>
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Contact Person') }}</label>
                            <input type="text" class="q2-input" id="so-contact" value="{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Email') }}</label>
                            <input type="email" class="q2-input" id="so-email" value="{{ $selectedCustomer?->email ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Phone') }}</label>
                            <input type="text" class="q2-input" id="so-phone" value="{{ $selectedCustomer?->phone ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Payment Terms') }}</label>
                            <input type="text" class="q2-input" id="so-terms" value="{{ $selectedCustomer?->payment_terms ?? '' }}" readonly />
                        </div>
                    </div>
                    <p class="q2-hint mt-4">{{ __('Contact details shown are from the customer record.') }}</p>
                </section>

                {{-- (b) order info --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Order Information') }}</h2>
                    </div>
                    <div class="q2-g4 mt-5">
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Sales Order №') }}</label>
                            <input type="text" class="q2-input" value="{{ $order?->sales_order_number ?? '' }}" placeholder="{{ __('Auto-assigned on save') }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label for="order_date" class="q2-label">{{ __('Order Date') }} <span style="color:var(--red-2,#B91C1C)">*</span></label>
                            <input id="order_date" name="order_date" type="date" class="q2-input" value="{{ old('order_date', $order?->order_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('order_date')" />
                        </div>
                        <div class="q2-field">
                            <label for="reference" class="q2-label">{{ __('Reference №') }}</label>
                            <input id="reference" name="reference" type="text" class="q2-input" value="{{ old('reference', $order?->reference ?? '') }}" placeholder="{{ __('Optional') }}" />
                            <x-input-error :messages="$errors->get('reference')" />
                        </div>
                        <div class="q2-field">
                            <label for="branch_id" class="q2-label">{{ __('Branch') }}</label>
                            <select id="branch_id" name="branch_id" class="q2-select">
                                <option value="">-- {{ __('Select branch') }} --</option>
                                @foreach ($branches as $branch)
                                    <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('branch_id')" />
                        </div>
                        <div class="q2-field">
                            <label for="cost_center_id" class="q2-label">{{ __('Cost Centre') }}</label>
                            <x-scoped-search-field
                                name="cost_center_id"
                                entity="cost-center"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                :value="old('cost_center_id', $selectedCostCenterId)"
                                :label="old('cost_center_id', $selectedCostCenterLabel)"
                                placeholder="{{ __('None') }}"
                            />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Department') }}</label>
                            <input type="text" class="q2-input" placeholder="Operations" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Prepared By') }}</label>
                            <input type="text" class="q2-input" value="{{ $order?->createdByUser?->name ?? auth()->user()?->name ?? '' }}" readonly />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Project') }}</label>
                            <input type="text" class="q2-input" placeholder="{{ __('Optional') }}" readonly />
                        </div>
                    </div>
                    <p class="q2-hint mt-4">{{ __('Sales Order № is auto-assigned on save. Department & Project are display-only.') }}</p>
                </section>

                {{-- (c) line items --}}
                <section class="q2-sec relative z-30">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Line Items') }}</h2>
                        <button type="button" id="so-add-line" class="q2-btn q2-btn--ghost q2-btn--sm" style="margin-left:auto">＋ {{ __('Add Line') }}</button>
                    </div>

                    <div class="mt-4 border border-shell round-thead-clip bg-[#fbfcfe]">
                        <table id="so-lines-table" class="q2-tbl w-full text-[0.929rem] table-fixed">
                            <thead>
                                <tr>
                                    <th style="width:12%">{{ __('Item Code') }}</th>
                                    <th style="width:24%">{{ __('Item Name') }}</th>
                                    <th style="width:24%">{{ __('Description') }}</th>
                                    <th style="width:7%" class="q2-right">{{ __('Qty') }}</th>
                                    <th style="width:11%" class="q2-right">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th style="width:8%" class="q2-right">{{ __('Disc %') }}</th>
                                    <th style="width:10%" class="q2-right">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th style="width:4%"></th>
                                </tr>
                            </thead>
                            <tbody id="so-lines-body"></tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </section>

                {{-- (d) notes --}}
                <section class="q2-sec">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Notes') }}</h2>
                    </div>
                    <div class="q2-g2 mt-5">
                        <div class="q2-field">
                            <label for="memo" class="q2-label">{{ __('Customer Notes') }}</label>
                            <textarea id="memo" name="memo" class="q2-input" style="height:auto;min-height:6rem;padding:.75rem .875rem;resize:vertical" placeholder="{{ __('Printed on the sales order PDF…') }}">{{ old('memo', $order?->memo ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('memo')" />
                        </div>
                        <div class="q2-field">
                            <label class="q2-label">{{ __('Internal Notes') }}</label>
                            <textarea class="q2-input" style="height:auto;min-height:6rem;padding:.75rem .875rem;resize:vertical" placeholder="{{ __('Visible to your team only…') }}" readonly></textarea>
                        </div>
                    </div>
                </section>

                {{-- (e) attachments --}}
                <section class="q2-sec" id="attachments">
                    <div class="q2-sec-head">
                        <span class="q2-sec-ic"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <h2 class="q2-sec-title">{{ __('Attachments') }}</h2>
                    </div>

                    @if(!empty($orderAttachments))
                        <ul id="so-existing-files" class="q2-li-wrap">
                            @foreach($orderAttachments as $attachment)
                                <li data-attachment-id="{{ $attachment->id }}" class="q2-li">
                                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="q2-li-name">{{ $attachment->name }}</span>
                                    <span class="q2-li-size">{{ format_bytes($attachment->file_size) }}</span>
                                    <button type="button" class="q2-li-rm" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}" onclick="soRemoveExistingFile(this)">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 18L18 6M6 6l12 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <label class="q2-drop mt-4" id="so-dropzone" for="so-files">
                        <span class="flex items-center justify-center gap-2 text-[0.929rem]">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Drag & drop files here, or') }} <b style="font-weight:700;color:var(--sec,#128F8E)">{{ __('browse') }}</b></span>
                        </span>
                        <span class="mt-1.5 flex items-center justify-center gap-1.5">
                            <span class="q2-fchip">PDF</span>
                            <span class="q2-fchip">IMG</span>
                            <span class="q2-fchip">XLSX</span>
                            <span class="q2-fchip">DOCX</span>
                        </span>
                    </label>

                    <input type="file" id="so-files" name="files[]" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.xls,.xlsx,.doc,.docx,.txt,.csv" class="hidden" />

                    <ul id="so-new-files" class="bill-file-list"></ul>

                    <x-input-error :messages="$errors->get('files')" class="mt-2" />
                </section>
            </div>

            {{-- §2/§3 rail: summary + quick nav --}}
            <aside class="q2-rail">
                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ $isEdit ? __('Breakdown') : __('Summary') }}</div>
                    <div class="q2-railsum">
                        <div class="q2-srow"><span>{{ __('Customer') }}</span><span class="q2-sval" id="p-cust">{{ $selectedCustomer?->name ?? '—' }}</span></div>
                        <div class="q2-srow"><span>{{ __('Contact') }}</span><span class="q2-sval" id="p-contact">{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '—' }}</span></div>
                        <div class="q2-srow"><span>{{ __('Order Date') }}</span><span class="q2-sval" id="p-date">{{ $order?->order_date?->format('M d, Y') ?? now()->format('M d, Y') }}</span></div>
                        <div class="q2-srow"><span>{{ __('Delivery') }}</span><span class="q2-sval" id="p-valid">{{ $order?->expected_delivery_date?->format('M d, Y') ?? '—' }}</span></div>
                        <div style="height:12px"></div>
                        <div class="q2-srow"><span>{{ __('Subtotal') }}</span><span class="q2-sval" id="v-sub">0.00</span></div>
                        <div class="q2-srow" id="r-disc" style="display:none"><span>{{ __('Discount') }}</span><span class="q2-sval" id="v-disc">0.00</span></div>
                        <div class="q2-srow" id="r-tax" style="display:none"><span>{{ __('Tax') }}</span><span class="q2-sval" id="v-tax">0.00</span></div>
                        <div class="q2-srow gt"><span>{{ __('Grand Total') }}</span><span class="q2-sval" id="v-gt">{{ $cs }}0.00</span></div>
                        <div id="p-lines"></div>
                        <p id="p-foot" class="q2-rail-memo" hidden></p>
                    </div>
                </div>

                <div class="q2-railcard">
                    <div class="q2-rail-group">{{ __('Quick Nav') }}</div>
                    @if ($isEdit)
                        <a href="{{ route('accounting.sales-orders.print', $order) }}" target="_blank" rel="noopener" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9V3h12v6M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2M6 14h12v7H6v-7z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Print / PDF') }}</span>
                        </a>
                        <a href="#attachments" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Attach File') }}</span>
                        </a>
                        <a href="{{ $cancelRoute }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M19 12H5m6-6-6 6 6 6" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Back') }}</span>
                        </a>
                    @else
                        <a href="{{ route('accounting.sales-orders.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>{{ __('Sales Orders List') }}</span>
                        </a>
                        <a href="{{ route('accounting.customers.create') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8M18 13v5m2.5-2.5h-5" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            <span>{{ __('New Customer') }}</span>
                        </a>
                        <a href="{{ route('accounting.invoices.index') }}" class="q2-vitem">
                            <svg viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 3h12a2 2 0 0 1 2 2v16l-4-2-4 2-4-2-4 2V5a2 2 0 0 1 2-2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            <span>{{ __('Invoices') }}</span>
                        </a>
                    @endif
                </div>
            </aside>
        </div>
    </form>

    @if($isEdit && $order->created_by && (int) $order->created_by !== (int) auth()->id())
    <form id="sales-order-delete-form" method="POST" action="{{ route('accounting.sales-orders.destroy', $order) }}" onsubmit="return fbConfirmSubmit(event, 'Delete this draft sales order? This cannot be undone.', { type: 'danger' })">
        @csrf
        @method('DELETE')
    </form>
    @endif
</div>

<script>
    const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const SO_CS = @json($cs);
    const SO_DEFAULT_INCOME_ACCOUNT_ID = @json((string) $defaultIncomeAccountId);
    const SO_LINES = @json($linesData);
    let soCustomerName = @json($selectedCustomer?->name ?? '');

    const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const parse = s => parseFloat(String(s == null ? '' : s).replace(/,/g, '')) || 0;
    const esc = s => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    function fmtDate(v) {
        if (!v) return '—';
        const m = String(v).match(/^(\d{4})-(\d{2})-(\d{2})/);
        if (!m) return v;
        const d = new Date(Number(m[1]), Number(m[2]) - 1, Number(m[3]));
        return d.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
    }

    let lineIndex = 0;

    function soLineRow(data) {
        const d = data || {};
        const idx = lineIndex++;
        const gross = parse(d.quantity) * parse(d.unit_price);
        const flat = parse(d.discount);
        const pct = d.discount_pct != null
            ? parse(d.discount_pct)
            : (gross > 0 ? Math.round(flat / gross * 10000) / 100 : 0);
        const taxRate = parse(d.tax_rate);
        const incomeAccountId = d.income_account_id || SO_DEFAULT_INCOME_ACCOUNT_ID || '';

        return `
            <tr class="so-row">
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" class="bill-ci bill-sku" value="${esc(d.sku || '')}" placeholder="Code" readonly tabindex="-1" aria-label="Item code" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    ${scopedSearchFieldHtml({
                        name: `lines[${idx}][product_id]`,
                        entity: 'product',
                        searchUrl: PRODUCT_SEARCH_URL,
                        value: d.product_id || '',
                        label: d.label || '',
                        placeholder: 'Search items…',
                    })}
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="text" class="bill-ci" name="lines[${idx}][description]" value="${esc(d.description || '')}" placeholder="Description" aria-label="Description" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="any" min="0" class="bill-ci bill-ci-num bill-qty" name="lines[${idx}][quantity]" value="${d.quantity != null ? d.quantity : 1}" aria-label="Quantity" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" class="bill-ci bill-ci-num bill-price" name="lines[${idx}][unit_price]" value="${d.unit_price != null ? d.unit_price : 0}" aria-label="Unit price" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" max="100" class="bill-ci bill-ci-num bill-disc-pct" value="${pct}" aria-label="Discount percent" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <span class="bill-amt bill-line-total q-amt">0.00</span>
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <div class="flex gap-1 justify-end">
                        <button type="button" class="bill-ibtn q-ibtn" title="Duplicate line" aria-label="Duplicate line" onclick="soDuplicateRow(this)">⧉</button>
                        <button type="button" class="bill-ibtn bill-ibtn--del q-ibtn q-ibtn--del" title="Delete line" aria-label="Delete line" onclick="soRemoveLine(this)">🗑</button>
                    </div>
                </td>
                <input type="hidden" name="lines[${idx}][tax_rate]" class="so-tax-rate" value="${taxRate}" />
                <input type="hidden" name="lines[${idx}][discount]" class="so-flat-discount" value="${flat}" />
                <input type="hidden" name="lines[${idx}][income_account_id]" class="so-income-account" value="${incomeAccountId}" />
            </tr>
        `;
    }

    function soRowData(row) {
        const g = sel => (row.querySelector(sel) || { value: '' }).value;
        const num = sel => parse(g(sel));
        const picker = row.querySelector('[name*="[product_id]"]');
        const labelEl = picker && picker.closest('[x-data]')
            ? picker.closest('[x-data]').querySelector('.scoped-search-field input')
            : null;
        const qty = num('.bill-qty');
        const price = num('.bill-price');
        const pct = num('.bill-disc-pct');

        return {
            product_id: g('[name*="[product_id]"]'),
            label: labelEl ? labelEl.value : '',
            sku: g('.bill-sku'),
            description: g('[name*="[description]"]'),
            quantity: qty,
            unit_price: price,
            discount: qty * price > 0 ? Math.round(qty * price * pct) / 100 : 0,
            discount_pct: pct,
            tax_rate: num('.so-tax-rate'),
            income_account_id: g('.so-income-account'),
        };
    }

    function soAddLine(data) {
        document.getElementById('so-lines-body')
            .insertAdjacentHTML('beforeend', soLineRow(data || {}));
        soUpdateTotals();
    }

    function soRemoveLine(btn) {
        const row = btn.closest('tr.so-row');
        row.remove();
        if (!document.querySelector('#so-lines-body tr.so-row')) soAddLine();
        soUpdateTotals();
    }

    function soDuplicateRow(btn) {
        const row = btn.closest('tr.so-row');
        row.insertAdjacentHTML('afterend', soLineRow(soRowData(row)));
        soUpdateTotals();
    }

    function soUpdateTotals() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;
        const lines = [];

        document.querySelectorAll('#so-lines-body tr.so-row').forEach(row => {
            const qty = parse(row.querySelector('.bill-qty').value);
            const price = parse(row.querySelector('.bill-price').value);
            const pct = parse(row.querySelector('.bill-disc-pct').value);
            const rate = parse(row.querySelector('.so-tax-rate').value);

            const gross = qty * price;
            const flat = gross > 0 ? Math.round(gross * pct) / 100 : 0;
            row.querySelector('.so-flat-discount').value = flat.toFixed(2);

            const amount = gross - flat;
            const lineTax = amount * rate / 100;
            row.querySelector('.bill-line-total').textContent = fmt(amount + lineTax);

            subtotal += gross;
            discount += flat;
            tax += lineTax;

            if (gross || amount) {
                lines.push({
                    c: row.querySelector('.bill-sku').value || '',
                    n: (row.querySelector('.scoped-search-field input') || { value: '' }).value || '',
                    q: qty,
                    a: amount,
                });
            }
        });

        const grand = subtotal - discount + tax;

        document.getElementById('v-sub').textContent = fmt(subtotal);
        const rd = document.getElementById('r-disc');
        rd.style.display = discount > 0 ? '' : 'none';
        document.getElementById('v-disc').textContent = fmt(discount);
        const rt = document.getElementById('r-tax');
        rt.style.display = tax > 0 ? '' : 'none';
        document.getElementById('v-tax').textContent = fmt(tax);
        document.getElementById('v-gt').textContent = SO_CS + fmt(grand);

        const holder = document.getElementById('p-lines');
        holder.innerHTML = lines.map(l =>
            `<div class="quot-p-line"><span class="quot-p-code">${esc(l.c) || '—'}</span><span class="quot-p-name">${esc(l.n) || 'Untitled'}</span><span class="quot-p-qty">×${l.q}</span><span class="quot-p-amt">${fmt(l.a)}</span></div>`
        ).join('') || '<div class="quot-p-line"><span class="quot-p-name" style="color:#94a3b8">No line items yet</span></div>';
    }

    function soSync() {
        document.getElementById('p-cust').textContent = soCustomerName || '—';
        document.getElementById('p-contact').textContent = document.getElementById('so-contact').value || '—';
        document.getElementById('p-date').textContent = fmtDate(document.getElementById('order_date').value);
        document.getElementById('p-valid').textContent = fmtDate(document.getElementById('expected_delivery_date').value);
        document.getElementById('p-foot').textContent = document.getElementById('memo').value || '—';
        soUpdateTotals();
    }

    function soCustomerSelected(id, item) {
        if (item) {
            document.getElementById('so-contact').value = item.display_name || item.label || '';
            document.getElementById('so-email').value = item.email || '';
            document.getElementById('so-phone').value = item.phone || '';
            document.getElementById('so-terms').value = item.payment_terms || '';
            soCustomerName = item.label || '';
        }
        soSync();
    }

    document.getElementById('so-add-line').addEventListener('click', () => soAddLine());

    const soLinesBody = document.getElementById('so-lines-body');
    soLinesBody.addEventListener('item-selected', e => {
        const row = e.target.closest('tr.so-row');
        if (!row || !row.querySelector('[name*="[product_id]"]')) return;
        const item = e.detail.item || {};

        if (item.sku != null) {
            const sku = row.querySelector('.bill-sku');
            if (sku) sku.value = item.sku;
        }
        if (item.description) {
            const desc = row.querySelector('[name*="[description]"]');
            if (desc) desc.value = item.description;
        }
        if (item.sales_price != null) {
            const price = row.querySelector('.bill-price');
            if (price) price.value = parse(item.sales_price).toFixed(2);
        }
        if (item.tax_rate != null) {
            const rate = row.querySelector('.so-tax-rate');
            if (rate) rate.value = item.tax_rate;
        }
        const acct = row.querySelector('.so-income-account');
        if (acct) acct.value = item.income_account_id || SO_DEFAULT_INCOME_ACCOUNT_ID || '';

        soUpdateTotals();
    });

    soLinesBody.addEventListener('input', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) soUpdateTotals();
    });
    soLinesBody.addEventListener('change', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) soUpdateTotals();
    });

    ['order_date', 'expected_delivery_date', 'memo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', soSync);
    });

    document.getElementById('sales-order-form').addEventListener('submit', soUpdateTotals);

    (SO_LINES.length ? SO_LINES : [{}]).forEach(d => soAddLine(d));
    soSync();

    /* ── attachments ─────────────────────────────────────────── */
    let soNewFiles = [];

    function soFmtSize(b) {
        if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
        if (b >= 1024) return (b / 1024).toFixed(1) + ' KB';
        return b + ' B';
    }

    function soRenderNewFiles() {
        const list = document.getElementById('so-new-files');
        if (!list) return;
        list.innerHTML = '';
        soNewFiles.forEach((f, i) => {
            const li = document.createElement('li');
            li.dataset.i = i;
            li.className = 'flex items-center gap-3 px-4 py-2.5 text-[0.929rem]';
            li.innerHTML = `
                <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span class="flex-1 min-w-0 truncate text-gray-800">${esc(f.name)}</span>
                <span class="shrink-0 text-[0.786rem] text-slate-400">${soFmtSize(f.size)}</span>
                <button type="button" class="bill-ibtn bill-ibtn--del q-ibtn q-ibtn--del" title="Remove" aria-label="Remove" onclick="soRemoveNewFile(this)">🗑</button>
            `;
            list.appendChild(li);
        });
    }

    function soSyncFileInput() {
        const input = document.getElementById('so-files');
        if (!input) return;
        const dt = new DataTransfer();
        soNewFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    function soAddFiles(fileList) {
        Array.from(fileList || []).forEach(f => soNewFiles.push(f));
        soRenderNewFiles();
        soSyncFileInput();
    }

    function soRemoveNewFile(btn) {
        const li = btn.closest('li[data-i]');
        if (!li) return;
        soNewFiles.splice(parseInt(li.dataset.i, 10), 1);
        soRenderNewFiles();
        soSyncFileInput();
    }

    function soRemoveExistingFile(btn) {
        const li = btn.closest('li[data-attachment-id]');
        if (!li) return;
        const id = li.dataset.attachmentId;
        let holder = document.getElementById('so-delete-documents');
        if (!holder) {
            holder = document.createElement('div');
            holder.id = 'so-delete-documents';
            document.getElementById('sales-order-form').appendChild(holder);
        }
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'delete_documents[]';
        hidden.value = id;
        holder.appendChild(hidden);
        li.remove();
    }

    function soWireDropzone() {
        const dz = document.getElementById('so-dropzone');
        const input = document.getElementById('so-files');
        if (!dz || !input) return;

        input.addEventListener('change', () => soAddFiles(input.files));

        ['dragover', 'dragenter'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.add('bill-drop--drag');
        }));
        ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.remove('bill-drop--drag');
        }));
        dz.addEventListener('drop', e => {
            if (e.dataTransfer && e.dataTransfer.files.length) soAddFiles(e.dataTransfer.files);
        });
    }

    soWireDropzone();
</script>
