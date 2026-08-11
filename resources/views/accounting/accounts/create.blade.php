<x-app-layout>
    <x-slot name="header">{{ __('Chart of Accounts') }}</x-slot>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">
        @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

        @include('accounting.accounts._form', [
            'cs' => $cs,
            'account' => null,
            'isEdit' => false,
            'parentAccounts' => $parentAccounts,
        ])
    </div>
</x-app-layout>
