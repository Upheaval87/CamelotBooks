<x-app-layout>
    <x-list-header title="{{ __('Create New Run') }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.payroll-runs.create') }}">
                    {{ __('Create New Run') }}
                </x-button>
            </div>
            

            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Run Number</th>
                                <th>Period</th>
                                <th>Pay Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-right">Gross</th>
                                <th class="text-right">Net Pay</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @php
                                $statusColors = [
                                    'draft' => 'gray',
                                    'calculated' => 'yellow',
                                    'approved' => 'blue',
                                    'posted' => 'green',
                                    'partially_paid' => 'orange',
                                    'fully_paid' => 'emerald',
                                ];
                            @endphp
                            @forelse($runs as $run)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.payroll-runs.show', $run) }}" class="text-ink hover:text-gold">
                                            {{ $run->run_number }}
                                        </a>
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $run->period_label }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $run->pay_date?->format('M d, Y') ?? '—' }}
                                    </td>
                                    <td class="text-center">
                                        @php
                                            $color = $statusColors[$run->status] ?? 'gray';
                                        @endphp
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-{{ $color }}-100 text-{{ $color }}-800">
                                            {{ str_replace('_', ' ', ucfirst($run->status)) }}
                                        </span>
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($run->total_gross) }}
                                    </td>
                                    <td class="numeric">
                                        {{ format_money($run->total_net_pay) }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.payroll-runs.show', $run) }}" class="text-ink hover:text-gold">
                                            {{ __('View') }}
                                        </a>
                                        <a href="{{ route('accounting.payroll-runs.print-payslips', $run) }}" class="text-gray-600 hover:text-gray-900" target="_blank">
                                            {{ __('Print Payslips') }}
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No payroll runs found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if($runs->hasPages())
                    <div class="px-6 py-3 border-t border-gray-200">
                        {{ $runs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
