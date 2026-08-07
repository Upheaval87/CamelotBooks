<x-app-layout>
    @include('accounting.quotations._form', [
        'isEdit' => false,
        'customers' => $customers,
        'branches' => $branches,
        'costCenters' => $costCenters,
        'incomeAccounts' => $incomeAccounts,
        'products' => $products,
        'currencies' => $currencies,
        'defaultIncomeAccountId' => $defaultIncomeAccountId,
        'title' => __('Create Quotation'),
    ])
</x-app-layout>
