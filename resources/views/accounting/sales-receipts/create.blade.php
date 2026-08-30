<x-app-layout>
    @include('accounting.sales-receipts._form', [
        'isEdit' => false,
        'customers' => $customers,
        'branches' => $branches,
        'incomeAccounts' => $incomeAccounts,
        'paymentMethods' => $paymentMethods,
        'mobileProviders' => $mobileProviders,
        'products' => $products,
        'defaultIncomeAccountId' => $defaultIncomeAccountId ?? ($incomeAccounts->first()?->id ?? ''),
        'selectedCustomerId' => request()->query('customer_id', ''),
        'preselectInvoiceId' => $preselectInvoiceId ?? null,
        'title' => __('Create Sales Receipt'),
    ])
</x-app-layout>
