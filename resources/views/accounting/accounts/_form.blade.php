@php
    $account = $account ?? null;
    $isEdit = $isEdit ?? (bool) $account;
    $formAction = $formAction ?? ($isEdit ? route('accounting.accounts.update', $account) : route('accounting.accounts.store'));
    $formMethod = $formMethod ?? ($isEdit ? 'PUT' : 'POST');
    $cancelRoute = $cancelRoute ?? route('accounting.accounts.index');
    $title = $title ?? ($isEdit ? __('Edit Account') : __('Create Account'));
    $subtitle = $subtitle ?? ($isEdit
        ? ($account ? $account->code . ' - ' . $account->name : '')
        : 'Add a new account to your chart of accounts.');
    $submitLabel = $submitLabel ?? ($isEdit ? __('Update Account') : __('Create Account'));
@endphp

<div class="suite">

    {{-- sticky page head --}}
    <div class="sticky-head">
        <div>
            <h1>{{ $title }}</h1>
            <div class="sub">{{ $subtitle }}</div>
        </div>
        <div class="tbtns">
            <a href="{{ $cancelRoute }}" class="btn ghost sm">{{ __('Cancel') }}</a>
            <button type="submit" form="account-form" class="btn cta">{{ $submitLabel }}</button>
        </div>
    </div>

    <form method="POST" action="{{ $formAction }}" id="account-form" novalidate>
        @csrf
        @if ($formMethod === 'PUT')
            @method('PUT')
        @endif

        <x-input-error :messages="$errors->get('error')" class="mb-4" />

        <div class="shell">
            <div class="flex flex-col gap-5 min-w-0">

                {{-- account details --}}
                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/></svg></span>
                        <h2>Account Details</h2>
                        <span class="rule"></span>
                    </div>
                    <div class="g4">
                        <div class="field sp2">
                            <label for="code">Code <span class="req">*</span></label>
                            <input id="code" name="code" type="text" class="input" value="{{ $isEdit ? old('code', $account->code) : old('code') }}" placeholder="e.g. 1000" required autofocus />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="name">Name <span class="req">*</span></label>
                            <input id="name" name="name" type="text" class="input" value="{{ $isEdit ? old('name', $account->name) : old('name') }}" placeholder="e.g. Petty Cash" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="type">Type <span class="req">*</span></label>
                            <select id="type" name="type" class="input" required>
                                <option value="">Select Type</option>
                                <option value="asset" {{ ($isEdit ? old('type', $account->type) : old('type')) === 'asset' ? 'selected' : '' }}>Asset</option>
                                <option value="liability" {{ ($isEdit ? old('type', $account->type) : old('type')) === 'liability' ? 'selected' : '' }}>Liability</option>
                                <option value="equity" {{ ($isEdit ? old('type', $account->type) : old('type')) === 'equity' ? 'selected' : '' }}>Equity</option>
                                <option value="income" {{ ($isEdit ? old('type', $account->type) : old('type')) === 'income' ? 'selected' : '' }}>Income</option>
                                <option value="expense" {{ ($isEdit ? old('type', $account->type) : old('type')) === 'expense' ? 'selected' : '' }}>Expense</option>
                            </select>
                            <x-input-error :messages="$errors->get('type')" class="mt-2" />
                        </div>
                        <div class="field">
                            <label for="sub_type">Sub Type <span class="req">*</span></label>
                            <select id="sub_type" name="sub_type" class="input" required>
                                <option value="">Select Sub Type</option>
                            </select>
                            <x-input-error :messages="$errors->get('sub_type')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="parent_id">Parent Account (Optional)</label>
                            <x-scoped-search-field
                                name="parent_id"
                                entity="account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                :value="$isEdit ? old('parent_id', $account->parent_id) : old('parent_id')"
                                :label="$isEdit
                                    ? (old('parent_id', $account->parent_id)
                                        ? (($parentAccounts->firstWhere('id', (int) old('parent_id', $account->parent_id)))
                                            ? $parentAccounts->firstWhere('id', (int) old('parent_id', $account->parent_id))->code . ' - ' . $parentAccounts->firstWhere('id', (int) old('parent_id', $account->parent_id))->name
                                            : '')
                                        : '')
                                    : (old('parent_id')
                                        ? (($parentAccounts->firstWhere('id', (int) old('parent_id')))
                                            ? $parentAccounts->firstWhere('id', (int) old('parent_id'))->code . ' - ' . $parentAccounts->firstWhere('id', (int) old('parent_id'))->name
                                            : '')
                                        : '')"
                                placeholder="{{ __('None (Top Level)') }}"
                            />
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>
                        <div class="field sp4">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3" class="input">{{ $isEdit ? old('description', $account->description) : old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="opening_balance">Opening Balance</label>
                            <input id="opening_balance" name="opening_balance" type="number" step="0.01" min="0" class="input" value="{{ $isEdit ? old('opening_balance', $account->opening_balance) : old('opening_balance', '0.00') }}" />
                            <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="opening_balance_date">Opening Balance Date</label>
                            <input id="opening_balance_date" name="opening_balance_date" type="date" class="input" value="{{ $isEdit ? old('opening_balance_date', $account->opening_balance_date?->format('Y-m-d')) : old('opening_balance_date') }}" />
                            <x-input-error :messages="$errors->get('opening_balance_date')" class="mt-2" />
                        </div>
                        <div class="field sp2">
                            <label for="currency">Currency <span class="req">*</span></label>
                            <input id="currency" name="currency" type="text" class="input" value="{{ $isEdit ? old('currency', $account->currency) : old('currency', 'USD') }}" maxlength="10" required />
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>
                    </div>
                </section>
            </div>

            {{-- right rail --}}
            <aside>
                <div class="railsum">
                    <div class="card">
                        <div class="rail-sec">
                            <div class="sec-head">
                                <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg></span>
                                <h2>Quick Nav</h2>
                                <span class="rule"></span>
                            </div>
                            <div class="vlist">
                                @if($isEdit)
                                    <a href="{{ route('accounting.accounts.show', $account) }}" class="vitem">
                                        <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z"/><path d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg></span>
                                        View Account
                                    </a>
                                @endif
                                <a href="{{ route('accounting.journal-entries.create') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8c-2 0-4 .8-4 2s2 2 4 2 4-.8 4-2-2-2-4-2zm0 0V4m-4 6c0 1.2 1.8 2 4 2s4-.8 4-2m-8 0v6c0 1.2 1.8 2 4 2s4-.8 4-2v-6"/></svg></span>
                                    New Journal Entry
                                </a>
                                <a href="{{ route('accounting.accounts.index') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20V2H6.5A2.5 2.5 0 0 0 4 4.5v15z"/><path d="M4 19.5A2.5 2.5 0 0 0 6.5 22H20v-5"/></svg></span>
                                    Chart of Accounts
                                </a>
                                <a href="{{ route('accounting.general-ledger.index') }}" class="vitem">
                                    <span class="ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 6h16M4 12h16M4 18h16"/></svg></span>
                                    General Ledger
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </aside>
        </div>
    </form>
</div>

<script>
    const subTypes = {
        asset: ['current_asset', 'fixed_asset', 'other_asset'],
        liability: ['current_liability', 'long_term_liability'],
        equity: ['equity'],
        income: ['revenue', 'other_income'],
        expense: ['cost_of_goods_sold', 'operating_expense', 'other_expense']
    };

    const typeSelect = document.getElementById('type');
    const subTypeSelect = document.getElementById('sub_type');
    const oldType = '{{ $isEdit ? old("type", $account->type) : old("type") }}';
    const oldSubType = '{{ $isEdit ? old("sub_type", $account->sub_type) : old("sub_type") }}';

    function updateSubTypes() {
        const type = typeSelect.value;
        subTypeSelect.innerHTML = '<option value="">Select Sub Type</option>';

        if (subTypes[type]) {
            subTypes[type].forEach(function(st) {
                const option = document.createElement('option');
                option.value = st;
                option.textContent = st.split('_').map(function(w) { return w.charAt(0).toUpperCase() + w.slice(1); }).join(' ');
                if (st === oldSubType) option.selected = true;
                subTypeSelect.appendChild(option);
            });
        }
    }

    typeSelect.addEventListener('change', updateSubTypes);

    if (oldType) {
        typeSelect.value = oldType;
        updateSubTypes();
    } else {
        updateSubTypes();
    }
</script>
