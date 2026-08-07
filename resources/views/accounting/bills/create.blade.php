<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    @include('accounting.bills._form', [
        'isEdit' => false,
        'formAction' => route('accounting.bills.store'),
        'formMethod' => 'POST',
        'cancelRoute' => route('accounting.bills.index'),
        'title' => __('Create Bill'),
        'cs' => $cs,
    ])
</x-app-layout>
