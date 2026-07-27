<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Customer') }}: {{ $customer->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="POST" action="{{ route('accounting.customers.update', $customer) }}">
                    @csrf
                    @method('PUT')

                    <div class="space-y-6">
                        <div>
                            <x-input-label for="name" value="{{ __('Name') }}" />
                            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $customer->name)" required autofocus />
                            <x-input-error :messages="$errors->get('name')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="display_name" value="{{ __('Display Name') }}" />
                            <x-text-input id="display_name" name="display_name" type="text" class="mt-1 block w-full" :value="old('display_name', $customer->display_name)" />
                            <x-input-error :messages="$errors->get('display_name')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="email" value="{{ __('Email') }}" />
                                <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $customer->email)" />
                                <x-input-error :messages="$errors->get('email')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="phone" value="{{ __('Phone') }}" />
                                <x-text-input id="phone" name="phone" type="text" class="mt-1 block w-full" :value="old('phone', $customer->phone)" />
                                <x-input-error :messages="$errors->get('phone')" class="mt-2" />
                            </div>
                        </div>

                        <div>
                            <x-input-label for="billing_address" value="{{ __('Billing Address') }}" />
                            <textarea id="billing_address" name="billing_address" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('billing_address', $customer->billing_address) }}</textarea>
                            <x-input-error :messages="$errors->get('billing_address')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="shipping_address" value="{{ __('Shipping Address') }}" />
                            <textarea id="shipping_address" name="shipping_address" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                            <x-input-error :messages="$errors->get('shipping_address')" class="mt-2" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="currency" value="{{ __('Currency') }}" />
                                <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $customer->currency ?? $cs)" maxlength="10" />
                                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="payment_terms" value="{{ __('Payment Terms') }}" />
                                <select id="payment_terms" name="payment_terms" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="due_on_receipt" {{ old('payment_terms', $customer->payment_terms) === 'due_on_receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                    <option value="net_15" {{ old('payment_terms', $customer->payment_terms) === 'net_15' ? 'selected' : '' }}>Net 15</option>
                                    <option value="net_30" {{ old('payment_terms', $customer->payment_terms) === 'net_30' ? 'selected' : '' }}>Net 30</option>
                                    <option value="net_60" {{ old('payment_terms', $customer->payment_terms) === 'net_60' ? 'selected' : '' }}>Net 60</option>
                                    <option value="net_90" {{ old('payment_terms', $customer->payment_terms) === 'net_90' ? 'selected' : '' }}>Net 90</option>
                                    <option value="custom" {{ old('payment_terms', $customer->payment_terms) === 'custom' ? 'selected' : '' }}>Custom</option>
                                </select>
                                <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="payment_terms_days" value="{{ __('Payment Terms (Days)') }}" />
                                <x-text-input id="payment_terms_days" name="payment_terms_days" type="number" class="mt-1 block w-full" :value="old('payment_terms_days', $customer->payment_terms_days)" min="0" />
                                <x-input-error :messages="$errors->get('payment_terms_days')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="credit_limit" value="{{ __('Credit Limit') }}" />
                                <x-text-input id="credit_limit" name="credit_limit" type="number" step="0.01" class="mt-1 block w-full" :value="old('credit_limit', $customer->credit_limit)" min="0" />
                                <x-input-error :messages="$errors->get('credit_limit')" class="mt-2" />
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <x-input-label for="opening_balance" value="{{ __('Opening Balance') }} ({{ $cs }})" />
                                <x-text-input id="opening_balance" name="opening_balance" type="number" step="0.01" class="mt-1 block w-full" :value="old('opening_balance', $customer->opening_balance)" />
                                <x-input-error :messages="$errors->get('opening_balance')" class="mt-2" />
                            </div>

                            <div>
                                <x-input-label for="opening_balance_date" value="{{ __('Opening Balance Date') }}" />
                                <x-text-input id="opening_balance_date" name="opening_balance_date" type="date" class="mt-1 block w-full" :value="old('opening_balance_date', $customer->opening_balance_date?->format('Y-m-d'))" />
                                <x-input-error :messages="$errors->get('opening_balance_date')" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <div class="flex items-center justify-end mt-6 space-x-3">
                        <a href="{{ route('accounting.customers.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Cancel') }}
                        </a>
                        <x-primary-button>{{ __('Update Customer') }}</x-primary-button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
