<x-app-layout>
    <x-slot name="header">{{ __('Create Asset Category') }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                <form method="POST" action="{{ route('accounting.asset-categories.store') }}">
                    @csrf

                    <x-form.section number="01" :title="__('Category Details')" />

                    <div class="space-y-6">
                        <div class="grid grid-cols-2 gap-4">
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
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="input mt-1">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <x-form.section number="02" :title="__('Financial Depreciation')" />

                        <div class="mt-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="depreciation_method_financial" value="{{ __('Depreciation Method') }}" />
                                    <select id="depreciation_method_financial" name="depreciation_method_financial" class="input mt-1" required>
                                        <option value="">Select Method</option>
                                        <option value="straight_line" {{ old('depreciation_method_financial') === 'straight_line' ? 'selected' : '' }}>Straight Line</option>
                                        <option value="declining_balance" {{ old('depreciation_method_financial') === 'declining_balance' ? 'selected' : '' }}>Declining Balance</option>
                                        <option value="units_of_production" {{ old('depreciation_method_financial') === 'units_of_production' ? 'selected' : '' }}>Units of Production</option>
                                        <option value="sum_of_years" {{ old('depreciation_method_financial') === 'sum_of_years' ? 'selected' : '' }}>Sum of Years' Digits</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('depreciation_method_financial')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="useful_life_financial" value="{{ __('Useful Life (Years)') }}" />
                                    <x-text-input id="useful_life_financial" name="useful_life_financial" type="number" min="1" class="mt-1 block w-full" :value="old('useful_life_financial')" required />
                                    <x-input-error :messages="$errors->get('useful_life_financial')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="residual_value_type_financial" value="{{ __('Residual Value Type') }}" />
                                    <select id="residual_value_type_financial" name="residual_value_type_financial" class="input mt-1">
                                        <option value="percentage" {{ old('residual_value_type_financial') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="fixed" {{ old('residual_value_type_financial', 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('residual_value_type_financial')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="residual_value_financial" value="{{ __('Residual Value') }}" />
                                    <x-text-input id="residual_value_financial" name="residual_value_financial" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('residual_value_financial', '0.00')" />
                                    <x-input-error :messages="$errors->get('residual_value_financial')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <x-form.section number="03" :title="__('Tax Depreciation')" />

                        <div class="mt-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <x-input-label for="depreciation_method_tax" value="{{ __('Depreciation Method') }}" />
                                    <select id="depreciation_method_tax" name="depreciation_method_tax" class="input mt-1" required>
                                        <option value="">Select Method</option>
                                        <option value="straight_line" {{ old('depreciation_method_tax') === 'straight_line' ? 'selected' : '' }}>Straight Line</option>
                                        <option value="declining_balance" {{ old('depreciation_method_tax') === 'declining_balance' ? 'selected' : '' }}>Declining Balance</option>
                                        <option value="capital_allowance" {{ old('depreciation_method_tax') === 'capital_allowance' ? 'selected' : '' }}>Capital Allowance</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('depreciation_method_tax')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="useful_life_tax" value="{{ __('Useful Life (Years)') }}" />
                                    <x-text-input id="useful_life_tax" name="useful_life_tax" type="number" min="1" class="mt-1 block w-full" :value="old('useful_life_tax')" required />
                                    <x-input-error :messages="$errors->get('useful_life_tax')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="residual_value_type_tax" value="{{ __('Residual Value Type') }}" />
                                    <select id="residual_value_type_tax" name="residual_value_type_tax" class="input mt-1">
                                        <option value="percentage" {{ old('residual_value_type_tax') === 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="fixed" {{ old('residual_value_type_tax', 'fixed') === 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('residual_value_type_tax')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="residual_value_tax" value="{{ __('Residual Value') }}" />
                                    <x-text-input id="residual_value_tax" name="residual_value_tax" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('residual_value_tax', '0.00')" />
                                    <x-input-error :messages="$errors->get('residual_value_tax')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="depreciation_rate_tax" value="{{ __('Depreciation Rate (%)') }}" />
                                    <x-text-input id="depreciation_rate_tax" name="depreciation_rate_tax" type="number" step="0.01" min="0" max="100" class="mt-1 block w-full" :value="old('depreciation_rate_tax')" />
                                    <x-input-error :messages="$errors->get('depreciation_rate_tax')" class="mt-2" />
                                </div>
                            </div>
                        </div>

                        <x-form.section number="04" :title="__('Revaluation')" />

                        <div class="mt-4">
                            <div class="flex items-center">
                                <input type="checkbox" id="is_revaluation_enabled" name="is_revaluation_enabled" value="1" {{ old('is_revaluation_enabled') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                <x-input-label for="is_revaluation_enabled" value="{{ __('Enable Revaluation') }}" class="ml-2" />
                            </div>
                        </div>

                        <x-form.section number="05" :title="__('GL Accounts')" />

                        <div class="mt-4">
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="asset_account_id" value="{{ __('Asset Account') }}" />
                                    <select id="asset_account_id" name="asset_account_id" class="input mt-1">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('asset_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('asset_account_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="accumulated_depreciation_account_id" value="{{ __('Accumulated Depreciation Account') }}" />
                                    <select id="accumulated_depreciation_account_id" name="accumulated_depreciation_account_id" class="input mt-1">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('accumulated_depreciation_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('accumulated_depreciation_account_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="depreciation_expense_account_id" value="{{ __('Depreciation Expense Account') }}" />
                                    <select id="depreciation_expense_account_id" name="depreciation_expense_account_id" class="input mt-1">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('depreciation_expense_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('depreciation_expense_account_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="accumulated_impairment_account_id" value="{{ __('Accumulated Impairment Account') }}" />
                                    <select id="accumulated_impairment_account_id" name="accumulated_impairment_account_id" class="input mt-1">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('accumulated_impairment_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('accumulated_impairment_account_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="impairment_loss_account_id" value="{{ __('Impairment Loss Account') }}" />
                                    <select id="impairment_loss_account_id" name="impairment_loss_account_id" class="input mt-1">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('impairment_loss_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('impairment_loss_account_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="disposal_gain_loss_account_id" value="{{ __('Disposal Gain/Loss Account') }}" />
                                    <select id="disposal_gain_loss_account_id" name="disposal_gain_loss_account_id" class="input mt-1">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('disposal_gain_loss_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('disposal_gain_loss_account_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="revaluation_surplus_account_id" value="{{ __('Revaluation Surplus Account') }}" />
                                    <select id="revaluation_surplus_account_id" name="revaluation_surplus_account_id" class="input mt-1">
                                        <option value="">Select Account</option>
                                        @foreach($accounts as $account)
                                            <option value="{{ $account->id }}" {{ old('revaluation_surplus_account_id') == $account->id ? 'selected' : '' }}>
                                                {{ $account->code }} - {{ $account->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('revaluation_surplus_account_id')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-8 gap-3">
                        <x-button variant="ghost" href="{{ route('accounting.asset-categories.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Create Category') }}</x-primary-button>
                    </div>
                </form>
            </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Fixed Assets'), 'route' => route('accounting.fixed-assets.index'), 'icon' => 'grid'],
                        ['title' => __('Asset Categories List'), 'route' => route('accounting.asset-categories.index'), 'icon' => 'bars3'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
