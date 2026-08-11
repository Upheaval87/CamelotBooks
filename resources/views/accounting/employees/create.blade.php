<x-app-layout>
    <x-slot name="header">{{ __('Employees') }}</x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

        @include('accounting.employees._form', [
            'cs' => $cs,
            'employee' => null,
            'isEdit' => false,
            'branches' => $branches,
            'costCenters' => $costCenters,
        ])
    </div>
</x-app-layout>
