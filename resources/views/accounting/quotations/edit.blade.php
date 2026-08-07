<x-app-layout>
    @include('accounting.quotations._form', [
        'isEdit' => true,
        'quotation' => $quotation,
        'customers' => $customers,
        'branches' => $branches,
        'costCenters' => $costCenters,
        'incomeAccounts' => $incomeAccounts,
        'products' => $products,
        'currencies' => $currencies,
        'defaultIncomeAccountId' => $defaultIncomeAccountId,
        'title' => __('Edit Quotation') . ' ' . $quotation->quotation_number,
        'subtitle' => __('Update the draft quotation before sending it to the customer.'),
        'submitLabel' => __('Save Changes'),
    ])
</x-app-layout>
