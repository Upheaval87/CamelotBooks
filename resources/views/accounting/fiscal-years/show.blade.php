<x-app-layout>
    <x-list-header title="{{ __('Fiscal Year: ') }} {{ $fiscalYear->label }}" />

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Actions') }}</span>
                    @if($fiscalYear->isOpen() && $fiscalYear->allPeriodsClosedOrLocked())
                        @can('fiscal-years.close')
                            <form method="POST" action="{{ route('accounting.fiscal-years.close', $fiscalYear) }}" class="inline" onsubmit="return confirm('Are you sure you want to close fiscal year {{ $fiscalYear->label }}?');">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Run Year-End Close') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if($fiscalYear->isClosed())
                        <button type="button" onclick="document.getElementById('reopen-modal').classList.remove('hidden')" class="tr-item">{{ __('Reopen Year') }}</button>
                    @endif
                </div>
                <div class="tr-spacer"></div>
                <a href="{{ route('accounting.fiscal-years.index') }}" class="tr-item">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                    {{ __('Back') }}
                </a>
            </x-record-toolbar>

            <div class="detail-page">
                <div class="detail-page-main">

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
            <div class="card p-6">
                <div class="detail-grid">
                    <x-detail-field label="{{ __('Label') }}" strong>{{ $fiscalYear->label }}</x-detail-field>
                    <x-detail-field label="{{ __('Period') }}" strong>{{ $fiscalYear->start_date->format('M d, Y') }} — {{ $fiscalYear->end_date->format('M d, Y') }}</x-detail-field>
                    <x-detail-field label="{{ __('Status') }}" noBorder>
                        @if($fiscalYear->isOpen())
                            <span class="status-pill positive">{{ __('Open') }}</span>
                        @elseif($fiscalYear->isClosed())
                            <span class="status-pill neutral">{{ __('Closed') }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field label="{{ __('Closed By') }}">{{ $fiscalYear->closedByUser->name ?? '—' }}</x-detail-field>
                </div>
                @if($fiscalYear->closingEntry)
                    <div class="mt-4 pt-4 border-t border-gray-200">
                        <div class="text-sm text-gray-500">{{ __('Closing Entry') }}: <span class="font-sans text-gray-900">{{ $fiscalYear->closingEntry->journal_number }}</span></div>
                    </div>
                @endif
                @if($fiscalYear->reopened_at)
                    <div class="mt-2">
                        <div class="text-sm text-red-600">{{ __('Reopened') }} {{ $fiscalYear->reopened_at->format('M d, Y H:i') }} {{ __('by') }} {{ $fiscalYear->reopenedByUser->name }} — {{ __('Reason') }}: {{ $fiscalYear->reopen_reason }}</div>
                    </div>
                @endif
                @if($fiscalYear->isOpen() && !$fiscalYear->allPeriodsClosedOrLocked())
                    <div class="mt-4 bg-yellow-50 border border-yellow-200 rounded-md p-3 text-sm text-yellow-700">
                        {{ __('All periods must be closed or locked before running year-end close.') }}
                    </div>
                @endif
            </div>

            {{-- Periods --}}
            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Periods') }}</p>
                <div class="overflow-x-auto">
                    <table class="record-datasheet">
                        <thead>
                            <tr>
                                <th>{{ __('Label') }}</th>
                                <th>{{ __('Start Date') }}</th>
                                <th>{{ __('End Date') }}</th>
                                <th class="text-center">{{ __('Status') }}</th>
                                <th>{{ __('Closed By') }}</th>
                                <th class="text-right">{{ __('Actions') }}</th>
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
                                            <span class="status-pill positive">{{ __('Open') }}</span>
                                        @elseif($period->isClosed())
                                            <span class="status-pill neutral">{{ __('Closed') }}</span>
                                        @elseif($period->isLocked())
                                            <span class="status-pill negative">{{ __('Locked') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $period->closedByUser->name ?? '—' }}</td>
                                    <td class="text-right">
                                        @if($period->isOpen())
                                            <form method="POST" action="{{ route('accounting.periods.close', $period) }}" class="inline" onsubmit="return confirm('Close this period?');">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">{{ __('Close') }}</button>
                                            </form>
                                        @endif
                                        @if($period->isClosed())
                                            <form method="POST" action="{{ route('accounting.periods.lock', $period) }}" class="inline" onsubmit="return confirm('Lock this period?');">
                                                @csrf
                                                <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Lock') }}</button>
                                            </form>
                                            <form method="POST" action="{{ route('accounting.periods.reopen', $period) }}" class="inline" onsubmit="return confirm('Reopen this period?');">
                                                @csrf
                                                <button type="submit" class="text-ink hover:text-gold">{{ __('Reopen') }}</button>
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
                <x-detail-quick-actions :groups="[
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.fiscal-years.index'), 'icon' => 'back', 'title' => __('Back to Fiscal Years')],
                    ]],
                ]" />
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
            @can('fiscal-years.reopen')
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
            @endcan
        </div>
    </div>
    @endif
</x-app-layout>
