<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Account') }}: {{ $account->code }} - {{ $account->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.accounts.update', $account) }}">
                    @csrf
                    @method('PUT')

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
                            <select id="parent_id" name="parent_id" class="input mt-1">
                                <option value="">None (Top Level)</option>
                                @foreach($parentAccounts as $parent)
                                    <option value="{{ $parent->id }}" data-type="{{ $parent->type }}" {{ old('parent_id', $account->parent_id) == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->code }} - {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $account->description) }}</textarea>
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

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <x-button variant="ghost" href="{{ route('accounting.accounts.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Update Account') }}</x-primary-button>
                    </div>
                </form>
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
        const parentSelect = document.getElementById('parent_id');
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

            filterParentAccounts();
        }

        function filterParentAccounts() {
            const type = typeSelect.value;
            const options = parentSelect.querySelectorAll('option[data-type]');

            options.forEach(function(option) {
                if (!type || option.dataset.type === type) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                    if (option.selected) parentSelect.value = '';
                }
            });
        }

        typeSelect.addEventListener('change', updateSubTypes);
        updateSubTypes();
    </script>
</x-app-layout>
