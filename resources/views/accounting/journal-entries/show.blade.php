<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp
    @php
        $jeStatusBadge = match ($journalEntry->status) {
            'pending_approval' => 'pending',
            'posted' => 'approved',
            'reversed', 'rejected' => 'rejected',
            default => 'neutral',
        };
    @endphp

    <x-review.head
        :title="__('Journal Entry') . ' - ' . $journalEntry->journal_number"
        :back-url="route('accounting.journal-entries.index')"
        back-label="{{ __('Back to Journal Entries') }}"
    >
        <x-slot name="badge">
            <x-review.badge :variant="$jeStatusBadge" :dot="in_array($journalEntry->status, ['pending_approval', 'posted'], true)">
                @if($journalEntry->status === 'draft'){{ __('Draft') }}
                @elseif($journalEntry->status === 'pending_approval'){{ __('Pending Approval') }}
                @elseif($journalEntry->status === 'posted'){{ __('Posted') }}
                @elseif($journalEntry->status === 'reversed'){{ __('Reversed') }}
                @elseif($journalEntry->status === 'rejected'){{ __('Rejected') }}
                @else{{ ucfirst($journalEntry->status) }}@endif
            </x-review.badge>
        </x-slot>
    </x-review.head>

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
                    @if($journalEntry->status === 'posted')
                        @can('journal-entries.reverse')
                            <form method="POST" action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" class="inline" onsubmit="return fbConfirmSubmit(event, '{{ __('Are you sure you want to reverse this entry?') }}');">
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

            <div class="detail-page">
                <div class="detail-page-main">

            

            

            <x-review.card title="{{ __('Entry Information') }}" icon="<path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'/>">
                <div class="mt-[22px] grid grid-cols-1 gap-x-8 gap-y-[22px] md:grid-cols-2 lg:grid-cols-3">
                    <x-review.field label="{{ __('Journal Number') }}" mono>{{ $journalEntry->journal_number }}</x-review.field>
                    <x-review.field label="{{ __('Date') }}">{{ $journalEntry->date->format('M d, Y') }}</x-review.field>
                    <x-review.field label="{{ __('Reference') }}" mono>{{ $journalEntry->reference ?? '—' }}</x-review.field>
                    <x-review.field label="{{ __('Branch') }}">{{ $journalEntry->branch->name ?? '—' }}</x-review.field>
                    <x-review.field label="{{ __('Adjusting Entry') }}">{{ $journalEntry->is_adjusting_entry ? __('Yes') : __('No') }}</x-review.field>
                    <x-review.field label="{{ __('Created By') }}">{{ $journalEntry->createdBy->name ?? '—' }}</x-review.field>
                    <x-review.field label="{{ __('Posted By') }}">{{ $journalEntry->postedByUser->name ?? '—' }}</x-review.field>
                    @if($journalEntry->posted_at)
                        <x-review.field label="{{ __('Posted At') }}">{{\Illuminate\Support\Carbon::parse($journalEntry->posted_at)->format('M d, Y h:i A') }}</x-review.field>
                    @endif
                    @if($journalEntry->rejection_reason)
                        <x-review.field label="{{ __('Rejection Reason') }}" class="lg:col-span-2"><span class="text-red-600">{{ $journalEntry->rejection_reason }}</span></x-review.field>
                    @endif
                    <x-review.field label="{{ __('Description') }}" class="lg:col-span-3">{{ $journalEntry->memo ?? '—' }}</x-review.field>
                </div>
            </x-review.card>

            @if($journalEntry->status === 'pending_approval')
                <x-review.decision title="{{ __('Review & Decide') }}" hint="{{ __('Approve to post this entry, or reject it with a reason.') }}">
                    <x-slot name="fields">
                        <form id="je-reject-form" method="POST" action="{{ route('accounting.journal-entries.reject', $journalEntry) }}">
                            @csrf
                            <div>
                                <x-input-label for="rejection_reason" value="{{ __('Reason for Rejection') }}" />
                                <textarea id="rejection_reason" name="rejection_reason" rows="3" class="min-h-[110px] mt-1 block w-full rounded-xl border-shell bg-white/80 focus:border-[rgba(182,145,63,.55)] focus:ring-[3px] focus:ring-[rgba(182,145,63,.15)] focus:outline-none" required></textarea>
                                <x-input-error :messages="$errors->get('rejection_reason')" class="mt-2" />
                            </div>
                        </form>
                    </x-slot>
                    <x-slot name="actions">
                        <form id="je-approve-form" method="POST" action="{{ route('accounting.journal-entries.approve', $journalEntry) }}" class="inline">
                            @csrf
                        </form>
                        <x-review.btn variant="reject" type="submit" form="je-reject-form">{{ __('Reject') }}</x-review.btn>
                        <x-review.btn variant="primary" size="lg" type="submit" form="je-approve-form">{{ __('Approve') }}</x-review.btn>
                    </x-slot>
                </x-review.decision>
            @elseif($journalEntry->status === 'posted')
                <x-review.outcome
                    title="{{ __('Journal entry approved and posted') }}"
                    :description="__('Approved by') . ' ' . ($journalEntry->approvedByUser->name ?? $journalEntry->postedByUser->name ?? '—') . ($journalEntry->approved_at ? ' — ' . \Illuminate\Support\Carbon::parse($journalEntry->approved_at)->format('M d, Y h:i A') : '')"
                    chip="POSTED"
                />
            @elseif($journalEntry->status === 'rejected')
                <x-review.outcome
                    title="{{ __('Journal entry rejected') }}"
                    :description="$journalEntry->rejection_reason ?: __('The entry was not approved.')"
                    chip="REJECTED"
                    tone="rejected"
                />
            @elseif($journalEntry->status === 'reversed')
                <x-review.outcome
                    title="{{ __('Journal entry reversed') }}"
                    :description="__('This entry was reversed and no longer affects the ledger.')"
                    chip="REVERSED"
                />
            @endif

            <div class="card p-6">
                <p class="text-base font-semibold text-ink mb-5">{{ __('Journal Lines') }}</p>
                <div class="overflow-x-auto">
                    <table class="record-datasheet">
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
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        {{ $line->account->code }} - {{ $line->account->name }}
                                    </td>
                                    <td class="numeric">
                                        {{ $line->debit > 0 ? format_number($line->debit) : '' }}
                                    </td>
                                    <td class="numeric">
                                        {{ $line->credit > 0 ? format_number($line->credit) : '' }}
                                    </td>
                                    <td>
                                        {{ $line->memo ?? '' }}
                                    </td>
                                    <td>
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
                        <table class="record-datasheet">
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
                                        <td>
                                            {{ $log->created_at->format('M d, Y h:i A') }}
                                        </td>
                                        <td>
                                            <span class="capitalize">{{ str_replace('_', ' ', $log->action) }}</span>
                                        </td>
                                        <td>
                                            {{ $log->user->name ?? '—' }}
                                        </td>
                                        <td>
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
                <x-detail-quick-actions :groups="[
                    ['label' => __('Insights'), 'links' => [
                        ['route' => 'javascript:window.print()', 'icon' => 'print', 'title' => __('Print')],
                    ]],
                    ['label' => __('Navigation'), 'links' => [
                        ['route' => route('accounting.journal-entries.index'), 'icon' => 'back', 'title' => __('Back to Journal Entries')],
                    ]],
                ]" />
            </div>
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
