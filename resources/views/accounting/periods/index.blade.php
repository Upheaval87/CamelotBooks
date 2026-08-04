<x-app-layout>
    <x-list-header title="{{ __('Accounting Periods') }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="primary" onclick="document.getElementById('create-period-modal').classList.remove('hidden')">{{ __('Create Period') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
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

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th class="text-center">Status</th>
                                <th>Closed By</th>
                                <th>Closed At</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($periods as $period)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        {{ $period->label }}
                                    </td>
                                    <td>
                                        {{ $period->start_date->format('M d, Y') }}
                                    </td>
                                    <td>
                                        {{ $period->end_date->format('M d, Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($period->isOpen())
                                            <span class="status-pill positive">Open</span>
                                        @elseif($period->isClosed())
                                            <span class="status-pill neutral">Closed</span>
                                        @elseif($period->isLocked())
                                            <span class="status-pill negative">Locked</span>
                                        @else
                                            <span class="status-pill neutral">{{ ucfirst($period->status) }}</span>
                                        @endif
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $period->closedByUser->name ?? '—' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $period->closed_at ? $period->closed_at->format('M d, Y h:i A') : '—' }}
                                    </td>
                                    <td class="text-right">
                                        @if($period->isOpen())
                                            @can('accounting-periods.close')
                                                <form method="POST" action="{{ route('accounting.periods.close', $period) }}" class="inline" onsubmit="return confirm('Are you sure you want to close this period?');">
                                                    @csrf
                                                    <button type="submit" class="text-yellow-600 hover:text-yellow-900">Close</button>
                                                </form>
                                            @endcan
                                        @endif
                                        @if($period->isClosed())
                                            @can('accounting-periods.lock')
                                                <form method="POST" action="{{ route('accounting.periods.lock', $period) }}" class="inline" onsubmit="return confirm('Are you sure you want to lock this period? This cannot be undone.');">
                                                    @csrf
                                                    <button type="submit" class="text-red-600 hover:text-red-900">Lock</button>
                                                </form>
                                            @endcan
                                            @can('accounting-periods.reopen')
                                                <form method="POST" action="{{ route('accounting.periods.reopen', $period) }}" class="inline" onsubmit="return confirm('Are you sure you want to reopen this period?');">
                                                    @csrf
                                                    <button type="submit" class="text-ink hover:text-gold">Reopen</button>
                                                </form>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No accounting periods found.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="create-period-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Create Accounting Period</h3>
                <button onclick="document.getElementById('create-period-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <form method="POST" action="{{ route('accounting.periods.store') }}">
                @csrf
                <div class="mb-4">
                    <x-input-label for="label" value="{{ __('Label') }}" />
                    <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" placeholder="e.g. January 2026" required />
                    <x-input-error :messages="$errors->get('label')" class="mt-2" />
                </div>
                <div class="mb-4">
                    <x-input-label for="start_date" value="{{ __('Start Date') }}" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                </div>
                <div class="mb-6">
                    <x-input-label for="end_date" value="{{ __('End Date') }}" />
                    <x-text-input id="end_date" name="end_date" type="date" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('end_date')" class="mt-2" />
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('create-period-modal').classList.add('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </button>
                    <x-primary-button type="submit">{{ __('Create') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
