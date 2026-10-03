{{-- ── Pane 4: Reversals Processed ── --}}
@if ($dateError)
    <div class="gerr" role="alert">{{ $dateError }}</div>
@endif

<form method="GET" action="{{ route('accounting.transaction-controls.index') }}" class="filtersrow">
    <input type="hidden" name="tab" value="reversals_processed">

    <div class="fgroup grow searchwrap">
        <svg viewBox="0 0 24 24" fill="none"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/></svg>
        <input type="text" name="q" class="in" aria-label="{{ __('Search') }}" placeholder="{{ __('Search reversal or transaction reference…') }}" value="{{ $filters['q'] ?? '' }}">
    </div>

    <label class="fgroup">
        <span class="fl">{{ __('Type') }}</span>
        <select name="type" class="sel">
            <option value="">{{ __('All types') }}</option>
            @foreach ($typeOptions as $value => $label)
                <option value="{{ $value }}" @selected(($filters['type'] ?? '') === $value)>{{ $label }}</option>
            @endforeach
        </select>
    </label>

    <label class="fgroup">
        <span class="fl">{{ __('Reversed from') }}</span>
        <input type="date" name="from" class="in" value="{{ $from }}">
    </label>
    <label class="fgroup">
        <span class="fl">{{ __('Reversed to') }}</span>
        <input type="date" name="to" class="in" value="{{ $to }}">
    </label>

    <div class="fgroup">
        <button type="submit" class="btn btn-sec">{{ __('Apply') }}</button>
    </div>
    <div class="fgroup">
        <a href="{{ route('accounting.transaction-controls.index', ['tab' => 'reversals_processed']) }}" class="btn btn-ghost">{{ __('Clear') }}</a>
    </div>
</form>

<div class="tcard">
    <div class="txlist">
        <table>
            <thead>
                <tr>
                    <th>{{ __('Reversal №') }}</th>
                    <th>{{ __('Original') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Reversed on') }}</th>
                    <th>{{ __('Reason / Description') }}</th>
                    <th class="r">{{ __('Amount') }}</th>
                    <th>{{ __('Approved by') }}</th>
                    <th>{{ __('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($processed as $req)
                    @php
                        $reversal = $req->reversal;
                        $original = $req->journalEntry;
                        $amount = (float) ($reversal?->amount ?? $original?->total_debit ?? 0);
                    @endphp
                    <tr>
                        <td>
                            @if ($reversal?->reversal_journal_entry_id)
                                <a class="ref" href="{{ route('accounting.journal-entries.show', $reversal->reversal_journal_entry_id) }}">{{ $reversal->reversal_number }}</a>
                            @else
                                <span class="ref">{{ $req->reference_number }}</span>
                            @endif
                            <span class="dsub">{{ $req->reference_number }}</span>
                        </td>
                        <td>
                            @if ($original)
                                <a class="ref" href="{{ route('accounting.journal-entries.show', $original->id) }}">{{ $original->journal_number }}</a>
                            @else
                                <span class="ref">—</span>
                            @endif
                        </td>
                        <td>@include('accounting.transaction-controls._type-chip', ['module' => $req->original_transaction_type])</td>
                        <td class="mono">{{ optional($req->reversal_date)->format('d M Y') ?? '—' }}</td>
                        <td>
                            <span class="dmain">{{ \Illuminate\Support\Str::limit($original?->memo ?: __('No description'), 60) }}</span>
                            @if ($req->reason)
                                <span class="dsub">{{ \Illuminate\Support\Str::limit($req->reason, 60) }}</span>
                            @endif
                        </td>
                        <td class="r tot">{{ number_format($amount, 2) }}</td>
                        <td>
                            <span class="who">
                                <span class="avatar">{{ strtoupper(\Illuminate\Support\Str::substr($userNames[(int) $req->approved_by] ?? '—', 0, 1)) }}</span>
                                {{ $userNames[(int) $req->approved_by] ?? '—' }}
                            </span>
                        </td>
                        <td>
                            <span class="pill ok"><i></i>{{ __('Reversed') }}</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8">
                            <div class="emptygate plain">
                                <div class="t">{{ __('No reversals processed yet') }}</div>
                                <div class="s">{{ __('Reversals authorized in the Authorization tab will appear here once posted.') }}</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            @if ($processed->total() > 0)
                <tfoot>
                    <tr>
                        <td colspan="8">
                            <div class="tfoot">
                                <span>{{ __('Showing') }} {{ $processed->firstItem() }}–{{ $processed->lastItem() }} {{ __('of') }} {{ $processed->total() }} {{ __('processed reversals') }}</span>
                                @if ($processed->hasPages())
                                    <span class="pager">
                                        @if ($processed->onFirstPage())
                                            <span class="pg dis">‹</span>
                                        @else
                                            <a href="{{ $processed->previousPageUrl() }}" class="pg">‹</a>
                                        @endif
                                        @foreach ($processed->getUrlRange(max(1, $processed->currentPage() - 2), min($processed->lastPage(), $processed->currentPage() + 2)) as $page => $url)
                                            @if ($page === $processed->currentPage())
                                                <span class="pg on">{{ $page }}</span>
                                            @else
                                                <a href="{{ $url }}" class="pg">{{ $page }}</a>
                                            @endif
                                        @endforeach
                                        @if ($processed->hasMorePages())
                                            <a href="{{ $processed->nextPageUrl() }}" class="pg">›</a>
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
