<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp
    @include('accounting.purchase-orders._form', [
        'isEdit' => false,
        'formAction' => route('accounting.purchase-orders.store'),
        'formMethod' => 'POST',
        'cancelRoute' => route('accounting.purchase-orders.index'),
        'title' => __('Create Purchase Order'),
        'subtitle' => __('Order goods and services from a vendor.'),
        'submitLabel' => __('Create Purchase Order'),
        'cs' => $cs,
        'vendors' => $vendors,
        'products' => $products,
        'accounts' => $accounts,
        'costCenters' => $costCenters,
        'branches' => $branches,
        'requisition' => $requisition,
        'selectedVendorId' => $selectedVendorId,
    ])
</x-app-layout>
