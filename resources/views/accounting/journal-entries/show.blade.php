<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Journal Entry') }} - {{ $journalEntry->journal_number }}</x-slot>

    <div class="pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <x-record-toolbar>
                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Create') }}</span>
                    <a href="{{ route('accounting.journal-entries.create') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        {{ __('New') }}
                    </a>
                    @if($journalEntry->status === 'draft')
                        @can('journal-entries.submit')
                            <form method="POST" action="{{ route('accounting.journal-entries.submit-for-approval', $journalEntry) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Submit for Approval') }}</button>
                            </form>
                        @endcan
                    @endif
                    @if($journalEntry->status === 'pending_approval')
                        @can('journal-entries.approve')
                            <form method="POST" action="{{ route('accounting.journal-entries.approve', $journalEntry) }}" class="inline">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Approve') }}</button>
                            </form>
                        @endcan
                        <button type="button" onclick="document.getElementById('rejectModal').classList.remove('hidden')" class="tr-item">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            {{ __('Reject') }}
                        </button>
                    @endif
                    @if($journalEntry->status === 'posted')
                        @can('journal-entries.reverse')
                            <form method="POST" action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" class="inline" onsubmit="return confirm('{{ __('Are you sure you want to reverse this entry?') }}');">
                                @csrf
                                <button type="submit" class="tr-save">{{ __('Reverse') }}</button>
                            </form>
                        @endcan
                    @endif
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Reference') }}</span>
                    <a href="{{ route('accounting.accounts.index') }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        {{ __('Lookup Account') }}
                    </a>
                    <a href="{{ route('accounting.journal-entries.index', ['search' => $journalEntry->journal_number]) }}" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        {{ __('View Related Transactions') }}
                    </a>
                </div>

                <div class="tr-divider"></div>

                <div class="tr-group">
                    <span class="tr-group-label">{{ __('Document') }}</span>
                    <button onclick="window.print()" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        {{ __('Print') }}
                    </button>
                    <button type="button" class="tr-item">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        {{ __('Attach File') }}
                    </button>
                    <button type="button" disabled title="{{ __('No email on file') }}" class="tr-item opacity-40 cursor-not-allowed">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        {{ __('Email') }}
                    </button>
                </div>

                <div class="tr-spacer"></div>

                <x-dropdown align="left" width="56">
                    <x-slot name="trigger">
                        <button type="button" class="tr-more" aria-label="{{ __('More actions') }}">
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
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                                {{ __('Add Note') }}
                            </button>
                            <a href="{{ route('accounting.journal-entries.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                                {{ __('Back to Journal Entries') }}
                            </a>
                        </div>
                    </x-slot>
                </x-dropdown>
            </x-record-toolbar>

            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative">
                    {{ session('error') }}
                </div>
            @endif

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Entry Information') }}</p>
                <div class="detail-grid">
                    <x-detail-field :label="__('Journal Number')" :value="$journalEntry->journal_number" />
                    <x-detail-field :label="__('Date')" :value="$journalEntry->date->format('M d, Y')" />
                    <x-detail-field :label="__('Reference')" :value="$journalEntry->reference ?? '—'" />
                    <x-detail-field :label="__('Status')">
                        @if($journalEntry->status === 'draft')
                            <span class="status-pill neutral">{{ __('Draft') }}</span>
                        @elseif($journalEntry->status === 'pending_approval')
                            <span class="status-pill neutral">{{ __('Pending Approval') }}</span>
                        @elseif($journalEntry->status === 'posted')
                            <span class="status-pill positive">{{ __('Posted') }}</span>
                        @elseif($journalEntry->status === 'reversed')
                            <span class="status-pill negative">{{ __('Reversed') }}</span>
                        @else
                            <span class="status-pill neutral">{{ ucfirst($journalEntry->status) }}</span>
                        @endif
                    </x-detail-field>
                    <x-detail-field :label="__('Branch')" :value="$journalEntry->branch->name ?? '—'" />
                    <x-detail-field :label="__('Adjusting Entry')" :value="$journalEntry->is_adjusting_entry ? __('Yes') : __('No')" />
                    <x-detail-field :label="__('Created By')" :value="$journalEntry->createdBy->name ?? '—'" />
                    <x-detail-field :label="__('Posted By')" :value="$journalEntry->postedByUser->name ?? '—'" />
                    @if($journalEntry->posted_at)
                        <x-detail-field :label="__('Posted At')" :value="\Illuminate\Support\Carbon::parse($journalEntry->posted_at)->format('M d, Y h:i A')" />
                    @endif
                    <x-detail-field :label="__('Description')" :value="$journalEntry->memo ?? '—'" class="col-span-4" />
                    @if($journalEntry->rejection_reason)
                        <x-detail-field :label="__('Rejection Reason')" :value="$journalEntry->rejection_reason" class="col-span-4" value-class="text-red-600" />
                    @endif
                </div>
            </div>

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Journal Lines') }}</p>
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
                            @foreach($journalEntry->lines as $index => $line)
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
                                        {{ $line->branch->name ?? $journalEntry->branch->name ?? '—' }}
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-50">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-right text-sm font-semibold text-gray-700">{{ __('Totals') }}</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($journalEntry->total_debit) }}</td>
                                <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($journalEntry->total_credit) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            @if($journalEntry->auditLogs->count() > 0)
                <div class="card p-6">
                    <p class="text-base font-semibold text-ink mb-5">{{ __('Audit Trail') }}</p>
                    <div class="overflow-x-auto">
                        <table class="datasheet">
                            <thead>
                                <tr>
                                    <th>{{ __('Date') }}</th>
                                    <th>{{ __('Action') }}</th>
                                    <th>{{ __('User') }}</th>
                                    <th>{{ __('Details') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($journalEntry->auditLogs->sortByDesc('created_at') as $log)
                                    <tr>
                                        <td class="text-ink-soft">
                                            {{ $log->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td>
                                            <span class="capitalize">{{ str_replace('_', ' ', $log->action) }}</span>
                                        </td>
                                        <td class="text-ink-soft">
                                            {{ $log->user->name ?? '—' }}
                                        </td>
                                        <td class="text-ink-soft">
                                            @if($log->old_values)
                                                <span class="text-red-600">{{ __('Old') }}:</span> {{ json_encode($log->old_values) }}
                                            @endif
                                            @if($log->new_values)
                                                <span class="text-green-600 ml-2">{{ __('New') }}:</span> {{ json_encode($log->new_values) }}
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>

    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Reject Journal Entry') }}</h3>
            @can('journal-entries.reject')
                <form method="POST" action="{{ route('accounting.journal-entries.reject', $journalEntry) }}">
                    @csrf
                    <div class="mb-4">
                        <x-input-label for="rejection_reason" value="{{ __('Reason for Rejection') }}" />
                        <textarea id="rejection_reason" name="rejection_reason" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                    </div>
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Cancel') }}
                        </button>
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150">
                            {{ __('Reject') }}
                        </button>
                    </div>
                </form>
            @endcan
        </div>
    </div>

    <style>
        @media print {
            body * {
                visibility: hidden;
            }
            .print-area, .print-area * {
                visibility: visible;
            }
            .print-area {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
            }
            nav, header, .no-print {
                display: none !important;
            }
        }
    </style>
</x-app-layout>
