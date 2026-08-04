<x-app-layout>
    <x-slot name="header">{{ __('Edit Account') }}: {{ $account->code }} - {{ $account->name }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                <form method="POST" action="{{ route('accounting.accounts.update', $account) }}">
                    @csrf
                    @method('PUT')

                    <x-form.section number="01" :title="__('Account Details')" />

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="code" value="{{ __('Code') }}" />
                            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $account->code)" required autofocus />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="name" value="{{ __('Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $account->name)" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="type" value="{{ __('Type') }}" />
                                <select id="type" name="type" class="input mt-1" required>
                                    <option value="">Select Type</option>
                                    <option value="asset" {{ old('type', $account->type) === 'asset' ? 'selected' : '' }}>Asset</option>
                                    <option value="liability" {{ old('type', $account->type) === 'liability' ? 'selected' : '' }}>Liability</option>
                                    <option value="equity" {{ old('type', $account->type) === 'equity' ? 'selected' : '' }}>Equity</option>
                                    <option value="income" {{ old('type', $account->type) === 'income' ? 'selected' : '' }}>Income</option>
                                    <option value="expense" {{ old('type', $account->type) === 'expense' ? 'selected' : '' }}>Expense</option>
                                </select>
                                <x-input-error :messages="$errors->get('type')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="sub_type" value="{{ __('Sub Type') }}" />
                                <select id="sub_type" name="sub_type" class="input mt-1" required>
                                    <option value="">Select Sub Type</option>
                                </select>
                                <x-input-error :messages="$errors->get('sub_type')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="parent_id" value="{{ __('Parent Account (Optional)') }}" />
                            <x-scoped-search-field
                                name="parent_id"
                                entity="account"
                                search-url="{{ route('accounting.search.entity', ['entity' => 'account']) }}"
                                :value="old('parent_id', $account->parent_id)"
                                :label="old('parent_id', $account->parent_id) ? (($parentAccounts->firstWhere('id', (int) old('parent_id', $account->parent_id))) ? $parentAccounts->firstWhere('id', (int) old('parent_id', $account->parent_id))->code . ' - ' . $parentAccounts->firstWhere('id', (int) old('parent_id', $account->parent_id))->name : '') : ''"
                                placeholder="{{ __('None (Top Level)') }}"
                            />
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="input mt-1">{{ old('description', $account->description) }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="opening_balance" value="{{ __('Opening Balance') }}" />
                                <x-text-input id="opening_balance" name="opening_balance" type="number" step="0.01" class="mt-1 block w-full" :value="old('opening_balance', $account->opening_balance)" />
                                <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="opening_balance_date" value="{{ __('Opening Balance Date') }}" />
                                <x-text-input id="opening_balance_date" name="opening_balance_date" type="date" class="mt-1 block w-full" :value="old('opening_balance_date', $account->opening_balance_date?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('opening_balance_date')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="currency" value="{{ __('Currency') }}" />
                            <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $account->currency)" maxlength="10" required />
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.accounts.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Update Account') }}</x-primary-button>
                    </div>
                </form>
            </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Journal Entry'), 'route' => route('accounting.journal-entries.create'), 'icon' => 'document'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Chart of Accounts'), 'route' => route('accounting.accounts.index'), 'icon' => 'users'],
                    ]],
                ]" />
            </div>
        </div>
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
        const oldSubType = '{{ old("sub_type", $account->sub_type) }}';

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
        updateSubTypes();
    </script>
</x-app-layout>
