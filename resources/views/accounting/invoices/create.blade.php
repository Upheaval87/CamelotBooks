<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('accounting.invoices._form', [
                'cs' => $cs,
                'copyQuote' => $copyQuote ?? null,
                'preselectCustomer' => $preselectCustomer ?? null,
                'copyQuotes' => $copyQuotes ?? collect(),
                'customers' => $customers,
                'products' => $products,
                'incomeAccounts' => $incomeAccounts,
                'costCenters' => $costCenters,
                'branches' => $branches ?? collect(),
                'currencies' => $currencies ?? collect(),
            ])
        </div>
    </div>
</x-app-layout>
