<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            @include('accounting.invoices._form', [
                'invoice' => $invoice,
                'isEdit' => true,
                'title' => __('Edit Invoice') . ' #' . $invoice->invoice_number,
                'subtitle' => __('Update the details below and save to apply your changes.'),
                'submitLabel' => __('Update Invoice'),
                'cs' => $cs,
                'copyQuote' => $copyQuote ?? null,
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
