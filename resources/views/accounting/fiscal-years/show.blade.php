<x-app-layout>
    <x-slot name="header">{{ __('Fiscal Year: ') }} {{ $fiscalYear->label }}</x-slot>

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="ghost" href="{{ route('accounting.fiscal-years.index') }}">{{ __('Back') }}</x-button>
        @if($fiscalYear->isOpen() && $fiscalYear->allPeriodsClosedOrLocked())
            <form method="POST" action="{{ route('accounting.fiscal-years.close', $fiscalYear) }}" onsubmit="return confirm('Are you sure you want to close fiscal year {{ $fiscalYear->label }}? This will post a closing journal entry to Retained Earnings.');">
                @csrf
                <x-button variant="primary" type="submit">{{ __('Run Year-End Close') }}</x-button>
            </form>
        @endif
        @if($fiscalYear->isClosed())
            <x-button variant="ghost" onclick="document.getElementById('reopen-modal').classList.remove('hidden')">{{ __('Reopen Year') }}</x-button>
        @endif
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            {{-- FY Summary --}}
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                    <div>
                        <div class="text-sm text-gray-500">Label</div>
                        <div class="text-lg font-semibold">{{ $fiscalYear->label }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Period</div>
                        <div class="text-lg font-semibold">{{ $fiscalYear->start_date->format('M d, Y') }} — {{ $fiscalYear->end_date->format('M d, Y') }}</div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Status</div>
                        <div class="mt-1">
                            @if($fiscalYear->isOpen())
                                <span class="status-pill positive">Open</span>
                            @elseif($fiscalYear->isClosed())
                                <span class="status-pill neutral">Closed</span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="text-sm text-gray-500">Closed By</div>
                        <div>{{ $fiscalYear->closedByUser->name ?? '—' }}</div>
                    </div>
                </div>
                @if($fiscalYear->closingEntry)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-500">Closing Entry: <span class="font-mono text-gray-900">{{ $fiscalYear->closingEntry->journal_number }}</span></div>
                    </div>
                @endif
                @if($fiscalYear->reopened_at)
                    <div class="mt-2">
                        <div class="text-sm text-red-600">Reopened {{ $fiscalYear->reopened_at->format('M d, Y H:i') }} by {{ $fiscalYear->reopenedByUser->name }} — Reason: {{ $fiscalYear->reopen_reason }}</div>
                    </div>
                @endif
                @if($fiscalYear->isOpen() && !$fiscalYear->allPeriodsClosedOrLocked())
                    <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-md p-3 text-sm text-yellow-700">
                        All periods must be closed or locked before running year-end close. Close/lock remaining periods in the <a href="{{ route('accounting.periods.index') }}" class="underline">Accounting Periods</a> page.
                    </div>
                @endif
            </div>

            {{-- Periods --}}
            <div class="datasheet-wrap">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Periods</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th class="text-center">Status</th>
                                <th>Closed By</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($fiscalYear->periods->sortBy('start_date') as $period)
                                <tr class="hover:bg-gray-50">
                                    <td>{{ $period->label }}</td>
                                    <td>{{ $period->start_date->format('M d, Y') }}</td>
                                    <td>{{ $period->end_date->format('M d, Y') }}</td>
                                    <td class="text-center">
                                        @if($period->isOpen())
                                            <span class="status-pill positive">Open</span>
                                        @elseif($period->isClosed())
                                            <span class="status-pill neutral">Closed</span>
                                        @elseif($period->isLocked())
                                            <span class="status-pill negative">Locked</span>
                                        @endif
                                    </td>
                                    <td class="text-ink-soft">{{ $period->closedByUser->name ?? '—' }}</td>
                                    <td class="text-right">
                                        @if($period->isOpen())
                                            <form method="POST" action="{{ route('accounting.periods.close', $period) }}" class="inline" onsubmit="return confirm('Close this period?');">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">Close</button>
                                            </form>
                                        @endif
                                        @if($period->isClosed())
                                            <form method="POST" action="{{ route('accounting.periods.lock', $period) }}" class="inline" onsubmit="return confirm('Lock this period?');">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-900">Lock</button>
                                            </form>
                                            <form method="POST" action="{{ route('accounting.periods.reopen', $period) }}" class="inline" onsubmit="return confirm('Reopen this period?');">
                                                @csrf
                                                <button type="submit" class="text-ink hover:text-gold">Reopen</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Reopen Modal --}}
    @if($fiscalYear->isClosed())
    <div id="reopen-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-red-700">Reopen Fiscal Year</h3>
                <button onclick="document.getElementById('reopen-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <p class="text-sm text-gray-600 mb-4">This will reverse the closing journal entry and reopen all periods in this fiscal year. This is an audited action.</p>
            <form method="POST" action="{{ route('accounting.fiscal-years.reopen', $fiscalYear) }}">
                @csrf
                @method('PATCH')
                <div class="mb-6">
                    <x-input-label for="reason" value="{{ __('Reason for reopening (required, min 10 chars)') }}" />
                    <textarea id="reason" name="reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required minlength="10"></textarea>
                    <x-input-error :messages="$errors->get('reason')" class="mt-2" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('reopen-modal').classList.add('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </button>
                    <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Reopen Year
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif
</x-app-layout>
