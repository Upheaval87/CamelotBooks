@php
    $invoice = $invoice ?? null;
    $isEdit = $isEdit ?? (bool) $invoice;
    $formAction = $formAction ?? ($isEdit ? route('accounting.invoices.update', $invoice) : route('accounting.invoices.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? ($isEdit ? route('accounting.invoices.show', $invoice) : route('accounting.invoices.index'));

    $copyQuote = $copyQuote ?? null;
    $copyQuotes = $copyQuotes ?? collect();
    $preselectCustomer = $preselectCustomer ?? null;
    $customers = $customers ?? collect();
    $products = $products ?? collect();
    $costCenters = $costCenters ?? collect();

    $cs = $cs ?? \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');

    $termsLabels = [
        'due_on_receipt' => 'Due on receipt',
        'net_15' => 'Net 15',
        'net_30' => 'Net 30',
        'net_60' => 'Net 60',
        'net_90' => 'Net 90',
        'custom' => 'Custom',
    ];
    $termDays = ['due_on_receipt' => 0, 'net_15' => 15, 'net_30' => 30, 'net_60' => 60, 'net_90' => 90, 'custom' => 30];

    $normalizeTerms = function ($raw) {
        $v = strtolower(trim((string) $raw));
        if ($v === '') {
            return null;
        }
        $v = preg_replace('/[\s\-_]+/', ' ', $v);
        if (in_array($v, ['due on receipt', 'cod', 'cash on delivery', 'receipt'], true)) {
            return 'due_on_receipt';
        }
        if (preg_match('/^net\s*(\d+)$/', $v, $m)) {
            $key = 'net_' . (int) $m[1];

            return in_array($key, ['net_15', 'net_30', 'net_60', 'net_90'], true) ? $key : 'net_30';
        }

        return $v === 'custom' ? 'custom' : null;
    };

    $selectedCustomerId = old('customer_id', $copyQuote['customer_id'] ?? $preselectCustomer['id'] ?? $invoice?->customer_id ?? '');
    $selectedCustomerLabel = old('customer_search', $copyQuote['customer_name'] ?? $preselectCustomer['name'] ?? ($invoice?->customer?->name ?? ''));
    $selectedCustomer = $customers->firstWhere('id', (int) $selectedCustomerId);

    $defaultType = $isEdit ? ($invoice->customer_id ? 'existing' : 'walkin') : 'existing';
    $customerType = old('customer_type', $defaultType);
    $isWalkin = $customerType === 'walkin';

    $walkinName = old('customer_name', $invoice?->customer_name ?? '');
    $contactPerson = old('contact_person', $invoice?->contact_person ?? $copyQuote['customer_contact'] ?? $selectedCustomer?->display_name ?? $selectedCustomer?->name ?? '');
    $emailValue = old('email', $invoice?->email ?? $copyQuote['customer_email'] ?? $selectedCustomer?->email ?? '');
    $phoneValue = old('phone', $invoice?->phone ?? $copyQuote['customer_phone'] ?? $selectedCustomer?->phone ?? '');
    $addressValue = old('bill_address', $invoice?->bill_address ?? $selectedCustomer?->billing_address ?? '');

    $termsKey = old('terms_key', $invoice?->terms_key ?? ($copyQuote ? $normalizeTerms($copyQuote['customer_terms'] ?? '') : null));
    if (!is_string($termsKey) || !array_key_exists($termsKey, $termsLabels)) {
        $termsKey = 'net_30';
    }
    $extraTermsKey = in_array($termsKey, ['due_on_receipt', 'net_15', 'net_30', 'net_60'], true) ? null : $termsKey;

    $issueDate = old('invoice_date', $invoice?->invoice_date ?? today()->toDateString());
    $dueDefault = $invoice?->due_date ?? \Carbon\Carbon::parse($issueDate)->addDays($termDays[$termsKey] ?? 30)->toDateString();
    $dueDate = old('due_date', $dueDefault);

    $reference = old('reference', $invoice?->reference ?? '');
    $sourceRef = old('source_ref', $invoice?->source_ref ?? ($copyQuote['quotation_number'] ?? ''));
    $sourceQuotationId = old('source_quotation_id', $invoice?->source_quotation_id ?? ($copyQuote['quotation_id'] ?? ($copyQuote['id'] ?? '')));

    $ccValue = old('cost_centre', $invoice?->cost_centre ?? '');
    $ccCodes = $costCenters->pluck('code')->all();
    $extraCC = ($ccValue !== '' && $ccValue !== null && !in_array($ccValue, $ccCodes, true)) ? $ccValue : null;

    $department = old('department', $invoice?->department ?? 'Finance');
    $preparedBy = old('prepared_by', $invoice?->prepared_by ?? auth()->user()?->name ?? '');
    $notesCustomer = old('notes_customer', $invoice?->notes_customer ?? ($copyQuote['memo'] ?? ''));
    $notesInternal = old('notes_internal', $invoice?->notes_internal ?? '');

    $linesData = [];
    if (old('lines')) {
        foreach (array_values(old('lines')) as $l) {
            $p = !empty($l['product_id']) ? $products->firstWhere('id', (int) $l['product_id']) : null;
            $linesData[] = [
                'item_code' => ($l['item_code'] ?? '') ?: ($p?->sku ?? ''),
                'item_name' => ($l['item_name'] ?? '') ?: ($p?->name ?? ''),
                'description' => $l['description'] ?? '',
                'quantity' => $l['quantity'] ?? 1,
                'unit_price' => $l['unit_price'] ?? 0,
                'product_id' => $l['product_id'] ?? '',
                'discount' => $l['discount'] ?? '',
                'tax_rate' => $l['tax_rate'] ?? '',
                'income_account_id' => $l['income_account_id'] ?? '',
                'cost_center_id' => $l['cost_center_id'] ?? '',
            ];
        }
    } elseif ($invoice) {
        $linesData = $invoice->lines->map(fn ($l) => [
            'item_code' => $l->item_code ?: ($l->product?->sku ?? ''),
            'item_name' => $l->item_name ?: ($l->product?->name ?? ''),
            'description' => $l->description,
            'quantity' => $l->quantity,
            'unit_price' => $l->unit_price,
            'product_id' => $l->product_id ?? '',
            'discount' => $l->discount,
            'tax_rate' => $l->tax_rate,
            'income_account_id' => $l->income_account_id,
            'cost_center_id' => $l->cost_center_id,
        ])->all();
    } elseif ($copyQuote) {
        foreach ($copyQuote['lines'] as $l) {
            $linesData[] = [
                'item_code' => $l['sku'] ?? '',
                'item_name' => $l['label'] ?? '',
                'description' => $l['description'] ?? '',
                'quantity' => $l['quantity'] ?? 1,
                'unit_price' => $l['unit_price'] ?? 0,
                'product_id' => $l['product_id'] ?? '',
                'discount' => $l['discount'] ?? '',
                'tax_rate' => $l['tax_rate'] ?? '',
                'income_account_id' => $l['income_account_id'] ?? '',
                'cost_center_id' => $l['cost_center_id'] ?? '',
            ];
        }
    }

    if (!$linesData) {
        $linesData[] = [
            'item_code' => '', 'item_name' => '', 'description' => '',
            'quantity' => 1, 'unit_price' => 0,
            'product_id' => '', 'discount' => '', 'tax_rate' => '',
            'income_account_id' => '', 'cost_center_id' => '',
        ];
    }

    $invCustomers = $customers->map(fn ($c) => [
        'id' => $c->id,
        'name' => $c->name,
        'email' => $c->email ?? '',
        'phone' => $c->phone ?? '',
        'contact' => $c->display_name ?: $c->name,
        'address' => $c->billing_address ?? '',
    ])->values();

    $invQuotes = $copyQuotes->map(fn ($q) => [
        'id' => $q->id,
        'ref' => $q->quotation_number,
        'status' => ucfirst($q->status),
        'cust' => $q->customer?->name ?? '',
        'email' => $q->customer?->email ?? '',
        'phone' => $q->customer?->phone ?? '',
        'address' => $q->customer?->billing_address ?? '',
        'terms' => $normalizeTerms($q->customer?->payment_terms ?? ''),
        'valid' => $q->valid_until?->toDateString(),
        'invoiced' => false,
        'notes' => $q->memo ?? '',
        'total' => (float) $q->total,
        'lines' => $q->lines->map(fn ($l) => [
            'code' => $l->product?->sku ?? '',
            'name' => $l->item_name ?: ($l->product?->name ?? ''),
            'desc' => $l->description ?? '',
            'qty' => (float) $l->quantity,
            'price' => (float) $l->unit_price,
        ])->values(),
    ])->values();
@endphp

<div class="invm page-form">
    <form method="POST" action="{{ $formAction }}" id="invoice-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <input type="hidden" name="mode" value="draft">
        <input type="hidden" name="customer_id" id="ncCustomerId" value="{{ $selectedCustomerId }}">
        <input type="hidden" name="source_quotation_id" id="ncSourceQid" value="{{ $sourceQuotationId }}">

        @if ($errors->any())
            <x-feedback.alert variant="error" class="mb-4">
                <ul style="list-style:disc;padding-left:18px;margin:0">
                    @foreach ($errors->all() as $errorMessage)
                        <li>{{ $errorMessage }}</li>
                    @endforeach
                </ul>
            </x-feedback.alert>
        @endif

        <div id="createView">
            <div class="chead">
                <div><h2 id="cvTitle">{{ $isEdit ? 'Edit Invoice — ' . $invoice->invoice_number : 'Create Invoice' }}</h2></div>
                <div class="cacts">
                    <a href="{{ $cancelRoute }}" class="btn btn-ghost" id="ncCancel"><svg viewBox="0 0 24 24"><path d="M19 12H5M12 19l-7-7 7-7"/></svg>Cancel</a>
                    <button type="button" class="btn btn-ghost" id="ncFromQ"@if ($isEdit) style="display:none"@endif><svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>From quotation</button>
                    <button type="submit" name="mode" value="draft" class="btn btn-ghost" id="ncDraft"><svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.12 2.12 0 013 3L7 19l-4 1 1-4z"/></svg>Save Draft</button>
                    <button type="submit" name="mode" value="post" class="btn btn-cta" id="ncPost" disabled title="Requires customer, due date and at least one complete line"><svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>Post Invoice</button>
                </div>
            </div>

            <div class="layout">
                <div>
                    <section class="card2">
                        <div class="fsec"><span class="fs-ic"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span><span class="fs-t">Bill To</span><span class="fs-rule"></span></div>
                        <div class="g3" style="margin-bottom:12px">
                            <div class="fld"><label>Customer type</label>
                                <select class="sel" id="ncMode" name="customer_type"><option value="existing" @selected(!$isWalkin)>Existing customer</option><option value="walkin" @selected($isWalkin)>Walk-in customer</option></select>
                            </div>
                            <div class="fld"><label>Payment terms</label>
                                <select class="sel" id="ncTerms" name="terms_key"><option value="due_on_receipt" @selected($termsKey === 'due_on_receipt')>Due on receipt</option><option value="net_15" @selected($termsKey === 'net_15')>Net 15</option><option value="net_30" @selected($termsKey === 'net_30')>Net 30</option><option value="net_60" @selected($termsKey === 'net_60')>Net 60</option>@if ($extraTermsKey)<option value="{{ $extraTermsKey }}" selected>{{ $termsLabels[$extraTermsKey] }}</option>@endif</select>
                            </div>
                            <div class="fld"><label>Source document</label>
                                <div class="fldrow">
                                    <input class="in" id="ncSource" name="source_ref" value="{{ $sourceRef }}" placeholder="SO-… / QT-… (optional)">
                                    <button class="btn btn-ghost btn-sm" id="ncPickQ" type="button" style="height:38px{{ $isEdit ? ';display:none' : '' }}" title="Load lines & customer from an approved quotation"><svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg>Pick</button>
                                </div>
                            </div>
                        </div>
                        <div class="fld" id="ncSearchWrap" style="margin-bottom:12px{{ $isWalkin ? ';display:none' : '' }}">
                            <label>Customer <b>*</b></label>
                            <div class="searchwrap"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><input class="in" id="ncSearch" name="customer_search" list="custList" value="{{ $selectedCustomerLabel }}" placeholder="Search customers… (name or code)" style="padding-left:36px"></div>
                            <datalist id="custList"></datalist>
                        </div>
                        <div class="fld" id="ncNameWrap" style="margin-bottom:12px{{ $isWalkin ? '' : ';display:none' }}">
                            <label>Customer name <b>*</b></label>
                            <input class="in" id="ncName" name="customer_name" value="{{ $walkinName }}" placeholder="e.g. Jane Mwale">
                        </div>
                        <div class="g4">
                            <div class="fld"><label>Contact person</label><input class="in" id="ncContact" name="contact_person" value="{{ $contactPerson }}" placeholder="—"></div>
                            <div class="fld"><label>Email</label><input class="in" id="ncEmail" name="email" type="email" value="{{ $emailValue }}" placeholder="name@company.com"></div>
                            <div class="fld"><label>Phone</label><input class="in" id="ncPhone" name="phone" value="{{ $phoneValue }}" placeholder="+265 …"></div>
                            <div class="fld"><label>Bill-to address</label><input class="in" id="ncAddress" name="bill_address" value="{{ $addressValue }}" placeholder="Street, city"></div>
                        </div>
                        <div class="savenote{{ $isWalkin ? ' on' : '' }}" id="ncSaveNote">Walk-in captured — these details will be saved to the customer registry for future use.</div>
                    </section>

                    <section class="card2">
                        <div class="fsec"><span class="fs-ic"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M14 2v6h6"/></svg></span><span class="fs-t">Invoice Information</span><span class="fs-rule"></span></div>
                        <div class="g4" style="margin-bottom:12px">
                            <div class="fld"><label>Invoice №</label><input class="in auto" id="ncNo" readonly placeholder="Auto-assigned on save"@if ($isEdit) value="{{ $invoice->invoice_number }}"@endif></div>
                            <div class="fld"><label>Issue date <b>*</b></label><input class="in" type="date" id="ncDate" name="invoice_date" value="{{ $issueDate }}"></div>
                            <div class="fld"><label>Due date <b>*</b></label><input class="in" type="date" id="ncDue" name="due_date" value="{{ $dueDate }}"></div>
                            <div class="fld"><label>Reference №</label><input class="in" id="ncRef" name="reference" value="{{ $reference }}" placeholder="Customer PO (optional)"></div>
                        </div>
                        <div class="g4">
                            <div class="fld"><label>Cost centre</label>
                                <select class="sel" id="ncCC" name="cost_centre">
                                    <option value="">None</option>
                                    @foreach ($costCenters as $costCentre)
                                        <option value="{{ $costCentre->code }}" @selected($ccValue === $costCentre->code)>{{ $costCentre->code }} · {{ $costCentre->name }}</option>
                                    @endforeach
                                    @if ($extraCC !== null)
                                        <option value="{{ $extraCC }}" selected>{{ $extraCC }}</option>
                                    @endif
                                </select>
                            </div>
                            <div class="fld"><label>Department</label><input class="in auto" name="department" value="{{ $department }}" readonly></div>
                            <div class="fld"><label>Prepared by</label><input class="in auto" name="prepared_by" value="{{ $preparedBy }}" readonly></div>
                            <div class="fld"><label>Tax regime</label><input class="in auto" value="VAT 16.5% inclusive pricing" readonly></div>
                        </div>
                    </section>

                    <section class="card2">
                        <div class="fsec"><span class="fs-ic"><svg viewBox="0 0 24 24"><path d="M3 6h18M3 12h18M3 18h12"/></svg></span><span class="fs-t">Invoice Lines</span><span class="fs-rule"></span>
                            <button class="btn-add" id="ncAdd" type="button" style="margin-left:auto"><svg viewBox="0 0 24 24"><path d="M12 5v14M5 12h14"/></svg>Add Line</button>
                        </div>
                        <table class="etable">
                            <thead><tr><th style="width:90px">Item code</th><th style="width:24%">Item name</th><th style="width:24%">Description (optional)</th><th class="r" style="width:60px">Qty</th><th class="r" style="width:110px">Unit price ({{ $cs }})</th><th class="r" style="width:120px">Amount</th><th style="width:40px"></th></tr></thead>
                            <tbody id="ncLines">
                                @foreach ($linesData as $line)
                                    <tr>
                                        <td><input class="lin" type="text" data-f="code" name="lines[{{ $loop->index }}][item_code]" value="{{ $line['item_code'] }}" placeholder="Code" autocomplete="off"></td>
                                        <td><input class="lin" type="text" data-f="name" name="lines[{{ $loop->index }}][item_name]" value="{{ $line['item_name'] }}" placeholder="Item name" autocomplete="off"></td>
                                        <td><input class="lin" type="text" data-f="desc" name="lines[{{ $loop->index }}][description]" value="{{ $line['description'] }}" placeholder="Optional" autocomplete="off"></td>
                                        <td><input class="lin num" type="number" data-f="q" name="lines[{{ $loop->index }}][quantity]" value="{{ $line['quantity'] }}" min="0" step="1"></td>
                                        <td><input class="lin num" type="number" data-f="p" name="lines[{{ $loop->index }}][unit_price]" value="{{ $line['unit_price'] }}" min="0" step="0.01"></td>
                                        <td class="amtcell ramt">{{ number_format((float) $line['quantity'] * (float) $line['unit_price'], 2) }}</td>
                                        <td>
                                            <input type="hidden" name="lines[{{ $loop->index }}][product_id]" value="{{ $line['product_id'] }}">
                                            <input type="hidden" name="lines[{{ $loop->index }}][discount]" value="{{ $line['discount'] }}">
                                            <input type="hidden" name="lines[{{ $loop->index }}][tax_rate]" value="{{ $line['tax_rate'] }}">
                                            <input type="hidden" name="lines[{{ $loop->index }}][income_account_id]" value="{{ $line['income_account_id'] }}">
                                            <input type="hidden" name="lines[{{ $loop->index }}][cost_center_id]" value="{{ $line['cost_center_id'] }}">
                                            <button class="xb" type="button" title="Remove line" aria-label="Remove line"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </section>

                    <section class="card2">
                        <div class="fsec"><span class="fs-ic"><svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 00-2 2v16a2 2 0 002 2h12a2 2 0 002-2V8z"/><path d="M16 13H8M16 17H8"/></svg></span><span class="fs-t">Notes</span><span class="fs-rule"></span></div>
                        <div class="g2">
                            <div class="fld"><label>Customer notes</label><textarea class="in" id="ncNotesC" name="notes_customer" placeholder="Printed on the tax invoice…">{{ $notesCustomer }}</textarea></div>
                            <div class="fld"><label>Internal notes</label><textarea class="in" id="ncNotesI" name="notes_internal" placeholder="Collection notes…">{{ $notesInternal }}</textarea></div>
                        </div>
                    </section>
                </div>

                <aside>
                    <section class="rcard">
                        <div class="rhead"><span class="ric"><svg viewBox="0 0 24 24"><path d="M3 3v18h18"/><path d="M8 17v-6M13 17V7M18 17v-3"/></svg></span><span class="rt">Summary</span><span class="rule"></span></div>
                        <div class="rbody" style="padding:12px 14px">
                            <div class="sumrow"><span>Customer</span><b id="nsCust">—</b></div>
                            <div class="sumrow"><span>Issue date</span><b id="nsDate">—</b></div>
                            <div class="sumrow"><span>Due date</span><b id="nsDue">—</b></div>
                            <div class="sumrow"><span>Post check</span><b id="nsGate" class="gate-no">—</b></div>
                            <div class="sumrow"><span>Subtotal</span><b id="nsSub">0.00</b></div>
                            <div class="sumrow"><span>Tax (16.5%)</span><b id="nsTax">0.00</b></div>
                            <div class="grandtile"><span class="l">Invoice Total</span><span class="v" id="nsGrand">{{ $cs }} 0.00</span></div>
                            <div class="sumnote" id="nsLines">No complete lines yet</div>
                        </div>
                    </section>
                    <section class="rcard">
                        <div class="rhead"><span class="ric"><svg viewBox="0 0 24 24"><path d="M4 6h16M4 12h10M4 18h6"/></svg></span><span class="rt">Quick nav</span><span class="rule"></span></div>
                        <div class="rbody">
                            <a class="vitem" id="qnavList" href="{{ route('accounting.invoices.index') }}"><span class="vic"><svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="M7 9h10M7 13h6"/></svg></span>All Invoices</a>
                            <a class="vitem" id="qnavOrders" href="{{ route('accounting.sales-orders.index') }}"><span class="vic"><svg viewBox="0 0 24 24"><path d="M21 8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg></span>Sales Orders</a>
                            <a class="vitem" id="qnavCust" href="{{ route('accounting.customers.index') }}"><span class="vic"><svg viewBox="0 0 24 24"><path d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>Customers</a>
                        </div>
                    </section>
                </aside>
            </div>
        </div>
    </form>

    <div class="scrim" id="quoteScrim">
        <div class="mbox narrow" role="dialog" aria-modal="true">
            <div class="m-head">
                <span class="m-ic"><svg viewBox="0 0 24 24"><path d="M22 2L11 13"/><path d="M22 2l-7 20-4-9-9-4z"/></svg></span>
                <div class="m-head-left"><span class="m-title">Create from quotation</span><span class="m-sub">uninvoiced quotations</span></div>
                <button class="m-close" data-close="quoteScrim" aria-label="Close"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg></button>
            </div>
            <div class="m-body">
                <div class="searchwrap" style="margin-bottom:12px"><svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg><input class="in" id="qqSearch" placeholder="Search quotations… (number, customer, item)" style="padding-left:36px"></div>
                <div id="quoteList"></div>
            </div>
            <div class="m-foot">
                <span class="mnote">Loading a quotation fills customer, terms, lines and notes — everything stays editable.</span>
                <button class="btn btn-close btn-sm" data-close="quoteScrim"><svg viewBox="0 0 24 24"><path d="M18 6L6 18M6 6l12 12"/></svg>Close</button>
            </div>
        </div>
    </div>

    @include('accounting.invoices._workspace-modals', ['ivPayload' => [], 'nextReceipt' => ''])

    <script>
        window.INV_CUSTOMERS = @json($invCustomers);
        window.INV_QUOTES = @json($invQuotes);
        window.INV_LINE_START = {{ count($linesData) }};
    </script>
</div>
