<x-app-layout>
    @include('accounting.sales-receipts._form', [
        'isEdit' => true,
        'salesReceipt' => $salesReceipt,
        'customers' => $customers,
        'branches' => $branches,
        'incomeAccounts' => $incomeAccounts,
        'paymentMethods' => $paymentMethods,
        'mobileProviders' => $mobileProviders,
        'products' => $products,
        'defaultIncomeAccountId' => $defaultIncomeAccountId ?? ($incomeAccounts->first()?->id ?? ''),
        'title' => __('Edit Sales Receipt') . ' ' . $salesReceipt->receipt_number,
        'subtitle' => __('Update the draft receipt before posting it to the ledger.'),
        'submitLabel' => __('Save Changes'),
    ])
</x-app-layout>
