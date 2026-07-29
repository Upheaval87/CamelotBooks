<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Recurring Journal Template') }} - {{ $template->name }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-toolbar class="mb-6">
                <button type="button" class="inline-flex items-center justify-center w-7 h-7 bg-transparent text-atlas-navy/50 rounded-md hover:bg-gray-100 transition-colors" title="{{ __('New') }}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                </button>
                <a href="{{ route('accounting.recurring-journals.edit', $template) }}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-atlas-amber text-atlas-navy text-sm font-medium rounded-md hover:brightness-110 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"/></svg>
                    Save
                </a>

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-atlas-blue/10 text-atlas-blue text-sm font-medium rounded-md hover:bg-atlas-blue/20 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Run Now
                </button>
                <form method="POST" action="{{ route('accounting.recurring-journals.toggle', $template) }}" class="inline">
                    @csrf
                    <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                        @if($template->is_active)
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Pause
                        @else
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Resume
                        @endif
                    </button>
                </form>
                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                    Run History
                </button>

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <button type="button" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent text-atlas-navy/70 text-sm font-medium rounded-md hover:bg-gray-100 transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Attach Template
                </button>

                <span class="w-px h-5 bg-gray-200 mx-1" role="separator"></span>

                <x-dropdown align="left" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="inline-flex items-center justify-center w-7 h-7 bg-transparent text-atlas-navy/50 rounded-md hover:bg-gray-100 transition-colors">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                Duplicate
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                Export
                            </button>
                            <a href="{{ route('accounting.recurring-journals.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                Back to Templates
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>

                <x-slot name="right">
                    <button type="button" onclick="if(confirm('Cancel this schedule? Future runs will be stopped.')){}" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-transparent border border-atlas-danger/30 text-atlas-danger text-sm font-medium rounded-md hover:bg-atlas-danger/5 transition-colors">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Cancel Schedule
                    </button>
                </x-slot>
            </x-toolbar>

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

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Template Information') }}</h3>
                <div class="grid grid-cols-2 gap-6">
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Name') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->name }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                        <dd class="mt-1">
                            @if($template->is_active)
                                <span class="status-pill positive">Active</span>
                            @else
                                <span class="status-pill negative">Inactive</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Frequency') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900 capitalize">{{ $template->frequency }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Branch') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->branch->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Start Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->start_date->format('M d, Y') }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('End Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->end_date?->format('M d, Y') ?? 'No end date' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Next Run Date') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->next_run_date?->format('M d, Y') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Auto Post') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->auto_post ? 'Yes' : 'No' }}</dd>
                    </div>
                    @if($template->day_of_month)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Day of Month') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ $template->day_of_month }}</dd>
                        </div>
                    @endif
                    @if($template->day_of_week !== null)
                        <div>
                            <dt class="text-sm font-medium text-gray-500">{{ __('Day of Week') }}</dt>
                            <dd class="mt-1 text-sm text-gray-900">{{ ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$template->day_of_week] }}</dd>
                        </div>
                    @endif
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->memo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Created By') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ $template->createdBy->name ?? '—' }}</dd>
                    </div>
                </div>
            </div>

            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Template Lines') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Account</th>
                                <th class="text-right">Debit ({{ $cs }})</th>
                                <th class="text-right">Credit ({{ $cs }})</th>
                                <th>Description</th>
                                <th>Branch</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($template->templateLines as $index => $line)
                                <tr>
                                    <td class="text-ink-soft">{{ $index + 1 }}</td>
                                    <td>
                                        {{ $line->account->code }} - {{ $line->account->name }}
                                    </td>
                                    <td class="numeric">
                                        {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $line->memo ?? '' }}
                                    </td>
                                    <td class="text-ink-soft">
                                        {{ $line->branch->name ?? $template->branch->name ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Totals</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($template->templateLines->sum('debit')) }}</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($template->templateLines->sum('credit')) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($template->journalEntries->count() > 0)
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Recent Generated Entries') }}</h3>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>Journal #</th>
                                    <th>Date</th>
                                    <th class="text-center">Status</th>
                                    <th class="text-right">Debit ({{ $cs }})</th>
                                    <th class="text-right">Credit ({{ $cs }})</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($template->journalEntries as $entry)
                                    <tr>
                                        <td>
                                            <a href="{{ route('accounting.journal-entries.show', $entry) }}" class="text-ink hover:text-gold">
                                                {{ $entry->journal_number }}
                                            </a>
                                        </td>
                                        <td>
                                            {{ $entry->date->format('M d, Y') }}
                                        </td>
                                        <td class="text-center">
                                            @if($entry->status === 'posted')
                                                <span class="status-pill positive">Posted</span>
                                            @elseif($entry->status === 'draft')
                                                <span class="status-pill neutral">Draft</span>
                                            @else
                                                <span class="status-pill neutral">{{ ucfirst($entry->status) }}</span>
                                            @endif
                                        </td>
                                        <td class="numeric">{{ format_number($entry->total_debit) }}</td>
                                        <td class="numeric">{{ format_number($entry->total_credit) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-app-layout>
