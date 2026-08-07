<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    @include('accounting.bills._form', [
        'isEdit' => true,
        'bill' => $bill,
        'formAction' => route('accounting.bills.update', $bill),
        'formMethod' => 'PUT',
        'cancelRoute' => route('accounting.bills.show', $bill),
        'title' => __('Edit Bill') . ' #' . $bill->bill_number,
        'cs' => $cs,
    ])
</x-app-layout>
