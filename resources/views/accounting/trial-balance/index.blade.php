<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Trial Balance') }}</x-slot>

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
                        <x-scoped-search-field
                            name="branch_id"
                            entity="branch"
                            search-url="{{ route('accounting.search.entity', ['entity' => 'branch']) }}"
                            :value="request('branch_id')"
                            :label="request('branch_id') ? ($branches->firstWhere('id', (int) request('branch_id'))?->name ?? '') : ''"
                            placeholder="{{ __('All Branches') }}"
                        />
                    </div>
                    <div class="flex gap-2">
                        <x-primary-button type="submit">{{ __('Generate') }}</x-primary-button>
                        <a href="{{ route('accounting.trial-balance.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Clear') }}
                        </a>
                    </div>
                </form>
            </div>

            <x-report.card>
                <x-report.header
                    :company="$currentCompany->name ?? config('app.name')"
                    title="Trial Balance"
                    :range="'As of ' . \Carbon\Carbon::parse($asOfDate)->format('M d, Y')"
                />

                <x-report.toolbar
                    :csvRoute="route('accounting.trial-balance.export-csv', request()->query())"
                    :pdfRoute="route('accounting.trial-balance.export-pdf', request()->query())"
                />

                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Account Code</th>
                            <th>Account Name</th>
                            <th class="report-col-amt">Dr ({{ $cs }})</th>
                            <th class="report-col-amt">Cr ({{ $cs }})</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($trialBalance as $row)
                            @php $zero = $row['debit_balance'] == 0 && $row['credit_balance'] == 0; @endphp
                            <tr class="@if($zero) zero @endif">
                                <td><a href="{{ route('accounting.general-ledger.account', $row['account']->id) }}?date_to={{ $asOfDate }}{{ request('branch_id') ? '&branch_id='.request('branch_id') : '' }}" class="text-ink hover:text-gold underline"><span class="report-cell-code">{{ $row['account']->code }}</span></a></td>
                                <td><a href="{{ route('accounting.general-ledger.account', $row['account']->id) }}?date_to={{ $asOfDate }}{{ request('branch_id') ? '&branch_id='.request('branch_id') : '' }}" class="text-ink hover:text-gold underline">{{ $row['account']->name }}</a></td>
                                <td class="report-cell-amt">{{ $row['debit_balance'] > 0 ? format_number($row['debit_balance']) : '' }}</td>
                                <td class="report-cell-amt">{{ $row['credit_balance'] > 0 ? format_number($row['credit_balance']) : '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-ink-soft" style="padding:20px 14px">No accounts with balances found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr class="report-subtotal-row">
                            <td colspan="2" style="text-align:right">Totals</td>
                            <td class="report-cell-amt">{{ format_number($totalDebit) }}</td>
                            <td class="report-cell-amt">{{ format_number($totalCredit) }}</td>
                        </tr>
                        <tr class="report-total-row">
                            <td colspan="3" style="text-align:right">Difference</td>
                            <td class="report-cell-amt report-total-val {{ $difference == 0 ? '' : 'text-red-600' }}">
                                {{ format_number($difference) }}
                                @if($difference == 0)
                                    <span class="ml-1 text-sm">&#10003; Balanced</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </x-report.card>
        </div>
    </div>
</x-app-layout>
