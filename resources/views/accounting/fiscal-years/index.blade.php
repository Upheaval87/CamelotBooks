<x-app-layout>
    <x-list-header title="{{ __('Fiscal Years') }}" />

    <div class="flex items-center justify-end gap-2 mb-4">
        <x-button variant="primary" onclick="document.getElementById('create-fy-modal').classList.remove('hidden')">{{ __('Create Fiscal Year') }}</x-button>
    </div>

    <div class="pb-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">
            

            

            <div class="datasheet-wrap">
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Label</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Periods</th>
                                <th>Closed By</th>
                                <th class="text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($fiscalYears as $fy)
                                <tr class="hover:bg-gray-50">
                                    <td>
                                        <a href="{{ route('accounting.fiscal-years.show', $fy) }}" class="text-ink hover:text-gold">{{ $fy->label }}</a>
                                    </td>
                                    <td>
                                        {{ $fy->start_date->format('M d, Y') }}
                                    </td>
                                    <td>
                                        {{ $fy->end_date->format('M d, Y') }}
                                    </td>
                                    <td class="text-center">
                                        @if($fy->isOpen())
                                            <span class="status-pill positive">Open</span>
                                        @elseif($fy->isClosed())
                                            <span class="status-pill neutral">Closed</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-center text-sm text-gray-500">
                                        {{ $fy->periods->count() }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $fy->closedByUser->name ?? '—' }}
                                    </td>
                                    <td class="text-right">
                                        <a href="{{ route('accounting.fiscal-years.show', $fy) }}" class="text-ink hover:text-gold">View</a>
                                        @if($fy->isOpen() && $fy->allPeriodsClosedOrLocked())
                                            <form method="POST" action="{{ route('accounting.fiscal-years.close', $fy) }}" class="inline" onsubmit="return fbConfirmSubmit(event, 'Are you sure you want to close fiscal year {{ $fy->label }}? This will post a closing journal entry.', { type: 'action' });">
                                                @csrf
                                                <button type="submit" class="text-yellow-600 hover:text-yellow-900">Close Year</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-ink-soft">
                                        No fiscal years found. Create one to organize your accounting periods.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div id="create-fy-modal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-lg shadow-lg rounded-md bg-white">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-gray-900">Create Fiscal Year</h3>
                <button onclick="document.getElementById('create-fy-modal').classList.add('hidden')" class="text-gray-400 hover:text-gray-600">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <p class="text-sm text-gray-500 mb-4">12 monthly periods will be automatically generated within the fiscal year.</p>
            <form method="POST" action="{{ route('accounting.fiscal-years.store') }}">
                @csrf
                <div class="mb-4">
                    <x-input-label for="label" value="{{ __('Label') }}" />
                    <x-text-input id="label" name="label" type="text" class="mt-1 block w-full" placeholder="e.g. FY2026" required />
                    <x-input-error :messages="$errors->get('label')" class="mt-2" />
                </div>
                <div class="mb-6">
                    <x-input-label for="start_date" value="{{ __('Start Date') }}" />
                    <x-text-input id="start_date" name="start_date" type="date" class="mt-1 block w-full" required />
                    <x-input-error :messages="$errors->get('start_date')" class="mt-2" />
                    <p class="text-xs text-gray-400 mt-1">End date is automatically calculated as 12 months later.</p>
                </div>
                <div class="flex justify-end gap-2">
                    <button type="button" onclick="document.getElementById('create-fy-modal').classList.add('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-gold-500 focus:ring-offset-2 transition ease-in-out duration-150">
                        Cancel
                    </button>
                    <x-primary-button type="submit">{{ __('Create') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
