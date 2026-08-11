<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('accounting.products._form', [
                'cs' => $cs,
            ])
        </div>
    </div>
</x-app-layout>
