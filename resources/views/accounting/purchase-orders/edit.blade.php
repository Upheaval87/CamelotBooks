<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp
    @include('accounting.purchase-orders._form', [
        'isEdit' => true,
        'formAction' => route('accounting.purchase-orders.update', $order),
        'formMethod' => 'PUT',
        'cancelRoute' => route('accounting.purchase-orders.show', $order),
        'title' => __('Edit Purchase Order') . ' #' . $order->po_number,
        'subtitle' => __('Update the order details and line items.'),
        'submitLabel' => __('Update Purchase Order'),
        'cs' => $cs,
        'vendors' => $vendors,
        'products' => $products,
        'accounts' => $accounts,
        'costCenters' => $costCenters,
        'branches' => $branches,
        'order' => $order,
        'existingLines' => $order->lines,
    ])
</x-app-layout>
