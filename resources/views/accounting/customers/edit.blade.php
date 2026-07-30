<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Edit Customer') }}: {{ $customer->name }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="form-page">
                <div class="form-page-main">
                    <div class="card p-6">
                        <form method="POST" action="{{ route('accounting.customers.update', $customer) }}">
                            @csrf
                            @method('PUT')

                            <x-form.section number="01" :title="__('Customer Details')" />

                            <div class="grid grid-cols-2 gap-6">
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

                            <div class="mt-6">
                                <x-input-label for="billing_address" value="{{ __('Billing Address') }}" />
                                <textarea id="billing_address" name="billing_address" rows="2" class="input mt-1">{{ old('billing_address', $customer->billing_address) }}</textarea>
                                <x-input-error :messages="$errors->get('billing_address')" class="mt-2" />
                            </div>

                            <div class="mt-4">
                                <x-input-label for="shipping_address" value="{{ __('Shipping Address') }}" />
                                <textarea id="shipping_address" name="shipping_address" rows="2" class="input mt-1">{{ old('shipping_address', $customer->shipping_address) }}</textarea>
                                <x-input-error :messages="$errors->get('shipping_address')" class="mt-2" />
                            </div>

                            <div class="grid grid-cols-2 gap-6 mt-6">
                                <div>
                                    <x-input-label for="currency" value="{{ __('Currency') }}" />
                                    <x-text-input id="currency" name="currency" type="text" class="mt-1 block w-full" :value="old('currency', $customer->currency ?? $cs)" maxlength="10" />
                                    <x-input-error :messages="$errors->get('currency')" class="mt-2" />
                                </div>
                                <div>
                                    <x-input-label for="payment_terms" value="{{ __('Payment Terms') }}" />
                                    <select id="payment_terms" name="payment_terms" class="input mt-1">
                                        <option value="due_on_receipt" {{ old('payment_terms', $customer->payment_terms) === 'due_on_receipt' ? 'selected' : '' }}>Due on Receipt</option>
                                        <option value="net_15" {{ old('payment_terms', $customer->payment_terms) === 'net_15' ? 'selected' : '' }}>Net 15</option>
                                        <option value="net_30" {{ old('payment_terms', $customer->payment_terms) === 'net_30' ? 'selected' : '' }}>Net 30</option>
                                        <option value="net_60" {{ old('payment_terms', $customer->payment_terms) === 'net_60' ? 'selected' : '' }}>Net 60</option>
                                        <option value="net_90" {{ old('payment_terms', $customer->payment_terms) === 'net_90' ? 'selected' : '' }}>Net 90</option>
                                        <option value="custom" {{ old('payment_terms', $customer->payment_terms) === 'custom' ? 'selected' : '' }}>Custom</option>
                                    </select>
                                    <x-input-error :messages="$errors->get('payment_terms')" class="mt-2" />
                                </div>
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

                            <div class="grid grid-cols-2 gap-6 mt-6">
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

                            <div class="flex items-center justify-end mt-8 gap-3">
                                <x-button variant="ghost" href="{{ route('accounting.customers.index') }}">{{ __('Cancel') }}</x-button>
                                <x-primary-button>{{ __('Update Customer') }}</x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>

                <x-form.quick-actions :title="__('Quick Actions')" :groups="[
                    ['label' => __('Create'), 'links' => [
                        ['title' => __('New Invoice'), 'route' => route('accounting.invoices.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z\"/></svg>'],
                        ['title' => __('New Payment'), 'route' => route('accounting.customer-payments.create'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5z\"/></svg>'],
                    ]],
                    ['label' => __('View'), 'links' => [
                        ['title' => __('Customer List'), 'route' => route('accounting.customers.index'), 'icon' => '<svg class=\"w-4 h-4\" fill=\"none\" viewBox=\"0 0 24 24\" stroke=\"currentColor\" stroke-width=\"1.5\"><path stroke-linecap=\"round\" stroke-linejoin=\"round\" d=\"M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z\"/></svg>'],
                    ]],
                ]" />
            </div>
        </div>
    </div>
</x-app-layout>
