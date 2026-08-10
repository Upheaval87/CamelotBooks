<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('accounting.vendors._form', [
                'vendor' => $vendor,
                'isEdit' => true,
                'title' => __('Edit Vendor') . ': ' . $vendor->name,
                'subtitle' => __('Update this vendor\'s contact details, payment terms and balances.'),
                'submitLabel' => __('Update Vendor'),
                'cs' => $cs,
            ])
        </div>
    </div>
</x-app-layout>
