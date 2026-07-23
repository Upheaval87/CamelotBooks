<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Budget Variance Report') }}: {{ $budget->name }}
            </h2>
            <div class="flex items-center space-x-3">
                <a href="{{ route('accounting.budgets.show', $budget) }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                    {{ __('Back to Budget') }}
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
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
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Account</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Budget</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Actual</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Variance %</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($lines as $line)
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                        {{ $line['account']->code }} - {{ $line['account']->name }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($line['budget'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 text-right">
                                        {{ number_format($line['actual'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold {{ $line['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $line['variance'] >= 0 ? '+' : '' }}{{ number_format($line['variance'], 2) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-right {{ $line['variance'] >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                        {{ $line['variance_pct'] !== null ? number_format($line['variance_pct'], 1) . '%' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-4 text-center text-sm text-gray-500">
                                        No accounts found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900">Total</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">{{ number_format($total_budget, 2) }}</td>
                                <td class="px-6 py-4 text-sm font-semibold text-gray-900 text-right">{{ number_format($total_actual, 2) }}</td>
                                <td class="px-6 py-4 text-sm font-bold text-right {{ $total_variance >= 0 ? 'text-green-600' : 'text-red-600' }}">
                                    {{ $total_variance >= 0 ? '+' : '' }}{{ number_format($total_variance, 2) }}
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
