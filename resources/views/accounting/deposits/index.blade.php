<x-app-layout>
    <x-slot name="header">{{ __('New Deposit') }}</x-slot>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-4 flex items-center justify-end">
                <x-button variant="primary" href="{{ route('accounting.deposits.create') }}">
                    {{ __('New Deposit') }}
                </x-button>
            </div>
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">{{ session('success') }}</div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-medium text-gray-900 mb-2">{{ __('Undeposited Funds Balance') }}</h3>
                <p class="text-3xl font-bold text-indigo-600">{{ format_money($undepositedBalance) }}</p>
            </div>

            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-medium text-gray-900">{{ __('Available for Deposit') }}</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Journal #</th>
                                <th>Description</th>
                                <th class="text-right">Amount</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($undepositedLines as $line)
                                <tr>
                                    <td class="text-ink-soft">{{ $line->journalEntry->date->format('M d, Y') }}</td>
                                    <td class="text-ink-soft">{{ $line->journalEntry->journal_number }}</td>
                                    <td class="text-ink-soft">{{ $line->memo ?? $line->journalEntry->memo ?? '—' }}</td>
                                    <td class="numeric">{{ format_money($line->debit) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-ink-soft">No undeposited items.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
