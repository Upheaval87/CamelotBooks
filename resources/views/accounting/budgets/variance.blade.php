<x-app-layout>
    <x-list-header title="{{ __('Budget Variance Report') }}: {{ $budget->name }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.budgets.show', $budget) }}">{{ __('Back to Budget') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-3 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Budget') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $budget->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Fiscal Year') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $budget->fiscalYear->label ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Period') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $date_from }} to {{ $date_to }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Variance Details') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th class="text-right">Budget</th>
                                <th class="text-right">Actual</th>
                                <th class="text-right">Variance</th>
                                <th class="text-right">Variance %</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($lines as $line)
                                <tr>
                                    <td>
                                        {{ $line['account']->code }} - {{ $line['account']->name }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($line['budget']) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($line['actual']) }}
                                    </td>
                                    <td class="figure px-6 py-4 whitespace-nowrap text-sm text-right font-semibold {{ $line['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $line['variance'] >= 0 ? '+' : '' }}{{ format_money($line['variance']) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $line['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $line['variance_pct'] !== null ? number_format($line['variance_pct'], 1) . '%' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-ink-soft">
                                        No accounts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">Total</td>
                                <td class="figure px-6 py-4 text-sm font-semibold text-gray-900 text-right">{{ format_money($total_budget) }}</td>
                                <td class="figure px-6 py-4 text-sm font-semibold text-gray-900 text-right">{{ format_money($total_actual) }}</td>
                                <td class="figure px-6 py-4 text-sm font-bold text-right {{ $total_variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $total_variance >= 0 ? '+' : '' }}{{ format_money($total_variance) }}
                                </td>
                                <td class="px-6 py-4 text-sm text-right {{ $total_variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $total_budget != 0 ? number_format(($total_variance / $total_budget) * 100, 1) . '%' : '—' }}
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
