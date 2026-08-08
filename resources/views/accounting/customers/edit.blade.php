<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('accounting.customers._form', [
                'customer' => $customer,
                'isEdit' => true,
                'title' => __('Edit Customer') . ': ' . $customer->name,
                'subtitle' => __('Update this customer\'s contact details, payment terms and balances.'),
                'submitLabel' => __('Update Customer'),
                'cs' => $cs,
            ])
        </div>
    </div>
</x-app-layout>
