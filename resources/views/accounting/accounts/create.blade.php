<x-app-layout>
    <x-slot name="header">{{ __('Create Account') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                <form method="POST" action="{{ route('accounting.accounts.store') }}">
                    @csrf

                    <x-form.section number="01" :title="__('Account Details')" />

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="code" value="{{ __('Code') }}" />
                            <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required autofocus />
                            <x-input-error :messages="$errors->get('code')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="name" value="{{ __('Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="type" value="{{ __('Type') }}" />
                                <select id="type" name="type" class="input mt-1" required>
                                    <option value="">Select Type</option>
                                    <option value="asset" {{ old('type') === 'asset' ? 'selected' : '' }}>Asset</option>
                                    <option value="liability" {{ old('type') === 'liability' ? 'selected' : '' }}>Liability</option>
                                    <option value="equity" {{ old('type') === 'equity' ? 'selected' : '' }}>Equity</option>
                                    <option value="income" {{ old('type') === 'income' ? 'selected' : '' }}>Income</option>
                                    <option value="expense" {{ old('type') === 'expense' ? 'selected' : '' }}>Expense</option>
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
                                    <option value="{{ $parent->id }}" data-type="{{ $parent->type }}" {{ old('parent_id') == $parent->id ? 'selected' : '' }}>
                                        {{ $parent->code }} - {{ $parent->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('parent_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="input mt-1">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="opening_balance" value="{{ __('Opening Balance') }}" />
                                <x-text-input id="opening_balance" name="opening_balance" type="number" step="0.01" class="mt-1 block w-full" :value="old('opening_balance', '0.00')" />
                                <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="opening_balance_date" value="{{ __('Opening Balance Date') }}" />
                                <x-text-input id="opening_balance_date" name="opening_balance_date" type="date" class="mt-1 block w-full" :value="old('opening_balance_date')" />
                                <x-input-error :messages="$errors->get('opening_balance_date')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="currency" value="{{ __('Currency') }}" />
                            <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', 'USD')" maxlength="10" required />
                            <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.accounts.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Create Account') }}</x-primary-button>
                    </div>
                </form>
            </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Journal Entry'), 'route' => route('accounting.journal-entries.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Chart of Accounts'), 'route' => route('accounting.accounts.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 18.75a6 6 0 016-6m0 0a6 6 0 016 6M8.25 6.75a4.5 4.5 0 11-9 0 4.5 4.5 0 019 0zM17.25 18.75a6 6 0 00-3-5.25m3 5.25a6 6 0 01-3-5.25m3 5.25h5.25m-5.25 0v-5.25M12 8.25a4.5 4.5 0 100-9 4.5 4.5 0 000 9z\"/></svg>'],
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
        const parentSelect = document.getElementById('parent_id');
        const oldType = '{{ old("type") }}';
        const oldSubType = '{{ old("sub_type") }}';

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

        if (oldType) {
            typeSelect.value = oldType;
            updateSubTypes();
        }
    </script>
</x-app-layout>
