@php
    $quotation = $quotation ?? null;
    $isEdit = $isEdit ?? (bool) $quotation;
    $formAction = $formAction ?? ($isEdit ? route('accounting.quotations.update', $quotation) : route('accounting.quotations.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.quotations.show', $quotation) : route('accounting.quotations.index'));
    $title = $title ?? ($isEdit ? 'Edit Quotation' : 'Create Quotation');
    $subtitle = $subtitle ?? 'Quote products & services with live document preview.';
    $submitLabel = $submitLabel ?? ($isEdit ? 'Save Changes' : 'Save');

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    $defaultIncomeAccountId = $defaultIncomeAccountId ?? ($incomeAccounts->first()?->id ?? '');

    $oldCustomerId = old('customer_id', $quotation?->customer_id ?? ($selectedCustomerId ?? ''));
    $selectedCustomer = $customers->firstWhere('id', (int) $oldCustomerId);

    $currencyCodes = $currencies->pluck('code')->all();
    $defaultCurrency = in_array('MWK', $currencyCodes) ? 'MWK' : ($currencies->first()?->code ?? 'USD');
    $selectedCurrency = old('currency', $quotation?->currency ?? $defaultCurrency);
    $selectedBranchId = old('branch_id', $quotation?->branch_id ?? $branches->first()?->id ?? '');
    $selectedCostCenterId = old('cost_center_id', $quotation?->cost_center_id ?? '');
    $selectedCostCenterLabel = $selectedCostCenterId ? ($costCenters->firstWhere('id', (int) $selectedCostCenterId)?->name ?? '') : '';

    $quotationAttachments = $quotationAttachments ?? ($quotation?->attachments ?? collect());

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
    } elseif ($quotation) {
        $linesData = $quotation->lines->map(function ($l) {
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
    {{-- sticky page head --}}
    <div class="form-page-head flex items-start justify-between gap-4 flex-wrap pb-4 mb-6 border-b border-line">
        <div>
            <h1 class="text-2xl font-extrabold tracking-[-0.02em] text-gray-900">{{ $title }}</h1>
            <p class="mt-1 text-[13.5px] text-gray-500">{{ $subtitle }}</p>
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ $cancelRoute }}" class="{{ $btnTertiary }}">{{ __('Cancel') }}</a>
            <button type="submit" name="action" value="save_draft" form="quotation-form" class="{{ $btnGhost }}">{{ __('Save Draft') }}</button>
            @unless ($isEdit)
                <button type="submit" name="action" value="save_and_new" form="quotation-form" class="{{ $btnGhost }}">{{ __('Save & New') }}</button>
            @endunless
            <button type="submit" name="action" value="save" form="quotation-form" class="{{ $btnPrimary }}">{{ $submitLabel }}</button>
            <button type="submit" name="action" value="submit_for_approval" form="quotation-form" class="{{ $btnAccent }}">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M12 19V6M6 12l6-6 6 6" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                {{ __('Submit for Approval') }}
            </button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="quotation-form" enctype="multipart/form-data" novalidate data-customer-name="{{ $selectedCustomer?->name ?? '' }}">
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="grid gap-6 items-start lg:grid-cols-[1fr_340px]">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- customer --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="9" cy="8" r="3.5" stroke="currentColor" stroke-width="2"/><path d="M2.5 20c1.2-3.5 4-5 6.5-5s5.3 1.5 6.5 5M16 4.6a3.5 3.5 0 0 1 0 6.8" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Customer Information') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-5 xl:grid-cols-4">
                        <div class="col-span-2">
                            <label for="customer_id" class="input-label">{{ __('Customer') }} <span class="text-red-600">*</span></label>
                            <x-scoped-search-field
                                name="customer_id"
                                entity="customer"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'customer']) }}"
                                :value="old('customer_id', $oldCustomerId)"
                                :label="old('customer_name', $selectedCustomer?->name ?? '')"
                                placeholder="{{ __('Search customers…') }}"
                                on-select="quotCustomerSelected"
                                required
                            />
                            <x-input-error :messages="$errors->get('customer_id')" class="mt-2" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Contact Person') }}</label>
                            <input type="text" class="input" id="quot-contact" value="{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '' }}" readonly />
                        </div>
                        <div>
                            <label for="valid_until" class="input-label">{{ __('Valid Until') }} <span class="text-red-600">*</span></label>
                            <input id="valid_until" name="valid_until" type="date" class="input" value="{{ old('valid_until', $quotation?->valid_until?->format('Y-m-d') ?? '') }}" />
                            <x-input-error :messages="$errors->get('valid_until')" class="mt-2" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Email') }}</label>
                            <input type="email" class="input" id="quot-email" value="{{ $selectedCustomer?->email ?? '' }}" readonly />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Phone') }}</label>
                            <input type="text" class="input" id="quot-phone" value="{{ $selectedCustomer?->phone ?? '' }}" readonly />
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
                            <label class="input-label">{{ __('Payment Terms') }}</label>
                            <input type="text" class="input" id="quot-terms" value="{{ $selectedCustomer?->payment_terms ?? '' }}" readonly />
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-400">Contact details shown are from the customer record.</p>
                </section>

                {{-- quotation info --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M7 3h10a2 2 0 0 1 2 2v16H5V5a2 2 0 0 1 2-2zM9 8h6M9 12h6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Quotation Information') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-2 gap-x-5 gap-y-4 mt-5 xl:grid-cols-4">
                        <div>
                            <label class="input-label">{{ __('Quotation №') }}</label>
                            <input type="text" class="input" value="{{ $quotation?->quotation_number ?? '' }}" placeholder="{{ __('Auto-assigned on save') }}" readonly />
                        </div>
                        <div>
                            <label for="quotation_date" class="input-label">{{ __('Date') }} <span class="text-red-600">*</span></label>
                            <input id="quotation_date" name="quotation_date" type="date" class="input" value="{{ old('quotation_date', $quotation?->quotation_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" required />
                            <x-input-error :messages="$errors->get('quotation_date')" class="mt-2" />
                        </div>
                        <div>
                            <label for="reference" class="input-label">{{ __('Reference №') }}</label>
                            <input id="reference" name="reference" type="text" class="input" value="{{ old('reference', $quotation?->reference ?? '') }}" placeholder="{{ __('Optional') }}" />
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
                            <label for="cost_center_id" class="input-label">{{ __('Cost Centre') }}</label>
                            <x-scoped-search-field
                                name="cost_center_id"
                                entity="cost-center"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'cost-center']) }}"
                                :value="old('cost_center_id', $selectedCostCenterId)"
                                :label="old('cost_center_id', $selectedCostCenterLabel)"
                                placeholder="{{ __('None') }}"
                            />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Department') }}</label>
                            <input type="text" class="input" placeholder="Operations" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Prepared By') }}</label>
                            <input type="text" class="input" value="{{ $quotation?->createdByUser?->name ?? auth()->user()?->name ?? '' }}" readonly />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Project') }}</label>
                            <input type="text" class="input" placeholder="{{ __('Optional') }}" />
                        </div>
                    </div>
                    <p class="mt-3 text-[11px] text-slate-400">Quotation № is auto-assigned on save. Department &amp; Project are display-only.</p>
                </section>

                {{-- line items --}}
                <section class="card relative z-30 rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 6h16M4 12h16M4 18h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Line Items') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                        <button type="button" id="quot-add-line" class="{{ $btnGhost }}" style="height:34px;padding:0 13px;font-size:12.5px;border-radius:10px;margin-left:12px;">＋ {{ __('Add Line') }}</button>
                    </div>

                    <div class="mt-4 border border-shell rounded-[14px] overflow-visible round-thead-clip bg-[#fbfcfe]">
                        <table id="quot-lines-table" class="w-full border-collapse text-[13px] table-fixed">
                            <thead>
                                <tr>
                                    <th class="w-[13%] py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Item Code') }}</th>
                                    <th class="w-[17%] py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Item Name') }}</th>
                                    <th class="py-[11px] px-2.5 text-left text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Description') }}</th>
                                    <th class="w-[8%] py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Qty') }}</th>
                                    <th class="w-[13%] py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Unit Price') }} ({{ $cs }})</th>
                                    <th class="w-[8%] py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Disc %') }}</th>
                                    <th class="w-[12%] py-[11px] px-2.5 text-right text-[10.5px] font-bold uppercase tracking-[0.08em] text-navy-200 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th class="w-[8%] py-[11px] px-2.5 bg-gradient-to-b from-navy-700 via-navy-800 to-navy-900 shadow-thead"></th>
                                </tr>
                            </thead>
                            <tbody id="quot-lines-body"></tbody>
                        </table>
                    </div>

                    <x-input-error :messages="$errors->get('lines')" class="mt-2" />
                </section>

                {{-- notes --}}
                <section class="card rounded-[20px] p-6 xl:p-[26px]">
                    <div class="{{ $secHead }}">
                        <span class="{{ $secIc }}"><svg width="15" height="15" viewBox="0 0 24 24" fill="none" aria-hidden="true"><path d="M4 5h16M4 10h16M4 15h10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg></span>
                        <h2 class="text-[15px] font-extrabold text-[#128F8E]">{{ __('Notes') }}</h2>
                        <span class="flex-1 h-px bg-line"></span>
                    </div>
                    <div class="grid grid-cols-1 gap-x-5 gap-y-4 mt-5 xl:grid-cols-2">
                        <div>
                            <label for="memo" class="input-label">{{ __('Customer Notes') }}</label>
                            <textarea id="memo" name="memo" class="input" placeholder="Printed on the quotation PDF…">{{ old('memo', $quotation?->memo ?? '') }}</textarea>
                            <x-input-error :messages="$errors->get('memo')" class="mt-2" />
                        </div>
                        <div>
                            <label class="input-label">{{ __('Internal Notes') }}</label>
                            <textarea class="input" placeholder="Visible to your team only…"></textarea>
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

                    @if(!empty($quotationAttachments))
                        <ul id="quot-existing-files" class="mt-4 divide-y divide-line border border-shell rounded-[14px] bg-[#fbfcfe]">
                            @foreach($quotationAttachments as $attachment)
                                <li data-attachment-id="{{ $attachment->id }}" class="flex items-center gap-3 px-4 py-2.5 text-[13px]">
                                    <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                                    <span class="flex-1 min-w-0 truncate text-gray-800">{{ $attachment->name }}</span>
                                    <span class="shrink-0 text-[11px] text-slate-400">{{ format_bytes($attachment->file_size) }}</span>
                                    <button type="button" class="bill-ibtn bill-ibtn--del" title="{{ __('Remove') }}" aria-label="{{ __('Remove') }}" onclick="quotRemoveExistingFile(this)">🗑</button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <label class="bill-drop mt-4" id="quot-dropzone" for="quot-files">
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

                    <input type="file" id="quot-files" name="files[]" multiple
                           accept=".pdf,.jpg,.jpeg,.png,.gif,.webp,.xls,.xlsx,.doc,.docx,.txt,.csv" class="hidden" />

                    <ul id="quot-new-files" class="bill-file-list"></ul>

                    <x-input-error :messages="$errors->get('files')" class="mt-2" />
                </section>
            </div>

            {{-- live preview --}}
            <aside class="quot-prev">
                <span class="quot-prev-label">{{ __('Live Document Preview') }}</span>
                <div class="quot-paper">
                    <div class="quot-p-head">
                        <span class="quot-p-title">{{ __('Quotation') }}</span>
                        <span class="quot-p-num">{{ $quotation?->quotation_number ?? '—' }}</span>
                    </div>
                    <div class="quot-p-body">
                        <div class="quot-p-grid">
                            <div><div class="quot-p-l">{{ __('Customer') }}</div><div class="quot-p-v" id="p-cust">{{ $selectedCustomer?->name ?? '—' }}</div></div>
                            <div><div class="quot-p-l">{{ __('Contact') }}</div><div class="quot-p-v" id="p-contact">{{ $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '—' }}</div></div>
                            <div><div class="quot-p-l">{{ __('Date') }}</div><div class="quot-p-v" id="p-date">{{ $quotation?->quotation_date?->format('M d, Y') ?? now()->format('M d, Y') }}</div></div>
                            <div><div class="quot-p-l">{{ __('Valid Until') }}</div><div class="quot-p-v" id="p-valid">{{ $quotation?->valid_until?->format('M d, Y') ?? '—' }}</div></div>
                        </div>
                        <div class="quot-p-lines" id="p-lines"></div>
                        <div class="quot-p-totals">
                            <div class="quot-p-row"><span>{{ __('Subtotal') }}</span><span class="v" id="v-sub">0.00</span></div>
                            <div class="quot-p-row" id="r-disc" style="display:none"><span>{{ __('Discount') }}</span><span class="v" id="v-disc">0.00</span></div>
                            <div class="quot-p-row" id="r-tax" style="display:none"><span>{{ __('Tax') }}</span><span class="v" id="v-tax">0.00</span></div>
                        </div>
                        <div class="quot-p-gt"><span class="quot-p-gt-label">{{ __('Grand Total') }}</span><span class="quot-p-gt-val" id="v-gt">{{ $cs }}0.00</span></div>
                        <p class="quot-p-foot" id="p-foot">{{ $quotation?->memo ?? '—' }}</p>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
    const PRODUCT_SEARCH_URL = @json(route('accounting.search.entity', ['entity' => 'product']));
    const QUOT_CS = @json($cs);
    const QUOT_DEFAULT_INCOME_ACCOUNT_ID = @json((string) $defaultIncomeAccountId);
    const QUOT_LINES = @json($linesData);
    let quotCustomerName = @json($selectedCustomer?->name ?? '');

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

    function quotLineRow(data) {
        const d = data || {};
        const idx = lineIndex++;
        const gross = parse(d.quantity) * parse(d.unit_price);
        const flat = parse(d.discount);
        const pct = d.discount_pct != null
            ? parse(d.discount_pct)
            : (gross > 0 ? Math.round(flat / gross * 10000) / 100 : 0);
        const taxRate = parse(d.tax_rate);
        const incomeAccountId = d.income_account_id || QUOT_DEFAULT_INCOME_ACCOUNT_ID || '';

        return `
            <tr class="quot-row">
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
                    <span class="bill-amt bill-line-total">0.00</span>
                </td>
                <td class="py-2 px-1.5 border-b border-line align-middle">
                    <div class="flex gap-1 justify-end">
                        <button type="button" class="bill-ibtn" title="Duplicate line" aria-label="Duplicate line" onclick="quotDuplicateRow(this)">⧉</button>
                        <button type="button" class="bill-ibtn bill-ibtn--del" title="Delete line" aria-label="Delete line" onclick="quotRemoveLine(this)">🗑</button>
                    </div>
                </td>
                <input type="hidden" name="lines[${idx}][tax_rate]" class="quot-tax-rate" value="${taxRate}" />
                <input type="hidden" name="lines[${idx}][discount]" class="quot-flat-discount" value="${flat}" />
                <input type="hidden" name="lines[${idx}][income_account_id]" class="quot-income-account" value="${incomeAccountId}" />
            </tr>
        `;
    }

    function quotRowData(row) {
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
            tax_rate: num('.quot-tax-rate'),
            income_account_id: g('.quot-income-account'),
        };
    }

    function quotAddLine(data) {
        document.getElementById('quot-lines-body')
            .insertAdjacentHTML('beforeend', quotLineRow(data || {}));
        quotUpdateTotals();
    }

    function quotRemoveLine(btn) {
        const row = btn.closest('tr.quot-row');
        row.remove();
        if (!document.querySelector('#quot-lines-body tr.quot-row')) quotAddLine();
        quotUpdateTotals();
    }

    function quotDuplicateRow(btn) {
        const row = btn.closest('tr.quot-row');
        row.insertAdjacentHTML('afterend', quotLineRow(quotRowData(row)));
        quotUpdateTotals();
    }

    function quotUpdateTotals() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;
        const lines = [];

        document.querySelectorAll('#quot-lines-body tr.quot-row').forEach(row => {
            const qty = parse(row.querySelector('.bill-qty').value);
            const price = parse(row.querySelector('.bill-price').value);
            const pct = parse(row.querySelector('.bill-disc-pct').value);
            const rate = parse(row.querySelector('.quot-tax-rate').value);

            const gross = qty * price;
            const flat = gross > 0 ? Math.round(gross * pct) / 100 : 0;
            row.querySelector('.quot-flat-discount').value = flat.toFixed(2);

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
        document.getElementById('v-gt').textContent = QUOT_CS + fmt(grand);

        const holder = document.getElementById('p-lines');
        holder.innerHTML = lines.map(l =>
            `<div class="quot-p-line"><span class="quot-p-code">${esc(l.c) || '—'}</span><span class="quot-p-name">${esc(l.n) || 'Untitled'}</span><span class="quot-p-qty">×${l.q}</span><span class="quot-p-amt">${fmt(l.a)}</span></div>`
        ).join('') || '<div class="quot-p-line"><span class="quot-p-name" style="color:#94a3b8">No line items yet</span></div>';
    }

    function quotSync() {
        document.getElementById('p-cust').textContent = quotCustomerName || '—';
        document.getElementById('p-contact').textContent = document.getElementById('quot-contact').value || '—';
        document.getElementById('p-date').textContent = fmtDate(document.getElementById('quotation_date').value);
        document.getElementById('p-valid').textContent = fmtDate(document.getElementById('valid_until').value);
        document.getElementById('p-foot').textContent = document.getElementById('memo').value || '—';
        quotUpdateTotals();
    }

    function quotCustomerSelected(id, item) {
        if (item) {
            document.getElementById('quot-contact').value = item.display_name || item.label || '';
            document.getElementById('quot-email').value = item.email || '';
            document.getElementById('quot-phone').value = item.phone || '';
            document.getElementById('quot-terms').value = item.payment_terms || '';
            quotCustomerName = item.label || '';
        }
        quotSync();
    }

    document.getElementById('quot-add-line').addEventListener('click', () => quotAddLine());

    const quotLinesBody = document.getElementById('quot-lines-body');
    quotLinesBody.addEventListener('item-selected', e => {
        const row = e.target.closest('tr.quot-row');
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
            const rate = row.querySelector('.quot-tax-rate');
            if (rate) rate.value = item.tax_rate;
        }
        const acct = row.querySelector('.quot-income-account');
        if (acct) acct.value = item.income_account_id || QUOT_DEFAULT_INCOME_ACCOUNT_ID || '';

        quotUpdateTotals();
    });

    quotLinesBody.addEventListener('input', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) quotUpdateTotals();
    });
    quotLinesBody.addEventListener('change', e => {
        if (e.target.closest('.bill-qty, .bill-price, .bill-disc-pct')) quotUpdateTotals();
    });

    ['quotation_date', 'valid_until', 'memo'].forEach(id => {
        const el = document.getElementById(id);
        if (el) el.addEventListener('input', quotSync);
    });

    document.getElementById('quotation-form').addEventListener('submit', quotUpdateTotals);

    (QUOT_LINES.length ? QUOT_LINES : [{}]).forEach(d => quotAddLine(d));
    quotSync();

    /* ── attachments ─────────────────────────────────────────── */
    let quotNewFiles = [];

    function quotFmtSize(b) {
        if (b >= 1048576) return (b / 1048576).toFixed(1) + ' MB';
        if (b >= 1024) return (b / 1024).toFixed(1) + ' KB';
        return b + ' B';
    }

    function quotRenderNewFiles() {
        const list = document.getElementById('quot-new-files');
        if (!list) return;
        list.innerHTML = '';
        quotNewFiles.forEach((f, i) => {
            const li = document.createElement('li');
            li.dataset.i = i;
            li.className = 'flex items-center gap-3 px-4 py-2.5 text-[13px]';
            li.innerHTML = `
                <svg class="w-4 h-4 shrink-0 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                <span class="flex-1 min-w-0 truncate text-gray-800">${esc(f.name)}</span>
                <span class="shrink-0 text-[11px] text-slate-400">${quotFmtSize(f.size)}</span>
                <button type="button" class="bill-ibtn bill-ibtn--del" title="Remove" aria-label="Remove" onclick="quotRemoveNewFile(this)">🗑</button>
            `;
            list.appendChild(li);
        });
    }

    function quotSyncFileInput() {
        const input = document.getElementById('quot-files');
        if (!input) return;
        const dt = new DataTransfer();
        quotNewFiles.forEach(f => dt.items.add(f));
        input.files = dt.files;
    }

    function quotAddFiles(fileList) {
        Array.from(fileList || []).forEach(f => quotNewFiles.push(f));
        quotRenderNewFiles();
        quotSyncFileInput();
    }

    function quotRemoveNewFile(btn) {
        const li = btn.closest('li[data-i]');
        if (!li) return;
        quotNewFiles.splice(parseInt(li.dataset.i, 10), 1);
        quotRenderNewFiles();
        quotSyncFileInput();
    }

    function quotRemoveExistingFile(btn) {
        const li = btn.closest('li[data-attachment-id]');
        if (!li) return;
        const id = li.dataset.attachmentId;
        let holder = document.getElementById('quot-delete-documents');
        if (!holder) {
            holder = document.createElement('div');
            holder.id = 'quot-delete-documents';
            document.getElementById('quotation-form').appendChild(holder);
        }
        const hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'delete_documents[]';
        hidden.value = id;
        holder.appendChild(hidden);
        li.remove();
    }

    function quotWireDropzone() {
        const dz = document.getElementById('quot-dropzone');
        const input = document.getElementById('quot-files');
        if (!dz || !input) return;

        input.addEventListener('change', () => quotAddFiles(input.files));

        ['dragover', 'dragenter'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.add('bill-drop--drag');
        }));
        ['dragleave', 'drop'].forEach(ev => dz.addEventListener(ev, e => {
            e.preventDefault();
            dz.classList.remove('bill-drop--drag');
        }));
        dz.addEventListener('drop', e => {
            if (e.dataTransfer && e.dataTransfer.files.length) quotAddFiles(e.dataTransfer.files);
        });
    }

    quotWireDropzone();
</script>
