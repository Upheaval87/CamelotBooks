<x-app-layout>
    @include('accounting.sales-orders._form', [
        'isEdit' => true,
        'order' => $order,
        'customers' => $customers,
        'branches' => $branches,
        'costCenters' => $costCenters,
        'incomeAccounts' => $incomeAccounts,
        'products' => $products,
        'currencies' => $currencies,
        'defaultIncomeAccountId' => $defaultIncomeAccountId,
        'title' => __('Edit Sales Order') . ' ' . $order->sales_order_number,
        'submitLabel' => __('Save Changes'),
    ])
</x-app-layout>
