@php
    $bill = $bill ?? null;
    $isEdit = $isEdit ?? (bool) $bill;
    $formAction = $formAction ?? ($isEdit ? route('accounting.bills.update', $bill) : route('accounting.bills.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.bills.show', $bill) : route('accounting.bills.index'));
    $title = $title ?? ($isEdit ? 'Edit Bill' : 'Create Bill');
    $subtitle = $subtitle ?? 'Record a supplier bill with line items and charges.';
    $submitLabel = $submitLabel ?? ($isEdit ? 'Save Changes' : 'Save');
    $selectedVendorId = $selectedVendorId ?? null;
    $billAttachments = $billAttachments ?? ($bill?->attachments ?? collect());

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $defaultExpenseAccountId = $defaultExpenseAccountId ?? ($expenseAccounts->first()?->id ?? '');

    $oldVendorId = old('vendor_id', $bill?->vendor_id ?? ($selectedVendorId ?? ''));
    $selectedVendor = $vendors->firstWhere('id', (int) $oldVendorId);

    $currencyCodes = $currencies->pluck('code')->all();
    $defaultCurrency = in_array('MWK', $currencyCodes) ? 'MWK' : ($currencies->first()?->code ?? 'USD');
    $selectedCurrency = old('currency', $bill?->currency ?? $defaultCurrency);
    $selectedBranchId = old('branch_id', $bill?->branch_id ?? $branches->first()?->id ?? '');

    $linesData = [];
    if (old('lines')) {
        foreach (array_values(old('lines')) as $l) {
            $gross = ((float) ($l['quantity'] ?? 0)) * ((float) ($l['unit_price'] ?? 0));
            $flat = (float) ($l['discount'] ?? 0);
            $linesData[] = [
                'product_id' => $l['product_id'] ?? '',
                'label' => $l['product_name'] ?? '',
                'sku' => $l['product_sku'] ?? '',
                'description' => $l['description'] ?? '',
                'quantity' => (float) ($l['quantity'] ?? 1),
                'unit_price' => (float) ($l['unit_price'] ?? 0),
                'discount' => $flat,
                'discount_pct' => $gross > 0 ? round($flat / $gross * 100, 2) : 0,
                'tax_rate' => (float) ($l['tax_rate'] ?? 0),
                'expense_account_id' => $l['expense_account_id'] ?? $defaultExpenseAccountId,
                'cost_center_id' => $l['cost_center_id'] ?? '',
            ];
        }
    } elseif ($bill) {
        $linesData = $bill->lines->map(function ($l) {
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
                'expense_account_id' => $l->expense_account_id,
                'cost_center_id' => $l->cost_center_id ?? '',
            ];
        })->all();
    }

    $vendorMap = $vendors->map(fn ($v) => [
        'id' => $v->id,
        'name' => $v->name,
        'display_name' => $v->display_name,
        'email' => $v->email,
        'phone' => $v->phone,
        'payment_terms' => $v->payment_terms,
        'address' => $v->billing_address ?: $v->remit_to_address,
    ])->values();

    $expenseAccountMap = $expenseAccounts->map(fn ($a) => ['id' => $a->id, 'label' => $a->code.' - '.$a->name])->values();

    $secIc = 'w-7 h-7 rounded-[9px] grid place-items-center text-white bg-[#128F8E] shadow-[inset_0_1px_0_rgba(255,255,255,.18),0_3px_8px_-3px_rgba(10,80,80,.4)]';
    $secHead = 'flex items-center gap-3';
    $btnTertiary = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] border border-transparent bg-transparent text-gray-600 transition-all duration-150 hover:bg-white/75 hover:text-[#0B2A2D] hover:-translate-y-px active:translate-y-0';
    $btnGhost = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] border border-shell bg-white/85 text-gray-700 shadow-sm transition-all duration-150 hover:bg-[rgba(17,69,75,.06)] hover:border-navy-700/25 hover:text-[#0B2A2D] hover:-translate-y-px active:translate-y-0';
    $btnPrimary = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] text-white border border-white/25 bg-gradient-to-b from-gold-500 to-gold-600 shadow-new transition-all duration-150 hover:-translate-y-px active:translate-y-0';
    $btnAccent = 'inline-flex items-center justify-center gap-2 h-10 px-4 rounded-xl font-semibold text-[13.5px] text-[#EAFFFF] border border-white/15 bg-gradient-to-b from-[#17565D] via-[#0C3539] to-[#0A2E32] shadow-[0_1px_2px_rgba(6,32,35,.30),0_10px_20px_-10px_rgba(12,53,57,.60),inset_0_1px_0_rgba(255,255,255,.12)] transition-all duration-150 hover:-translate-y-px active:translate-y-0 font-bold';
    $selectWrap = 'relative';
    $selectChevron = 'pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 w-3.5 h-3.5 text-slate-500';
@endphp

<div>
    {{-- page head --}}
    <div class="form-page-head pb-4 mb-6 border-b border-line flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ $title }}</h1>
            <p class="mt-1 text-[13.5px] text-gray-500">{{ $subtitle }}</p>
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ $cancelRoute }}" class="{{ $btnTertiary }}">{{ __('Cancel') }}</a>
            <button type="submit" name="action" value="save_draft" form="bill-form" class="{{ $btnGhost }}">{{ __('Save Draft') }}</button>
            @unless ($isEdit)
                <button type="submit" name="action" value="save_and_new" form="bill-form" class="{{ $btnGhost }}">{{ __('Save & New') }}</button>
            @endunless
            <button type="submit" name="action" value="save" form="bill-form" class="{{ $btnPrimary }}">{{ $submitLabel }}</button>
            <button type="submit" name="action" value="submit_for_approval" form="bill-form" class="{{ $btnAccent }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V6M6 12l6-6 6 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Submit for Approval') }}
            </button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="bill-form" enctype="multipart/form-data" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="grid gap-6 items-start lg:grid-cols-[1fr_320px]">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- supplier --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M3 9l9-6 9 6M5 9v11M19 9v11M9 9v11M15 9v11M3 20h18" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Supplier Information') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-5 xl:grid-cols-4">
                        <div class="col-span-2">
                            <label for="vendor_id" class="input-label">{{ __('Supplier') }} <span class="text-red-600">*</span></label>
                            <x-scoped-search-field
                                name="vendor_id"
                                entity="vendor"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'vendor']) }}"
                                :value="old('vendor_id', $oldVendorId)"
                                :label="old('vendor_name', $selectedVendor?->name ?? '')"
                                placeholder="{{ __('Search vendors…') }}"
                                on-select="billVendorSelected"
                                required
                            />
                            <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Contact Person') }}</label>
                            <input type="text" class="input" id="vendor-contact" value="{{ $selectedVendor?->display_name ?? $selectedVendor?->name ?? '' }}" readonly />
                        </div>
                        <div>
                            <label for="currency" class="input-label">{{ __('Currency') }}</label>
                            <div class="{{ $selectWrap }}">
                                <select id="currency" name="currency" class="input appearance-none pr-8">
                                    @forelse ($currencies as $curOption)
                                        <option value="{{ $curOption->code }}" @selected($selectedCurrency === $curOption->code)>{{ $curOption->code }} - {{ $curOption->name }}</option>
                                    @empty
                                        <option value="USD" @selected($selectedCurrency === 'USD')>USD - US Dollar</option>
                                    @endforelse
                                </select>
                                <svg class="{{ $selectChevron }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                            </div>
                        </div>
                        <div>
                            <label class="input-label">{{ __('Email') }}</label>
                            <input type="email" class="input" id="vendor-email" value="{{ $selectedVendor?->email ?? '' }}" readonly />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Phone') }}</label>
                            <input type="text" class="input" id="vendor-phone" value="{{ $selectedVendor?->phone ?? '' }}" readonly />
                        </div>
                        <div>
                            <label for="exchange_rate" class="input-label">{{ __('Exchange Rate') }}</label>
                            <input id="exchange_rate" name="exchange_rate" type="number" step="0.0001" min="0" class="input" value="{{ old('exchange_rate', $bill?->exchange_rate ?? 1) }}" />
                            <x-input-error :messages="$errors->get('exchange_rate')" class="mt-2" />
                        </div>
                        <div class="col-span-2 xl:col-span-1">
                            <label class="input-label">{{ __('Payment Terms') }}</label>
                            <input type="text" class="input" id="vendor-terms" value="{{ $selectedVendor?->payment_terms ?? '' }}" readonly />
                        </div>
                        <div class="col-span-2">
                            <label class="input-label">{{ __('Address') }}</label>
                            <input type="text" class="input" id="vendor-address" value="{{ $selectedVendor?->billing_address ?: $selectedVendor?->remit_to_address ?? '' }}" readonly />
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-400">Contact details shown are from the supplier record.</p>
                </section>

                {{-- bill info --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6M9 16h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Bill Information') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-5 xl:grid-cols-4">
                        <div>
                            <label class="input-label">{{ __('Bill Number') }}</label>
                            <input type="text" class="input" value="{{ $bill?->bill_number ?? '' }}" placeholder="{{ __('Auto-assigned on save') }}" readonly />
                        </div>
                        <div>
                            <label for="internal_number" class="input-label">{{ __('Supplier Invoice №') }}</label>
                            <input id="internal_number" name="internal_number" type="text" class="input" value="{{ old('internal_number', $bill?->internal_number ?? '') }}" placeholder="INV-…" />
                            <x-input-error :messages="$errors->get('internal_number')" class="mt-2" />
                        </div>
                        <div>
                            <label for="bill_date" class="input-label">{{ __('Bill Date') }} <span class="text-red-600">*</span></label>
                            <input id="bill_date" name="bill_date" type="date" class="input" value="{{ old('bill_date', $bill?->bill_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('bill_date')" class="mt-2" />
                        </div>
                        <div>
                            <label for="due_date" class="input-label">{{ __('Due Date') }} <span class="text-red-600">*</span></label>
                            <input id="due_date" name="due_date" type="date" class="input" value="{{ old('due_date', $bill?->due_date?->format('Y-m-d') ?? '') }}" required />
                            <div id="bill-due-error" class="mt-1.5 text-[11.5px] font-semibold text-red-600" style="display:none">Due date must be on or after bill date.</div>
                            <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
                        </div>
                        <div>
                            <label for="po_number" class="input-label">{{ __('Purchase Order') }}</label>
                            <input id="po_number" name="po_number" type="text" class="input" value="{{ old('po_number', $bill?->po_number ?? '') }}" placeholder="PO-…" />
                            <x-input-error :messages="$errors->get('po_number')" class="mt-2" />
                        </div>
                        <div>
                            <label for="grn_reference" class="input-label">{{ __('GRN') }}</label>
                            <input id="grn_reference" name="grn_reference" type="text" class="input" value="{{ old('grn_reference', $bill?->grn_reference ?? '') }}" placeholder="GRN-…" />
                            <x-input-error :messages="$errors->get('grn_reference')" class="mt-2" />
                        </div>
                        <div>
                            <label for="reference" class="input-label">{{ __('Reference №') }}</label>
                            <input id="reference" name="reference" type="text" class="input" value="{{ old('reference', $bill?->reference ?? '') }}" />
                            <x-input-error :messages="$errors->get('reference')" class="mt-2" />
                        </div>
                        <div>
                            <label for="branch_id" class="input-label">{{ __('Branch') }}</label>
                            <div class="{{ $selectWrap }}">
                                <select id="branch_id" name="branch_id" class="input appearance-none pr-8">
                                    <option value="">-- {{ __('Select branch') }} --</option>
                                    @foreach ($branches as $branch)
                                        <option value="{{ $branch->id }}" @selected((string) $selectedBranchId === (string) $branch->id)>{{ $branch->name }}</option>
                                    @endforeach
                                </select>
                                <svg class="{{ $selectChevron }}" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M6 9l6 6 6-6" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>
                            </div>
                            <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Department') }}</label>
                            <input type="text" class="input" placeholder="Operations" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Cost Centre') }}</label>
                            <input type="text" class="input" placeholder="CC-…" />
                        </div>
                        <div class="col-span-2">
                            <label class="input-label">{{ __('Project') }}</label>
                            <input type="text" class="input" placeholder="{{ __('Optional') }}" />
                        </div>
                    </div>
                </section>

                {{-- line items --}}
                <section class="card relative z-30 rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Line Items') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                        <button type="button" id="bill-add-line" class="{{ $btnGhost }}" style="height:34px;padding:0 13px;font-size:12.5px;border-radius:10px;">＋ {{ __('Add Line') }}</button>
                    </div>

                    <div class="mt-4 border border-shell rounded-[14px] overflow-visible round-thead-clip bg-[#fbfcfe]">
                        <table id="bill-lines-table" class="w-full border-collapse text-[13px] table-fixed">
                            <thead class="th-mist">
                                <tr>
                                    <th class="w-[13%]">{{ __('Item Code') }}</th>
                                    <th class="w-[16%]">{{ __('Item Name') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th class="w-[8%] num">{{ __('Qty') }}</th>
                                    <th class="w-[13%] num">{{ __('Unit Cost') }} ({{ $cs }})</th>
                                    <th class="w-[8%] num">{{ __('Disc %') }}</th>
                                    <th class="w-[12%] num">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th class="w-[8%]"></th>
                                </tr>
                            </thead>
                            <tbody id="bill-lines-body"></tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </section>

                {{-- additional charges --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2" stroke="currentColor" stroke-width="2"/><path d="M3 10h18M7 15h4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Additional Charges') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-5 xl:grid-cols-4">
                        <div>
                            <label for="freight_charges" class="input-label">{{ __('Freight') }}</label>
                            <input id="freight_charges" name="freight_charges" type="text" inputmode="decimal" class="input" data-charge="freight" value="{{ old('freight_charges', $bill?->freight_charges ?? '0.00') }}" />
                            <x-input-error :messages="$errors->get('freight_charges')" class="mt-2" />
                        </div>
                        <div>
                            <label for="insurance_charges" class="input-label">{{ __('Insurance') }}</label>
                            <input id="insurance_charges" name="insurance_charges" type="text" inputmode="decimal" class="input" data-charge="insurance" value="{{ old('insurance_charges', $bill?->insurance_charges ?? '0.00') }}" />
                            <x-input-error :messages="$errors->get('insurance_charges')" class="mt-2" />
                        </div>
                        <div>
                            <label for="customs_charges" class="input-label">{{ __('Customs') }}</label>
                            <input id="customs_charges" name="customs_charges" type="text" inputmode="decimal" class="input" data-charge="customs" value="{{ old('customs_charges', $bill?->customs_charges ?? '0.00') }}" />
                            <x-input-error :messages="$errors->get('customs_charges')" class="mt-2" />
                        </div>
                        <div>
                            <label for="other_charges" class="input-label">{{ __('Other Charges') }}</label>
                            <input id="other_charges" name="other_charges" type="text" inputmode="decimal" class="input" data-charge="other" value="{{ old('other_charges', $bill?->other_charges ?? '0.00') }}" />
                            <x-input-error :messages="$errors->get('other_charges')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- notes --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Notes') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 mt-5 xl:grid-cols-3">
                        <div>
                            <label for="memo" class="input-label">{{ __('Internal Notes') }}</label>
                            <textarea id="memo" name="memo" class="input" placeholder="Visible to your team only…">{{ old('memo', $bill?->memo ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                        </div>
                        <div>
                            <label for="supplier_notes" class="input-label">{{ __('Supplier Notes') }}</label>
                            <textarea id="supplier_notes" name="supplier_notes" class="input" placeholder="Printed on the bill PDF…">{{ old('supplier_notes', $bill?->supplier_notes ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('supplier_notes')" class="mt-2" />
                        </div>
                        <div>
                            <label for="payment_instructions" class="input-label">{{ __('Payment Instructions') }}</label>
                            <textarea id="payment_instructions" name="payment_instructions" class="input" placeholder="Bank / settlement details…">{{ old('payment_instructions', $bill?->payment_instructions ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('payment_instructions')" class="mt-2" />
                        </div>
                    </div>
                </section>

                {{-- attachments --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Attachments') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>

                    @if(!empty($billAttachments))
                        <ul id="bill-existing-files" class="mt-4 divide-y divide-line border border-shell rounded-[14px] bg-[#fbfcfe]">
                            @foreach($billAttachments as $attachment)
                                <li data-attachment-id="{{ $attachment->id }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px]">
                                    <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="flex-1 min-w-0 truncate text-gray-800">{{ $attachment->name }}</span>
                                    <span class="shrink-0 text-[11px] text-slate-400">{{ format_bytes($attachment->file_size) }}</span>
                                    <button type="button" class="bill-ibtn bill-ibtn--del" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}" onclick="billRemoveExistingFile(this)">🗑</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <label class="bill-drop mt-4" id="bill-dropzone" for="bill-files">
                        <div class="w-[46px] h-[46px] mx-auto mb-2.5 rounded-full grid place-items-center bg-[rgba(17,69,75,.06)] border border-navy-700/15 text-navy-700">
                            <svg width="19" height="19" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 16V4M7 9l5-5 5 5M4 20h16" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </div>
                        <p class="text-[13px] text-slate-500">{{ __('Drag & drop files here, or') }} <b class="text-gold-700">{{ __('browse') }}</b></p>
                        <div class="mt-2.5 flex gap-1.5 justify-center flex-wrap">
                            <span class="px-2.5 py-0.5 rounded-full bg-[rgba(17,69,75,.06)] border border-navy-700/15 text-navy-700 text-[10.5px] font-extrabold tracking-[0.06em]">PDF</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[rgba(17,69,75,.06)] border border-navy-700/15 text-navy-700 text-[10.5px] font-extrabold tracking-[0.06em]">IMG</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[rgba(17,69,75,.06)] border border-navy-700/15 text-navy-700 text-[10.5px] font-extrabold tracking-[0.06em]">XLSX</span>
                            <span class="px-2.5 py-0.5 rounded-full bg-[rgba(17,69,75,.06)] border border-navy-700/15 text-navy-700 text-[10.5px] font-extrabold tracking-[0.06em]">DOCX</span>
                        </div>
                    </label>

                    <input type="file" id="bill-files" name="files[]" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.xls,.xlsx,.doc,.docx,.txt,.csv" class="hidden" />

                    <ul id="bill-new-files" class="bill-file-list"></ul>

                    <x-input-error :messages="$errors->get('files')" class="mt-2" />
                </section>
            </div>

            {{-- summary --}}
            <aside class="card rounded-[20px] p-6 xl:p-[26px] lg:sticky"
                   style="top: calc(var(--topbar-h, 106px) + 94px); z-index: 10;">
                <div class="{{ $secHead }}">
                    <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><rect x="4" y="3" width="16" height="18" rx="2" stroke="currentColor" stroke-width="2"/><path d="M8 7.5h8M8.5 12h.01M12 12h.01M15.5 12h.01M8.5 15.5h.01M12 15.5h.01M15.5 15.5h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                    <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Summary') }}</h2>
                </div>

                <div class="mt-3.5">
                    <div class="flex justify-between gap-4 py-2 border-b border-line text-[13px]">
                        <span class="text-slate-500">{{ __('Subtotal') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums" id="bill-sub">0.00</span>
                    </div>
                    <div class="flex justify-between gap-4 py-2 border-b border-line text-[13px]">
                        <span class="text-slate-500">{{ __('Discount') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums" id="bill-discount">0.00</span>
                    </div>
                    <div class="flex justify-between gap-4 py-2 border-b border-line text-[13px]">
                        <span class="text-slate-500">{{ __('Tax') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums" id="bill-tax">0.00</span>
                    </div>
                    <div class="bill-sum-row flex justify-between gap-4 py-2 border-b border-line text-[13px]" data-charge="freight" style="display:none">
                        <span class="text-slate-500">{{ __('Freight') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums">0.00</span>
                    </div>
                    <div class="bill-sum-row flex justify-between gap-4 py-2 border-b border-line text-[13px]" data-charge="insurance" style="display:none">
                        <span class="text-slate-500">{{ __('Insurance') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums">0.00</span>
                    </div>
                    <div class="bill-sum-row flex justify-between gap-4 py-2 border-b border-line text-[13px]" data-charge="customs" style="display:none">
                        <span class="text-slate-500">{{ __('Customs') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums">0.00</span>
                    </div>
                    <div class="bill-sum-row flex justify-between gap-4 py-2 border-b border-line text-[13px]" data-charge="other" style="display:none">
                        <span class="text-slate-500">{{ __('Other Charges') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums">0.00</span>
                    </div>
                    <div class="flex justify-between gap-4 py-2 text-[13px]">
                        <span class="text-slate-500">{{ __('Rounding') }}</span>
                        <span class="font-semibold text-gray-900 tabular-nums" id="bill-rounding">0.00</span>
                    </div>
                </div>

                <div class="mt-3.5 flex justify-between items-center px-4 py-4 rounded-[14px] bg-gradient-to-r from-[#149897] via-[#128F8E] to-[#107C7B] shadow-[inset_0_1px_0_rgba(255,255,255,.2),inset_0_-1px_0_rgba(11,42,45,.35),0_10px_24px_-10px_rgba(10,80,80,.5)]">
                    <span class="text-[11px] font-extrabold uppercase tracking-[0.1em] text-[#DFF7F6]">{{ __('Grand Total') }}</span>
                    <span class="text-2xl font-extrabold text-white tabular-nums" id="bill-gt">{{ $cs }}0.00</span>
                </div>
                <p class="mt-2.5 text-[11.5px] text-slate-400 text-center">Zero charges are hidden · totals update instantly.</p>
            </aside>
        </div>
    </form>
</div>

<script>
    const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const BILL_CS = @json($cs);
    const BILL_DEFAULT_EXPENSE_ACCOUNT_ID = @json((string) $defaultExpenseAccountId);
    const BILL_EXPENSE_ACCOUNTS = @json($expenseAccountMap);
    const BILL_VENDORS = @json($vendorMap);
    const BILL_LINES = @json($linesData);

    const fmt = n => n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const parse = s => parseFloat(String(s == null ? '' : s).replace(/,/g, '')) || 0;
    const esc = s => String(s == null ? '' : s)
        .replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

    let lineIndex = 0;

    function billLineRow(data) {
        const d = data || {};
        const idx = lineIndex++;
        const gross = parse(d.quantity) * parse(d.unit_price);
        const flat = parse(d.discount);
        const pct = d.discount_pct != null
            ? parse(d.discount_pct)
            : (gross > 0 ? Math.round(flat / gross * 10000) / 100 : 0);
        const taxRate = parse(d.tax_rate);
        const expenseAccountId = d.expense_account_id || BILL_DEFAULT_EXPENSE_ACCOUNT_ID || '';

        return `
            <tr class="bill-row">
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
                    <input type="number" step="0.01" min="0" class="bill-ci bill-ci-num bill-price" name="lines[${idx}][unit_price]" value="${d.unit_price != null ? d.unit_price : 0}" aria-label="Unit cost" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <input type="number" step="0.01" min="0" max="100" class="bill-ci bill-ci-num bill-disc-pct" value="${pct}" aria-label="Discount percent" />
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <span class="bill-amt bill-line-total">0.00</span>
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <div class="flex gap-1 justify-end">
                        <button type="button" class="bill-ibtn" title="Duplicate line" aria-label="Duplicate line" onclick="billDuplicateRow(this)">⧉</button>
                        <button type="button" class="bill-ibtn bill-ibtn--del" title="Delete line" aria-label="Delete line" onclick="billRemoveLine(this)">🗑</button>
                    </div>
                </td>
                <input type="hidden" name="lines[${idx}][tax_rate]" class="bill-tax-rate" value="${taxRate}" />
                <input type="hidden" name="lines[${idx}][discount]" class="bill-flat-discount" value="${flat}" />
                <input type="hidden" name="lines[${idx}][expense_account_id]" class="bill-expense-account" value="${expenseAccountId}" />
                <input type="hidden" name="lines[${idx}][cost_center_id]" class="bill-cost-center" value="${esc(d.cost_center_id || '')}" />
            </tr>
        `;
    }

    function billRowData(row) {
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
            tax_rate: num('.bill-tax-rate'),
            expense_account_id: g('.bill-expense-account'),
            cost_center_id: g('.bill-cost-center'),
        };
    }

    function billAddLine(data) {
        document.getElementById('bill-lines-body')
            .insertAdjacentHTML('beforeend', billLineRow(data || {}));
        billUpdateTotals();
    }

    function billRemoveLine(btn) {
        const row = btn.closest('tr.bill-row');
        row.remove();
        if (!document.querySelector('#bill-lines-body tr.bill-row')) billAddLine();
        billUpdateTotals();
    }

    function billDuplicateRow(btn) {
        const row = btn.closest('tr.bill-row');
        row.insertAdjacentHTML('afterend', billLineRow(billRowData(row)));
        billUpdateTotals();
    }

    function billUpdateTotals() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;

        document.querySelectorAll('#bill-lines-body tr.bill-row').forEach(row => {
            const qty = parse(row.querySelector('.bill-qty').value);
            const price = parse(row.querySelector('.bill-price').value);
            const pct = parse(row.querySelector('.bill-disc-pct').value);
            const rate = parse(row.querySelector('.bill-tax-rate').value);

            const gross = qty * price;
            const flat = gross > 0 ? Math.round(gross * pct) / 100 : 0;
            row.querySelector('.bill-flat-discount').value = flat.toFixed(2);

            const amount = gross - flat;
            const lineTax = amount * rate / 100;
            row.querySelector('.bill-line-total').textContent = fmt(amount + lineTax);

            subtotal += gross;
            discount += flat;
            tax += lineTax;
        });

        let charges = 0;
        document.querySelectorAll('input[data-charge]').forEach(inp => {
            const v = parse(inp.value);
            const row = document.querySelector('.bill-sum-row[data-charge="' + inp.dataset.charge + '"]');
            if (v > 0) {
                if (row) { row.style.display = ''; row.querySelector('span:last-child').textContent = fmt(v); }
                charges += v;
            } else {
                if (row) row.style.display = 'none';
            }
        });

        const grand = subtotal - discount + tax + charges;

        document.getElementById('bill-sub').textContent = fmt(subtotal);
        document.getElementById('bill-discount').textContent = fmt(discount);
        document.getElementById('bill-tax').textContent = fmt(tax);
        document.getElementById('bill-rounding').textContent = '0.00';
        document.getElementById('bill-gt').textContent = BILL_CS + fmt(grand);
    }

    function billOnLineItemSelected(e) {
        const row = e.target.closest('tr.bill-row');
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
        if (item.purchase_price != null) {
            const price = row.querySelector('.bill-price');
            if (price) price.value = item.purchase_price;
        }
        if (item.tax_rate != null) {
            const rate = row.querySelector('.bill-tax-rate');
            if (rate) rate.value = item.tax_rate;
        }
        const acct = row.querySelector('.bill-expense-account');
        if (acct) acct.value = item.expense_account_id || BILL_DEFAULT_EXPENSE_ACCOUNT_ID || '';

        billUpdateTotals();
    }

    function billVendorSelected(id, item) {
        const v = BILL_VENDORS.find(x => x.id == id);
        if (!v) return;
        document.getElementById('vendor-contact').value = v.display_name || v.name || '';
        document.getElementById('vendor-email').value = v.email || '';
        document.getElementById('vendor-phone').value = v.phone || '';
        document.getElementById('vendor-terms').value = v.payment_terms || '';
        document.getElementById('vendor-address').value = v.address || '';
    }

    function billCheckDueDate() {
        const bd = document.getElementById('bill_date').value;
        const dd = document.getElementById('due_date').value;
        const err = document.getElementById('bill-due-error');
        const ddInput = document.getElementById('due_date');
        if (bd && dd && dd < bd) {
            if (err) err.style.display = 'block';
            ddInput.classList.add('input-error');
        } else {
            if (err) err.style.display = 'none';
            ddInput.classList.remove('input-error');
        }
    }

    document.getElementById('bill-add-line').addEventListener('click', () => billAddLine());

    const billLinesBody = document.getElementById('bill-lines-body');
    billLinesBody.addEventListener('item-selected', billOnLineItemSelected);
    billLinesBody.addEventListener('input', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) billUpdateTotals();
    });
    billLinesBody.addEventListener('change', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) billUpdateTotals();
    });

    document.getElementById('bill_date').addEventListener('change', billCheckDueDate);
    document.getElementById('due_date').addEventListener('change', billCheckDueDate);
    document.querySelectorAll('input[data-charge]').forEach(i => i.addEventListener('input', billUpdateTotals));

    document.getElementById('bill-form').addEventListener('submit', billUpdateTotals);

    (BILL_LINES.length ? BILL_LINES : [{}]).forEach(d => billAddLine(d));
    billUpdateTotals();

    /* ── attachments ─────────────────────────────────────────── */
    let billNewFiles = [];

    function billFmtSize(b) {
        if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
        if (b >= 1024) return (b / 1024).toFixed(1) + ' KB';
        return b + ' B';
    }

    function billRenderNewFiles() {
        const list = document.getElementById('bill-new-files');
        if (!list) return;
        list.innerHTML = '';
        billNewFiles.forEach((f, i) => {
            const li = document.createElement('li');
            li.dataset.i = i;
            li.className = 'flex items-center gap-3 px-4 py-2.5 text-[13px]';
            li.innerHTML = `
                <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span class="flex-1 min-w-0 truncate text-gray-800">${esc(f.name)}</span>
                <span class="shrink-0 text-[11px] text-slate-400">${billFmtSize(f.size)}</span>
                <button type="button" class="bill-ibtn bill-ibtn--del" title="Remove" aria-label="Remove" onclick="billRemoveNewFile(this)">🗑</button>
            `;
            list.appendChild(li);
        });
    }

    function billSyncFileInput() {
        const input = document.getElementById('bill-files');
        if (!input) return;
        const dt = new DataTransfer();
        billNewFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    function billAddFiles(fileList) {
        Array.from(fileList || []).forEach(f => billNewFiles.push(f));
        billRenderNewFiles();
        billSyncFileInput();
    }

    function billRemoveNewFile(btn) {
        const li = btn.closest('li[data-i]');
        if (!li) return;
        billNewFiles.splice(parseInt(li.dataset.i, 10), 1);
        billRenderNewFiles();
        billSyncFileInput();
    }

    function billRemoveExistingFile(btn) {
        const li = btn.closest('li[data-attachment-id]');
        if (!li) return;
        const id = li.dataset.attachmentId;
        let holder = document.getElementById('bill-delete-documents');
        if (!holder) {
            holder = document.createElement('div');
            holder.id = 'bill-delete-documents';
            document.getElementById('bill-form').appendChild(holder);
        }
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'delete_documents[]';
        hidden.value = id;
        holder.appendChild(hidden);
        li.remove();
    }

    function billWireDropzone() {
        const dz = document.getElementById('bill-dropzone');
        const input = document.getElementById('bill-files');
        if (!dz || !input) return;

        input.addEventListener('change', () => billAddFiles(input.files));

        ['dragover', 'dragenter'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.add('bill-drop--drag');
        }));
        ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.remove('bill-drop--drag');
        }));
        dz.addEventListener('drop', e => {
            if (e.dataTransfer && e.dataTransfer.files.length) billAddFiles(e.dataTransfer.files);
        });
    }

    billWireDropzone();
</script>
