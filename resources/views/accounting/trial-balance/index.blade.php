<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Trial Balance') }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.trial-balance.export-csv', request()->query()) }}">{{ __('Export CSV') }}</x-button>
        <x-button variant="ghost" href="{{ route('accounting.trial-balance.export-pdf', request()->query()) }}" target="_blank">{{ __('Export PDF') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6 bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <form method="GET" action="{{ route('accounting.trial-balance.index') }}" class="flex items-end gap-4">
                    <div class="flex-1">
                        <x-input-label for="as_of_date" value="{{ __('As Of Date') }}" />
                        <x-text-input id="as_of_date" name="as_of_date" type="date" class="mt-1 block w-full" :value="$asOfDate" />
                    </div>
                    <div class="flex-1">
                        <x-input-label for="branch_id" value="{{ __('Branch (Optional)') }}" />
                        <select id="branch_id" name="branch_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            <option value="">All Branches</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ request('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.trial-balance.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Trial Balance as of {{ \Carbon\Carbon::parse($asOfDate)->format('M d, Y') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th class="text-right">Dr ({{ $cs }})</th>
                                <th class="text-right">Cr ({{ $cs }})</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($trialBalance as $row)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.general-ledger.account', $row['account']->id) }}?date_to={{ $asOfDate }}{{ request('branch_id') ? '&branch_id='.request('branch_id') : '' }}" class="text-ink hover:text-gold underline">{{ $row['account']->code }}</a>
                                    </td>
                                    <td>
                                        <a href="{{ route('accounting.general-ledger.account', $row['account']->id) }}?date_to={{ $asOfDate }}{{ request('branch_id') ? '&branch_id='.request('branch_id') : '' }}" class="text-ink hover:text-gold underline">{{ $row['account']->name }}</a>
                                    </td>
                                    <td class="numeric">
                                        {{ $row['debit_balance'] > 0 ? format_number($row['debit_balance']) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ $row['credit_balance'] > 0 ? format_number($row['credit_balance']) : '' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">
                                        No accounts with balances found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Totals</td>
                                <td class="numeric">
                                    {{ format_number($totalDebit) }}
                                </td>
                                <td class="numeric">
                                    {{ format_number($totalCredit) }}
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-right text-sm font-bold text-gray-900">Difference</td>
                                <td colspan="2" class="px-6 py-4 whitespace-nowrap text-sm font-bold {{ $difference == 0 ? 'text-green-600' : 'text-red-600' }} text-right">
                                    {{ format_number($difference) }}
                                    @if($difference == 0)
                                        <span class="ml-2 text-green-600">&#10003; Balanced</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
