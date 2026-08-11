<x-app-layout>
    @include('accounting.sales-orders._form', [
        'isEdit' => false,
        'customers' => $customers,
        'branches' => $branches,
        'costCenters' => $costCenters,
        'incomeAccounts' => $incomeAccounts,
        'products' => $products,
        'currencies' => $currencies,
        'defaultIncomeAccountId' => $defaultIncomeAccountId,
        'title' => __('Create Sales Order'),
        'subtitle' => __('Capture customer orders & track fulfilment.'),
    ])
</x-app-layout>
