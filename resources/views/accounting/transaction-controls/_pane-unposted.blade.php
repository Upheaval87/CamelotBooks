{{-- ── Pane 2: Unposted Transactions ── --}}
<div class="rowhead">
    <p class="hint">{{ __('Draft and finalized journals awaiting posting. Reopen a finalized entry to edit it, or delete a draft you own.') }}</p>
    @can('journal-entries.create')
        <a class="btn btn-ghost btn-sm sp" href="{{ route('accounting.journal-entries.create') }}">
            <svg viewBox="0 0 24 24"><path d="M12 5v14m-7-7h14"/></svg>
            {{ __('New Journal') }}
        </a>
    @endcan
</div>

<div class="tcard">
    <div class="txlist">
        <table>
            <thead>
                <tr>
                    <th>{{ __('Reference') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Saved') }}</th>
                    <th>{{ __('Description') }}</th>
                    <th class="r">{{ __('Amount') }}</th>
                    <th>{{ __('Created by') }}</th>
                    <th>{{ __('State') }}</th>
                    <th class="r">{{ __('Actions') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($unposted as $entry)
                    <tr>
                        <td><span class="ref">{{ $entry->journal_number }}</span></td>
                        <td>@include('accounting.transaction-controls._type-chip', ['module' => $entry->source_module])</td>
                        <td class="mono">{{ optional($entry->updated_at)->format('d M Y') }}</td>
                        <td>
                            <span class="dmain">{{ \Illuminate\Support\Str::limit($entry->memo ?: __('No description'), 60) }}</span>
                            <span class="dsub">
                                @if ($entry->status === \App\Models\JournalEntry::STATUS_PENDING_APPROVAL)
                                    {{ __('Finalized — awaiting post') }}
                                @else
                                    {{ __('Draft — never posted') }}
                                @endif
                            </span>
                        </td>
                        <td class="r tot">{{ number_format((float) $entry->total_debit, 2) }}</td>
                        <td>
                            <span class="who">
                                <span class="avatar">{{ strtoupper(\Illuminate\Support\Str::substr(optional($entry->createdBy)->name ?? '—', 0, 1)) }}</span>
                                {{ optional($entry->createdBy)->name ?? '—' }}
                            </span>
                        </td>
                        <td>
                            @if ($entry->status === \App\Models\JournalEntry::STATUS_PENDING_APPROVAL)
                                <span class="pill fin"><i></i>{{ __('Finalized') }}</span>
                            @else
                                <span class="pill draft"><i></i>{{ __('Draft') }}</span>
                            @endif
                        </td>
                        <td class="r">
                            <div class="actcell">
                                <button type="button" class="ib" title="{{ __('View') }}" @click="openView(unpostedById({{ $entry->id }}))">
                                    <svg viewBox="0 0 24 24"><path d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18Z"/><circle cx="12" cy="12" r="3"/></svg>
                                </button>
                                @if ($entry->status === \App\Models\JournalEntry::STATUS_PENDING_APPROVAL)
                                    @can('journal-entries.edit')
                                        <button type="button" class="ib warnb" title="{{ __('Reopen') }}" @click="askReopen({{ $entry->id }})">
                                            <svg viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9M3 3v6h6"/></svg>
                                        </button>
                                    @endcan
                                @else
                                    <button type="button" class="ib delb" title="{{ __('Delete draft') }}" @click="askDeleteUnposted({{ $entry->id }})">
                                        <svg viewBox="0 0 24 24"><path d="M3 6h18M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2m2 0v14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2V6"/></svg>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="emptygate plain">
                                <div class="t">{{ __('Nothing is awaiting posting') }}</div>
                                <div class="s">{{ __('Draft and finalized journals will appear here.') }}</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($unposted->total() > 0)
                <tfoot>
                    <tr>
                        <td colspan="8">
                            <div class="tfoot">
                                <span>{{ __('Showing') }} {{ $unposted->firstItem() }}–{{ $unposted->lastItem() }} {{ __('of') }} {{ $unposted->total() }} {{ __('unposted transactions') }}</span>
                                @if ($unposted->hasPages())
                                    <span class="pager">
                                        @if ($unposted->onFirstPage())
                                            <span class="pg dis">‹</span>
                                        @else
                                            <a href="{{ $unposted->previousPageUrl() }}" class="pg">‹</a>
                                        @endif
                                        @foreach ($unposted->getUrlRange(max(1, $unposted->currentPage() - 2), min($unposted->lastPage(), $unposted->currentPage() + 2)) as $page => $url)
                                            @if ($page === $unposted->currentPage())
                                                <span class="pg on">{{ $page }}</span>
                                            @else
                                                <a href="{{ $url }}" class="pg">{{ $page }}</a>
                                            @endif
                                        @endforeach
                                        @if ($unposted->hasMorePages())
                                            <a href="{{ $unposted->nextPageUrl() }}" class="pg">›</a>
                                        @else
                                            <span class="pg dis">›</span>
                                        @endif
                                    </span>
                                @endif
                            </div>
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </div>
</div>
