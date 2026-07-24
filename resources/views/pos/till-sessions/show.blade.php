<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Till Session #') . $session->id }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Session</p>
                        <p class="text-lg font-semibold text-gray-900">#{{ $session->id }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Terminal</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $session->terminal?->identifier ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Cashier</p>
                        <p class="text-lg font-semibold text-gray-900">{{ $session->user?->name ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Status</p>
                        @if($session->isOpen())
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Open</span>
                        @else
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">Closed</span>
                        @endif
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Opened At</p>
                        <p class="text-sm font-medium text-gray-900">{{ $session->opened_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Closed At</p>
                        <p class="text-sm font-medium text-gray-900">{{ $session->closed_at?->format('M d, Y H:i') ?? '—' }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Cash Summary') }}</h3>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-6">
                    <div>
                        <p class="text-sm text-gray-500">Opening Float</p>
                        <p class="text-lg font-semibold text-gray-900">${{ number_format($session->opening_float, 2) }}</p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Expected Cash</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $session->expected_cash !== null ? '$' . number_format($session->expected_cash, 2) : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Actual Cash Count</p>
                        <p class="text-lg font-semibold text-gray-900">
                            {{ $session->actual_cash_count !== null ? '$' . number_format($session->actual_cash_count, 2) : '—' }}
                        </p>
                    </div>
                    <div>
                        <p class="text-sm text-gray-500">Variance</p>
                        <p class="text-lg font-semibold
                            {{ ($session->variance ?? 0) > 0 ? 'text-green-600' : (($session->variance ?? 0) < 0 ? 'text-red-600' : 'text-gray-900') }}">
                            {{ $session->variance !== null ? (($session->variance >= 0 ? '+' : '') . '$' . number_format($session->variance, 2)) : '—' }}
                        </p>
                    </div>
                </div>
            </div>

            @if($session->journalEntry)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">
                        {{ __('Journal Entry') }}
                        <a href="{{ route('accounting.journal-entries.show', $session->journalEntry) }}" class="text-sm text-indigo-600 hover:text-indigo-900 ml-2">
                            #{{ $session->journalEntry->journal_number }}
                        </a>
                    </h3>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Account</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Debit</th>
                                    <th class="px-4 py-2 text-right text-xs font-medium text-gray-500 uppercase">Credit</th>
                                    <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Description</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                @foreach($session->journalEntry->lines as $line)
                                    <tr>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900">
                                            {{ $line->account?->code }} – {{ $line->account?->name }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-gray-900">
                                            {{ $line->debit > 0 ? '$' . number_format($line->debit, 2) : '' }}
                                        </td>
                                        <td class="px-4 py-2 whitespace-nowrap text-sm text-right text-gray-900">
                                            {{ $line->credit > 0 ? '$' . number_format($line->credit, 2) : '' }}
                                        </td>
                                        <td class="px-4 py-2 text-sm text-gray-500">{{ $line->description }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="mt-6">
                <a href="{{ route('pos.till-sessions.index') }}" class="text-sm text-indigo-600 hover:text-indigo-900">&larr; Back to Till Sessions</a>
            </div>
        </div>
    </div>
</x-app-layout>
