{{-- ── Pane 1: Capture Reversal ── --}}
@if (!$loaded)
    <div class="gate">
        <div class="ic" aria-hidden="true">
            <svg viewBox="0 0 24 24">
                <path d="M8 7V3m8 4V3M3 11h18M5 21h14a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z"/>
            </svg>
        </div>
        <h2>{{ __('Load posted & saved transactions') }}</h2>
        <p>{{ __('Choose the period you want to review. Only posted journals inside this range can be reversed.') }}</p>

        @if ($dateError)
            <div class="gerr" role="alert">
                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.29 3.86 1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0Z"/></svg>
                {{ $dateError }}
            </div>
        @endif

        <form method="GET" action="{{ route('accounting.transaction-controls.index') }}" class="gform" data-tc-gate-form>
            <input type="hidden" name="tab" value="reversal">

            <label class="fgroup grow">
                <span class="fl">{{ __('From') }}</span>
                <input type="date" name="from" class="in" value="{{ $from ?? now()->startOfMonth()->format('Y-m-d') }}" required>
            </label>
            <label class="fgroup grow">
                <span class="fl">{{ __('To') }}</span>
                <input type="date" name="to" class="in" value="{{ $to ?? now()->format('Y-m-d') }}" required>
            </label>
            <div class="fgroup">
                <button type="submit" class="btn btn-cta">
                    <svg viewBox="0 0 24 24"><path d="m21 21-4.35-4.35M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/></svg>
                    {{ __('Load transactions') }}
                </button>
            </div>
        </form>

        <div class="presets">
            <span>{{ __('Quick ranges') }}</span>
            <button type="button" class="preset" @click="applyPreset($event, 'today')">{{ __('Today') }}</button>
            <button type="button" class="preset" @click="applyPreset($event, '7d')">{{ __('Last 7 days') }}</button>
            <button type="button" class="preset" @click="applyPreset($event, 'month')">{{ __('This month') }}</button>
            <button type="button" class="preset" @click="applyPreset($event, '30d')">{{ __('Last 30 days') }}</button>
        </div>
    </div>
@else
    @if ($dateError)
        <div class="gerr" role="alert">{{ $dateError }}</div>
    @endif

    <form method="GET" action="{{ route('accounting.transaction-controls.index') }}" class="filtersrow">
        <input type="hidden" name="tab" value="reversal">
        <input type="hidden" name="from" value="{{ $from }}">
        <input type="hidden" name="to" value="{{ $to }}">

        <span class="loaded-chip">
            <svg viewBox="0 0 24 24"><path d="M5 13l4 4L19 7"/></svg>
            {{ __('Loaded') }}
        </span>

        <div class="fgroup grow searchwrap">
            <span class="fl">{{ __('Search') }}</span>
            <svg viewBox="0 0 24 24" fill="none"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-4.35-4.35M11 19a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z"/></svg>
            <input type="text" name="q" class="in" placeholder="{{ __('Search reference or description…') }}" value="{{ $filters['q'] ?? '' }}">
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

        <div class="fgroup">
            <button type="submit" class="btn btn-sec">{{ __('Apply') }}</button>
        </div>
        <div class="fgroup">
            <a href="{{ route('accounting.transaction-controls.index', ['tab' => 'reversal', 'from' => $from, 'to' => $to]) }}" class="btn btn-ghost">{{ __('Clear') }}</a>
        </div>
    </form>

    <div class="tcard">
        <div class="txlist">
            <table>
                <thead>
                    <tr>
                        <th>{{ __('Reference') }}</th>
                        <th>{{ __('Type') }}</th>
                        <th>{{ __('Date') }}</th>
                        <th>{{ __('Description / Party') }}</th>
                        <th class="r">{{ __('Amount') }}</th>
                        <th>{{ __('Posted by') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th class="r">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($transactions as $entry)
                        <tr>
                            <td><span class="ref">{{ $entry->journal_number }}</span></td>
                            <td>@include('accounting.transaction-controls._type-chip', ['module' => $entry->source_module])</td>
                            <td>{{ optional($entry->date)->format('d M Y') }}</td>
                            <td>
                                <span class="dmain">{{ \Illuminate\Support\Str::limit($entry->memo ?: __('No description'), 60) }}</span>
                                @if ($entry->reference)
                                    <span class="dsub">{{ $entry->reference }}</span>
                                @endif
                            </td>
                            <td class="r tot">{{ number_format((float) $entry->total_debit, 2) }}</td>
                            <td>
                                <span class="who">
                                    <span class="avatar">{{ strtoupper(\Illuminate\Support\Str::substr(optional($entry->createdBy)->name ?? '—', 0, 1)) }}</span>
                                    {{ optional($entry->createdBy)->name ?? '—' }}
                                </span>
                            </td>
                            <td>
                                @if ($entry->isReversed())
                                    <span class="pill rev"><i></i>{{ __('Reversed') }}</span>
                                @else
                                    <span class="pill posted"><i></i>{{ __('Posted') }}</span>
                                @endif
                            </td>
                            <td class="r">
                                <div class="actcell">
                                    <button type="button" class="ib" title="{{ __('View transaction') }}" @click="openView(txById({{ $entry->id }}))">
                                        <svg viewBox="0 0 24 24"><path d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 4.5 12 4.5s8.58 3.01 9.96 7.18a1 1 0 0 1 0 .64C20.58 16.49 16.64 19.5 12 19.5s-8.58-3.01-9.96-7.18Z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="emptygate plain">
                                    <div class="t">{{ __('No transactions match') }}</div>
                                    <div class="s">{{ __('Adjust the filters or load a different period.') }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if ($transactions->total() > 0)
                    <tfoot>
                        <tr>
                            <td colspan="8">
                                <div class="tfoot">
                                    <span>{{ __('Showing') }} {{ $transactions->firstItem() }}–{{ $transactions->lastItem() }} {{ __('of') }} {{ $transactions->total() }} {{ __('transactions') }}</span>
                                    @if ($transactions->hasPages())
                                        <span class="pager">
                                            @if ($transactions->onFirstPage())
                                                <span class="pg dis">‹</span>
                                            @else
                                                <a href="{{ $transactions->previousPageUrl() }}" class="pg">‹</a>
                                            @endif
                                            @foreach ($transactions->getUrlRange(max(1, $transactions->currentPage() - 2), min($transactions->lastPage(), $transactions->currentPage() + 2)) as $page => $url)
                                                @if ($page === $transactions->currentPage())
                                                    <span class="pg on">{{ $page }}</span>
                                                @else
                                                    <a href="{{ $url }}" class="pg">{{ $page }}</a>
                                                @endif
                                            @endforeach
                                            @if ($transactions->hasMorePages())
                                                <a href="{{ $transactions->nextPageUrl() }}" class="pg">›</a>
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
@endif
