<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    <x-slot name="header">{{ __('Journal Entry') }} - {{ $journalEntry->journal_number }}</x-slot>

    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 mt-6">
        <x-toolbar class="mb-6">
            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Record</span>
            <x-toolbar-button href="{{ route('accounting.journal-entries.create') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                New
            </x-toolbar-button>
            @if($journalEntry->status === 'draft')
                <form method="POST" action="{{ route('accounting.journal-entries.submit-for-approval', $journalEntry) }}" class="inline">
                    @csrf
                    <x-toolbar-button variant="commit" type="submit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Submit for Approval
                    </x-toolbar-button>
                </form>
            @endif
            @if($journalEntry->status === 'pending_approval')
                <form method="POST" action="{{ route('accounting.journal-entries.approve', $journalEntry) }}" class="inline">
                    @csrf
                    <x-toolbar-button variant="commit" type="submit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Approve
                    </x-toolbar-button>
                </form>
                <x-toolbar-button onclick="document.getElementById('rejectModal').classList.remove('hidden')">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Reject
                </x-toolbar-button>
            @endif
            @if($journalEntry->status === 'posted')
                <form method="POST" action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" class="inline" onsubmit="return confirm('Are you sure you want to reverse this entry?');">
                    @csrf
                    <x-toolbar-button variant="commit" type="submit">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                        Reverse
                    </x-toolbar-button>
                </form>
            @endif

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Reference</span>
            <x-toolbar-button href="{{ route('accounting.accounts.index') }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                Lookup Account
            </x-toolbar-button>
            <x-toolbar-button href="{{ route('accounting.journal-entries.index', ['search' => $journalEntry->journal_number]) }}">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                View Related Transactions
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <span class="text-xs font-medium text-atlas-navy/40 uppercase tracking-wider mr-1">Document</span>
            <x-toolbar-button onclick="window.print()">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Print
            </x-toolbar-button>
            <x-toolbar-button>
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                Attach File
            </x-toolbar-button>
            <x-toolbar-button disabled title="No email on file">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                Email
            </x-toolbar-button>

            <span class="w-px h-5 bg-neutral-200 mx-1.5" role="separator"></span>

            <x-dropdown align="left" width="56">
                <x-slot name="trigger">
                    <x-toolbar-button>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"/></svg>
                    </x-toolbar-button>
                </x-slot>
                <x-slot name="content">
                    <div class="py-1">
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                            Duplicate
                        </button>
                        <button type="button" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/></svg>
                            Add Note
                        </button>
                        <a href="{{ route('accounting.journal-entries.index') }}" class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 flex items-center gap-2">
                            <svg class="w-4 h-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                            Back to Journal Entries
                        </a>
                    </div>
                </x-slot>
            </x-dropdown>

            <x-slot name="right">
            </x-slot>
        </x-toolbar>
    </div>

    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 space-y-6">
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

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Entry Information') }}</h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Journal Number') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->journal_number }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Date') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->date->format('M d, Y') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Reference') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->reference ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Status') }}</dt>
                    <dd class="mt-1">
                        @if($journalEntry->status === 'draft')
                            <span class="status-pill neutral">Draft</span>
                        @elseif($journalEntry->status === 'pending_approval')
                            <span class="status-pill neutral">Pending Approval</span>
                        @elseif($journalEntry->status === 'posted')
                            <span class="status-pill positive">Posted</span>
                        @elseif($journalEntry->status === 'reversed')
                            <span class="status-pill negative">Reversed</span>
                        @else
                            <span class="status-pill neutral">{{ ucfirst($journalEntry->status) }}</span>
                        @endif
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Branch') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->branch->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Adjusting Entry') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->is_adjusting_entry ? 'Yes' : 'No' }}</dd>
                </div>
                <div class="col-span-2">
                    <dt class="text-sm font-medium text-gray-500">{{ __('Memo') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->memo ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Created By') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->createdBy->name ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500">{{ __('Posted By') }}</dt>
                    <dd class="mt-1 text-sm text-gray-900">{{ $journalEntry->postedByUser->name ?? '—' }}</dd>
                </div>
                @if($journalEntry->posted_at)
                    <div>
                        <dt class="text-sm font-medium text-gray-500">{{ __('Posted At') }}</dt>
                        <dd class="mt-1 text-sm text-gray-900">{{ \Illuminate\Support\Carbon::parse($journalEntry->posted_at)->format('M d, Y h:i A') }}</dd>
                    </div>
                @endif
                @if($journalEntry->rejection_reason)
                    <div class="col-span-2">
                        <dt class="text-sm font-medium text-gray-500">{{ __('Rejection Reason') }}</dt>
                        <dd class="mt-1 text-sm text-red-600">{{ $journalEntry->rejection_reason }}</dd>
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Journal Lines') }}</h3>
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
                            <td colspan="2" class="px-6 py-4 text-right text-sm font-semibold text-gray-700">Totals</td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($journalEntry->total_debit) }}</td>
                            <td class="px-6 py-4 text-right text-sm font-bold text-gray-900">{{ format_number($journalEntry->total_credit) }}</td>
                            <td colspan="2"></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        @if($journalEntry->auditLogs->count() > 0)
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Audit Trail') }}</h3>
                <div class="overflow-x-auto">
                    <table class="datasheet">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Action</th>
                                <th>User</th>
                                <th>Details</th>
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
                                            <span class="text-red-600">Old:</span> {{ json_encode($log->old_values) }}
                                        @endif
                                        @if($log->new_values)
                                            <span class="text-green-600 ml-2">New:</span> {{ json_encode($log->new_values) }}
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

    <div id="rejectModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-full max-w-md shadow-lg rounded-md bg-white">
            <h3 class="text-lg font-semibold text-gray-800 mb-4">{{ __('Reject Journal Entry') }}</h3>
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
