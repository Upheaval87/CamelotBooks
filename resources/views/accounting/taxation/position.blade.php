<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-page-head">
            <div>
                <h1>{{ __('Tax Position') }}</h1>
                <p class="sub">{{ __('What you owe per tax type right now — collected less recoverable less paid.') }}</p>
            </div>
            <div class="flex items-center gap-2">
                <button type="button" class="tx-btn tx-btn-ghost" onclick="window.txExportTable(this, 'tax-position')">Export</button>
            </div>
        </div>

        <div class="tx-card">
            <div class="tx-li-wrap">
                <table class="tx-table" style="min-width:860px;">
                    <thead>
                        <tr>
                            <th>{{ __('Tax Type') }}</th>
                            <th class="num">{{ __('Collected') }}</th>
                            <th class="num">{{ __('Recoverable') }}</th>
                            <th class="num">{{ __('Adjustments') }}</th>
                            <th class="num">{{ __('Paid') }}</th>
                            <th class="num">{{ __('Outstanding Balance') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($positions as $position)
                            <tr>
                                <td><span class="tx-tchip tx-t-vat">{{ $position['type_name'] }}</span></td>
                                <td class="num">{{ number_format($position['collected'], 2) }}</td>
                                <td class="num">{{ number_format($position['recoverable'], 2) }}</td>
                                <td class="num">{{ number_format($position['adjustments'], 2) }}</td>
                                <td class="num">{{ number_format($position['paid'], 2) }}</td>
                                <td class="num"><strong class="{{ $position['outstanding'] > 0.005 ? 'tx-neg' : 'tx-green' }}">{{ number_format($position['outstanding'], 2) }}</strong></td>
                            </tr>
                        @empty
                            <tr><td colspan="6" style="text-align:center;padding:36px;color:var(--muted);">{{ __('No tax activity recorded yet.') }}</td></tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td class="lbl">{{ __('Total') }}</td>
                            <td class="num lbl">{{ number_format($totals['collected'], 2) }}</td>
                            <td class="num lbl">{{ number_format($totals['recoverable'], 2) }}</td>
                            <td class="num lbl">{{ number_format($totals['adjustments'], 2) }}</td>
                            <td class="num lbl">{{ number_format($totals['paid'], 2) }}</td>
                            <td class="num lbl">{{ number_format($totals['outstanding'], 2) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div class="tx-note">
            {{ __('A positive outstanding balance is payable to the authority; negative means recoverable. Amounts exclude the currency symbol.') }}
        </div>
    </div>
</x-app-layout>
