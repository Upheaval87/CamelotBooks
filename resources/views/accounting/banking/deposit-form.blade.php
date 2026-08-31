<x-app-layout>
    @php
        $available = $undepositedLines->map(fn ($l) => [
            'line_id' => $l['line_id'],
            'reference' => $l['receipt_number'] ?: ($l['reference'] ?? ''),
            'date' => $l['date']?->format('M d, Y') ?? '',
            'desc' => $l['memo'] ?? '',
            'payment_method' => $l['payment_method'] ?? '—',
            'amount' => (float) $l['amount'],
        ])->values()->all();
        $preselected = $preselected ?? [];
        $pmList = collect($available)
            ->pluck('payment_method')
            ->filter(fn ($m) => $m && $m !== '—')
            ->unique()
            ->sort()
            ->values()
            ->all();
    @endphp

    <div class="dp2-suite">
        <div class="dp2-wrap">

            <div class="dp2-phead">
                <div>
                    <h1>{{ __('New Deposit') }}</h1>
                    <div class="dp2-sub">{{ __('Pick the undeposited receipts to bank. The total is carried into the amount automatically.') }}</div>
                </div>
                <div class="dp2-acts">
                    <a href="{{ route('accounting.banking.deposits') }}" class="dp2-btn dp2-btn-ghost">{{ __('Cancel') }}</a>
                </div>
            </div>

            <form method="POST" action="{{ route('accounting.banking.deposits.store') }}" id="deposit-form"
                  x-data="depositForm({
                        available: {{ Js::from($available) }},
                        preselected: {{ Js::from($preselected) }},
                        pmOptions: {{ Js::from($pmList) }}
                  })">
                @csrf
                <template x-for="id in selected" :key="id">
                    <input type="hidden" name="line_ids[]" :value="id" />
                </template>
                <input type="hidden" name="action" x-model="action" />

                {{-- Deposit Details --}}
                <div class="dp2-card">
                    <div class="dp2-card-h">
                        <b>{{ __('Deposit Details') }}</b>
                    </div>
                    <div class="dp2-body">
                        <div class="dp2-g3">
                            <div>
                                <label class="dp2-lbl" for="bank_account_id">{{ __('Destination Bank Account') }} *</label>
                                <select id="bank_account_id" name="bank_account_id" class="dp2-in" required>
                                    <option value="">— {{ __('Choose bank account') }} —</option>
                                    @foreach($bankAccounts as $acc)
                                        <option value="{{ $acc->id }}" @selected(old('bank_account_id') == $acc->id)>{{ $acc->name }} · {{ $acc->code }}</option>
                                    @endforeach
                                </select>
                                @error('bank_account_id')<div class="dp2-sub" style="color:#b91c1c">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="dp2-lbl" for="date">{{ __('Deposit Date') }} *</label>
                                <input id="date" type="date" name="date" class="dp2-in" required value="{{ old('date', now()->format('Y-m-d')) }}" />
                                @error('date')<div class="dp2-sub" style="color:#b91c1c">{{ $message }}</div>@enderror
                            </div>
                            <div>
                                <label class="dp2-lbl" for="reference">{{ __('Reference') }}</label>
                                <input id="reference" type="text" name="reference" class="dp2-in" maxlength="255" value="{{ old('reference') }}" placeholder="e.g. Daily banking" />
                                @error('reference')<div class="dp2-sub" style="color:#b91c1c">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="dp2-g3" style="margin-top:14px">
                            <div>
                                <label class="dp2-lbl" for="description">{{ __('Description / Note') }}</label>
                                <input id="description" type="text" name="description" class="dp2-in" maxlength="500" value="{{ old('description') }}" placeholder="e.g. Banking of the day's cash receipts" />
                                @error('description')<div class="dp2-sub" style="color:#b91c1c">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Receipts to Deposit --}}
                <div class="dp2-card">
                    <div class="dp2-card-h">
                        <b>{{ __('Receipts to Deposit') }}</b>
                        <span class="dp2-sub" x-text="selected.length + ' selected'"></span>
                        <div class="dp2-right">
                            <button type="button" class="dp2-btn dp2-btn-sec dp2-btn-sm" @click="openAdd = true; modalSearch = ''; pmFilter = ''; modalSelected = []">{{ __('+ Add Receipts') }}</button>
                        </div>
                    </div>
                    <div class="dp2-scroll">
                        <table>
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Reference') }}</th>
                                    <th>{{ __('Description') }}</th>
                                    <th>{{ __('Payment Method') }}</th>
                                    <th class="dp2-num">{{ __('Amount') }} ({{ $cs }})</th>
                                    <th style="width:52px"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="row in selectedRows" :key="row.line_id">
                                    <tr>
                                        <td x-text="row.date"></td>
                                        <td class="dp2-ref" x-text="row.reference || row.line_id"></td>
                                        <td x-text="row.desc"></td>
                                        <td><span class="dp2-chip" x-text="row.payment_method"></span></td>
                                        <td class="dp2-num" x-text="fmt(row.amount)"></td>
                                        <td style="text-align:center">
                                            <button type="button" class="dp2-xbtn" :aria-label="'Remove ' + (row.reference || row.line_id)"
                                                    @click="remove(row.line_id)">✕</button>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="selected.length === 0">
                                    <td colspan="6"><div class="dp2-empty">{{ __('No receipts selected. Add undeposited receipts to deposit.') }}</div></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="dp2-amtbar">
                        <span>{{ __('DEPOSIT AMOUNT') }}</span>
                        <span x-text="'{{ $cs }}' + fmt(total)"></span>
                    </div>

                    <div style="padding:0 18px 18px;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap">
                        <button type="submit" class="dp2-btn dp2-btn-ghost" @click="action = 'draft'">{{ __('Save as Draft') }}</button>
                        <button type="submit" class="dp2-btn dp2-btn-cta" @click="action = 'post'" :disabled="selected.length === 0">{{ __('Save & Post') }}</button>
                    </div>

                    @if($errors->any())
                        <div style="padding:0 18px 18px">
                            <div style="color:#b91c1c;font-size:12.5px;font-weight:600" role="alert">
                                @foreach($errors->all() as $e)<div>{{ $e }}</div>@endforeach
                            </div>
                        </div>
                    @endif
                </div>

                {{-- Add Receipts modal --}}
                <div class="dp2-modal" :class="{ 'dp2-on': openAdd }" @keydown.escape.window="if(openAdd) openAdd = false">
                    <div class="dp2-mbox dp2-mbox--lg" role="dialog" aria-modal="true" aria-labelledby="add-receipts-title">
                        <div class="dp2-mbox-h">{{ __('Add Undeposited Receipts') }}</div>
                        <div class="dp2-mbox-b">

                            {{-- Toolbar: search + PM filter + bulk actions --}}
                            <div class="dp2-modal-toolbar">
                                <input type="text" class="dp2-in dp2-modal-search" placeholder="{{ __('Search by reference or description…') }}"
                                       x-model.debounce.250ms="modalSearch" />
                                <select class="dp2-in dp2-pm-select" x-model="pmFilter">
                                    <option value="">{{ __('All payment methods') }}</option>
                                    <template x-for="pm in pmOptions" :key="pm">
                                        <option :value="pm" x-text="pm"></option>
                                    </template>
                                </select>
                                <span class="dp2-sub" x-text="filteredModal.length + ' available'"></span>
                                <div class="dp2-right" style="display:flex;gap:6px">
                                    <button type="button" class="dp2-btn dp2-btn-ghost dp2-btn-xs" @click="selectAllModal()" x-show="modalSelected.length < filteredModal.length">{{ __('Select all') }}</button>
                                    <button type="button" class="dp2-btn dp2-btn-ghost dp2-btn-xs" @click="modalSelected = []" x-show="modalSelected.length > 0">{{ __('Deselect all') }}</button>
                                    <button type="button" class="dp2-btn dp2-btn-cta dp2-btn-sm" @click="addModalSelected()" :disabled="modalSelected.length === 0"
                                            x-text="modalSelected.length > 0 ? 'Add ' + modalSelected.length + ' receipt' + (modalSelected.length > 1 ? 's' : '') : '{{ __('Add selected') }}'">{{ __('Add selected') }}</button>
                                </div>
                            </div>

                            <div class="dp2-scroll">
                                <table>
                                    <thead>
                                        <tr>
                                            <th style="width:40px"><input type="checkbox" @change="toggleAllModal($event)" :checked="filteredModal.length > 0 && modalSelected.length === filteredModal.length" /></th>
                                            <th>{{ __('Date') }}</th>
                                            <th>{{ __('Reference') }}</th>
                                            <th>{{ __('Description') }}</th>
                                            <th>{{ __('Payment Method') }}</th>
                                            <th class="dp2-num">{{ __('Amount') }} ({{ $cs }})</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="row in filteredModal" :key="row.line_id">
                                            <tr :class="{ 'dp2-row-sel': modalSelected.includes(row.line_id) }">
                                                <td style="text-align:center">
                                                    <input type="checkbox" :value="row.line_id" :checked="modalSelected.includes(row.line_id)" @change="toggleModalRow(row.line_id)" />
                                                </td>
                                                <td x-text="row.date"></td>
                                                <td class="dp2-ref" x-text="row.reference || row.line_id"></td>
                                                <td x-text="row.desc"></td>
                                                <td><span class="dp2-chip" x-text="row.payment_method"></span></td>
                                                <td class="dp2-num" x-text="fmt(row.amount)"></td>
                                            </tr>
                                        </template>
                                        <tr x-show="filteredModal.length === 0">
                                            <td colspan="6"><div class="dp2-empty">{{ __('No undeposited receipts available.') }}</div></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <div class="dp2-mbox-f">
                            <button type="button" class="dp2-btn dp2-btn-ghost dp2-btn-sm" @click="openAdd = false">{{ __('Done') }}</button>
                        </div>
                    </div>
                </div>

            </form>

        </div>
    </div>

    <script>
        function depositForm(config) {
            return {
                available: config.available || [],
                selected: config.preselected || [],
                action: 'post',
                openAdd: false,
                modalSearch: '',
                pmFilter: '',
                pmOptions: config.pmOptions || [],
                modalSelected: [],
                get selectedRows() {
                    return this.selected
                        .map(id => this.available.find(r => r.line_id === id))
                        .filter(Boolean);
                },
                get filteredModal() {
                    let rows = this.available.filter(r => !this.selected.includes(r.line_id));
                    if (this.pmFilter) rows = rows.filter(r => r.payment_method === this.pmFilter);
                    if (this.modalSearch) {
                        const q = this.modalSearch.toLowerCase();
                        rows = rows.filter(r =>
                            (r.reference || '').toLowerCase().includes(q) ||
                            (r.desc || '').toLowerCase().includes(q)
                        );
                    }
                    return rows;
                },
                get total() {
                    return this.selectedRows.reduce((s, r) => s + Number(r.amount || 0), 0);
                },
                fmt(n) {
                    return new Intl.NumberFormat(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 }).format(Number(n || 0));
                },
                add(id) {
                    if (!this.selected.includes(id)) this.selected.push(id);
                },
                remove(id) {
                    this.selected = this.selected.filter(x => x !== id);
                },
                toggleModalRow(id) {
                    if (this.modalSelected.includes(id)) {
                        this.modalSelected = this.modalSelected.filter(x => x !== id);
                    } else {
                        this.modalSelected.push(id);
                    }
                },
                toggleAllModal(e) {
                    if (e.target.checked) {
                        this.modalSelected = this.filteredModal.map(r => r.line_id);
                    } else {
                        this.modalSelected = [];
                    }
                },
                selectAllModal() {
                    this.modalSelected = this.filteredModal.map(r => r.line_id);
                },
                addModalSelected() {
                    this.modalSelected.forEach(id => {
                        if (!this.selected.includes(id)) this.selected.push(id);
                    });
                    this.modalSelected = [];
                },
            };
        }
    </script>
</x-app-layout>
