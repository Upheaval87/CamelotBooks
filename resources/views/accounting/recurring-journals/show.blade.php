<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Recurring Journal Template') }} - {{ $template->name }}</x-slot>

    <div class="py-12">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Actions') }}</span>
                    <a href="{{ route('accounting.recurring-journals.edit', $template) }}" class="tr-save">{{ __('Save') }}</a>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('Run Now') }}
                    </button>
                    <form method="POST" action="{{ route('accounting.recurring-journals.toggle', $template) }}" class="inline">
                        @csrf
                        <button type="submit" class="tr-item">
                            @if($template->is_active)
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 9v6m4-6v6m7-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ __('Pause') }}
                            @else
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                {{ __('Resume') }}
                            @endif
                        </button>
                    </form>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        {{ __('Run History') }}
                    </button>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('Attach Template') }}
                    </button>
                </div>

                <div class="tr-spacer"></div>

                <button type="button" class="tr-archive" onclick="if(confirm('{{ __('Cancel this schedule?') }}')){}">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    {{ __('Cancel Schedule') }}
                </button>

                <x-dropdown align="left" width="48">
                    <x-slot name="trigger">
                        <button type="button" class="tr-more">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <div class="py-1">
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                                {{ __('Duplicate') }}
                            </button>
                            <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                {{ __('Export') }}
                            </button>
                            <a href="{{ route('accounting.recurring-journals.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                {{ __('Back to Templates') }}
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-record-toolbar>

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

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Template Information') }}</p>
                <div class="detail-grid">
                    <x-detail-field :label="__('Name')" :value="$template->name" />
                    <x-detail-field :label="__('Status')">
                        @if($template->is_active)
                            <span class="status-pill positive">{{ __('Active') }}</span>
                        @else
                            <span class="status-pill negative">{{ __('Inactive') }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field :label="__('Frequency')" :value="ucfirst($template->frequency)" />
                    <x-detail-field :label="__('Branch')" :value="$template->branch->name ?? '—'" />
                    <x-detail-field :label="__('Start Date')" :value="$template->start_date->format('M d, Y')" />
                    <x-detail-field :label="__('End Date')" :value="$template->end_date?->format('M d, Y') ?? __('No end date')" />
                    <x-detail-field :label="__('Next Run Date')" :value="$template->next_run_date?->format('M d, Y') ?? '—'" />
                    <x-detail-field :label="__('Auto Post')" :value="$template->auto_post ? __('Yes') : __('No')" />
                    @if($template->day_of_month)
                        <x-detail-field :label="__('Day of Month')" :value="$template->day_of_month" />
                    @endif
                    @if($template->day_of_week !== null)
                        <x-detail-field :label="__('Day of Week')" :value="['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'][$template->day_of_week]" />
                    @endif
                    <x-detail-field :label="__('Memo')" :value="$template->memo ?? '—'" class="col-span-3" />
                    <x-detail-field :label="__('Created By')" :value="$template->createdBy->name ?? '—'" />
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Template Lines') }}</p>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>{{ __('Account') }}</th>
                                <th class="text-right">{{ __('Debit') }} ({{ $cs }})</th>
                                <th class="text-right">{{ __('Credit') }} ({{ $cs }})</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Branch') }}</th>
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
                                <td colspan="2" class="px-6 py-4 text-right text-sm font-semibold text-gray-700">{{ __('Totals') }}</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($template->templateLines->sum('debit')) }}</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($template->templateLines->sum('credit')) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($template->journalEntries->count() > 0)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Recent Generated Entries') }}</p>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>{{ __('Journal #') }}</th>
                                    <th>{{ __('Date') }}</th>
                                    <th class="text-center">{{ __('Status') }}</th>
                                    <th class="text-right">{{ __('Debit') }} ({{ $cs }})</th>
                                    <th class="text-right">{{ __('Credit') }} ({{ $cs }})</th>
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
                                                <span class="status-pill positive">{{ __('Posted') }}</span>
                                            @elseif($entry->status === 'draft')
                                                <span class="status-pill neutral">{{ __('Draft') }}</span>
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
