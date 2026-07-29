<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Add Fixed Asset') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.fixed-assets.store') }}">
                    @csrf

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="category_id" value="{{ __('Asset Category') }}" />
                            <select id="category_id" name="category_id" class="input mt-1" required>
                                <option value="">Select Category</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                        {{ $cat->code }} - {{ $cat->name }}
                                    </option>
                                @endforeach
                            </select>
                            <x-input-error :messages="$errors->get('category_id')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="name" value="{{ __('Asset Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="description" value="{{ __('Description') }}" />
                            <textarea id="description" name="description" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description') }}</textarea>
                            <x-input-error :messages="$errors->get('description')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="serial_number" value="{{ __('Serial Number') }}" />
                            <x-text-input id="serial_number" name="serial_number" type="text" class="mt-1 block w-full" :value="old('serial_number')" />
                            <x-input-error :messages="$errors->get('serial_number')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="acquisition_date" value="{{ __('Acquisition Date') }}" />
                                <x-text-input id="acquisition_date" name="acquisition_date" type="date" class="mt-1 block w-full" :value="old('acquisition_date')" required />
                                <x-input-error :messages="$errors->get('acquisition_date')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="in_service_date" value="{{ __('In-Service Date') }}" />
                                <x-text-input id="in_service_date" name="in_service_date" type="date" class="mt-1 block w-full" :value="old('in_service_date')" required />
                                <x-input-error :messages="$errors->get('in_service_date')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="acquisition_cost" value="{{ __('Acquisition Cost') }}" />
                                <x-text-input id="acquisition_cost" name="acquisition_cost" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('acquisition_cost')" required />
                                <x-input-error :messages="$errors->get('acquisition_cost')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="residual_value" value="{{ __('Residual Value') }}" />
                                <x-text-input id="residual_value" name="residual_value" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('residual_value', '0.00')" />
                                <x-input-error :messages="$errors->get('residual_value')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="useful_life" value="{{ __('Useful Life (Financial, Years)') }}" />
                                <x-text-input id="useful_life" name="useful_life" type="number" min="1" class="mt-1 block w-full" :value="old('useful_life')" />
                                <x-input-error :messages="$errors->get('useful_life')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="depreciation_method_financial" value="{{ __('Financial Depreciation Method') }}" />
                                <select id="depreciation_method_financial" name="depreciation_method_financial" class="input mt-1">
                                    <option value="">Select Method</option>
                                    <option value="straight_line" {{ old('depreciation_method_financial') === 'straight_line' ? 'selected' : '' }}>Straight Line</option>
                                    <option value="declining_balance" {{ old('depreciation_method_financial') === 'declining_balance' ? 'selected' : '' }}>Declining Balance</option>
                                    <option value="units_of_production" {{ old('depreciation_method_financial') === 'units_of_production' ? 'selected' : '' }}>Units of Production</option>
                                    <option value="sum_of_years" {{ old('depreciation_method_financial') === 'sum_of_years' ? 'selected' : '' }}>Sum of Years' Digits</option>
                                </select>
                                <x-input-error :messages="$errors->get('depreciation_method_financial')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="useful_life_tax" value="{{ __('Useful Life (Tax, Years)') }}" />
                                <x-text-input id="useful_life_tax" name="useful_life_tax" type="number" min="1" class="mt-1 block w-full" :value="old('useful_life_tax')" />
                                <x-input-error :messages="$errors->get('useful_life_tax')" class="mt-2" />
                            </div>
                            <div>
                                <x-input-label for="depreciation_method_tax" value="{{ __('Tax Depreciation Method') }}" />
                                <select id="depreciation_method_tax" name="depreciation_method_tax" class="input mt-1">
                                    <option value="">Select Method</option>
                                    <option value="straight_line" {{ old('depreciation_method_tax') === 'straight_line' ? 'selected' : '' }}>Straight Line</option>
                                    <option value="declining_balance" {{ old('depreciation_method_tax') === 'declining_balance' ? 'selected' : '' }}>Declining Balance</option>
                                    <option value="capital_allowance" {{ old('depreciation_method_tax') === 'capital_allowance' ? 'selected' : '' }}>Capital Allowance</option>
                                </select>
                                <x-input-error :messages="$errors->get('depreciation_method_tax')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="residual_value_tax" value="{{ __('Residual Value (Tax)') }}" />
                                <x-text-input id="residual_value_tax" name="residual_value_tax" type="number" step="0.01" min="0" class="mt-1 block w-full" :value="old('residual_value_tax', '0.00')" />
                                <x-input-error :messages="$errors->get('residual_value_tax')" class="mt-2" />
                            </div>
                            <div>
                                <div class="flex items-center mt-6">
                                    <input type="checkbox" id="is_revaluation_enabled" name="is_revaluation_enabled" value="1" {{ old('is_revaluation_enabled') ? 'checked' : '' }} class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    <x-input-label for="is_revaluation_enabled" value="{{ __('Enable Revaluation') }}" class="ml-2" />
                                </div>
                            </div>
                        </div>

                        <div class="border-t pt-6">
                            <div class="form-section-label">3 · OPTIONAL DETAILS</div>
                            <div class="space-y-4">
                                <div>
                                    <x-input-label for="branch_id" value="{{ __('Branch') }}" />
                                    <select id="branch_id" name="branch_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($branches ?? [] as $branch)
                                            <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                                {{ $branch->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('branch_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="cost_center_id" value="{{ __('Cost Center') }}" />
                                    <select id="cost_center_id" name="cost_center_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($costCenters ?? [] as $cc)
                                            <option value="{{ $cc->id }}" {{ old('cost_center_id') == $cc->id ? 'selected' : '' }}>
                                                {{ $cc->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('cost_center_id')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="vendor_id" value="{{ __('Vendor') }}" />
                                    <select id="vendor_id" name="vendor_id" class="input mt-1">
                                        <option value="">None</option>
                                        @foreach($vendors ?? [] as $vendor)
                                            <option value="{{ $vendor->id }}" {{ old('vendor_id') == $vendor->id ? 'selected' : '' }}>
                                                {{ $vendor->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error :messages="$errors->get('vendor_id')" class="mt-2" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <x-button variant="ghost" href="{{ route('accounting.fixed-assets.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Create Asset') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
