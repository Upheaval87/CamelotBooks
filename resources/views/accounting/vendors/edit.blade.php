<x-app-layout>
    <x-slot name="header">{{ __('Edit Vendor') }}: {{ $vendor->name }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="card p-6">
                <form method="POST" action="{{ route('accounting.vendors.update', $vendor) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" value="{{ __('Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $vendor->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="display_name" value="{{ __('Display Name') }}" />
                            <x-text-input id="display_name" name="display_name" type="text" class="mt-1 block w-full" :value="old('display_name', $vendor->display_name)" />
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="email" value="{{ __('Email') }}" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $vendor->email)" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="phone" value="{{ __('Phone') }}" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $vendor->phone)" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="billing_address" value="{{ __('Billing Address') }}" />
                            <textarea id="billing_address" name="billing_address" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('billing_address', $vendor->billing_address) }}</textarea>
                            <x-input-error :messages="$errors->get('billing_address')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="remit_to_address" value="{{ __('Remit To Address') }}" />
                            <textarea id="remit_to_address" name="remit_to_address" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('remit_to_address', $vendor->remit_to_address) }}</textarea>
                            <x-input-error :messages="$errors->get('remit_to_address')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="currency" value="{{ __('Currency') }}" />
                                <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $vendor->currency)" maxlength="10" />
                                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="payment_terms" value="{{ __('Payment Terms') }}" />
                                <select id="payment_terms" name="payment_terms" class="input mt-1">
                                    <option value="due_on_receipt" {{ old('payment_terms', $vendor->payment_terms) === 'due_on_receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                    <option value="net_15" {{ old('payment_terms', $vendor->payment_terms) === 'net_15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="net_30" {{ old('payment_terms', $vendor->payment_terms) === 'net_30' ? 'selected' : '' }}>Net 30</option>
                                    <option value="net_60" {{ old('payment_terms', $vendor->payment_terms) === 'net_60' ? 'selected' : '' }}>Net 60</option>
                                    <option value="net_90" {{ old('payment_terms', $vendor->payment_terms) === 'net_90' ? 'selected' : '' }}>Net 90</option>
                                    <option value="custom" {{ old('payment_terms', $vendor->payment_terms) === 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="payment_terms_days" value="{{ __('Payment Terms (Days)') }}" />
                            <x-text-input id="payment_terms_days" name="payment_terms_days" type="number" class="mt-1 block w-full" :value="old('payment_terms_days', $vendor->payment_terms_days)" min="0" />
                            <x-input-error :messages="$errors->get('payment_terms_days')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="opening_balance" value="{{ __('Opening Balance') }}" />
                                <x-text-input id="opening_balance" name="opening_balance" type="number" step="0.01" class="mt-1 block w-full" :value="old('opening_balance', $vendor->opening_balance)" />
                                <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="opening_balance_date" value="{{ __('Opening Balance Date') }}" />
                                <x-text-input id="opening_balance_date" name="opening_balance_date" type="date" class="mt-1 block w-full" :value="old('opening_balance_date', $vendor->opening_balance_date?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('opening_balance_date')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <x-button variant="ghost" href="{{ route('accounting.vendors.index') }}">{{ __('Cancel') }}</x-button>
                        <x-primary-button>{{ __('Update Vendor') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
