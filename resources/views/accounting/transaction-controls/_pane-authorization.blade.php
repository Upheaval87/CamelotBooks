{{-- ── Pane 3: Authorization ── --}}
<div class="sechead">
    <h2>{{ __('Pending authorization') }}</h2>
    <span class="cnt">{{ $authQueue->count() }}</span>
</div>

<div class="tcard">
    <div class="txlist">
        <table>
            <thead>
                <tr>
                    <th>{{ __('Request') }}</th>
                    <th>{{ __('Type') }}</th>
                    <th>{{ __('Requested by') }}</th>
                    <th class="r">{{ __('Amount') }}</th>
                    <th>{{ __('Submitted') }}</th>
                    <th>{{ __('Waiting') }}</th>
                    <th class="r">{{ __('Action') }}</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($authQueue as $auth)
                    @php
                        $req = $auth->request;
                        $amt = (float) ($req?->partial_amount ?: ($req?->journalEntry?->total_debit ?? 0));
                    @endphp
                    <tr>
                        <td>
                            <span class="ref">{{ $req?->reference_number ?: ($req?->journalEntry?->journal_number ?? '—') }}</span>
                            <span class="dsub">{{ __('Reverse') }} {{ $req?->journalEntry?->journal_number }}</span>
                        </td>
                        <td>@include('accounting.transaction-controls._type-chip', ['module' => $req?->original_transaction_type ?: 'reversal'])</td>
                        <td>
                            <span class="who">
                                <span class="avatar">{{ strtoupper(\Illuminate\Support\Str::substr($userNames[(int) $req?->requested_by] ?? '—', 0, 1)) }}</span>
                                {{ $userNames[(int) $req?->requested_by] ?? '—' }}
                            </span>
                        </td>
                        <td class="r tot">{{ number_format($amt, 2) }}</td>
                        <td class="mono">{{ optional($req?->request_date)->format('d M Y') ?? '—' }}</td>
                        <td>
                            @if ($req?->request_date)
                                <span class="age">{{ $req->request_date->diffForHumans(null, true) }}</span>
                            @else
                                <span class="age">—</span>
                            @endif
                        </td>
                        <td class="r">
                            <button type="button" class="btn btn-cta btn-sm" @click="openAuth(authById({{ $auth->id }}))">
                                <svg viewBox="0 0 24 24"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/></svg>
                                {{ __('Review') }}
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
                            <div class="emptygate plain">
                                <div class="t">{{ __('Queue clear') }}</div>
                                <div class="s">{{ __('No reversal requests are awaiting authorization.') }}</div>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="7">
                        <div class="tfoot">
                            <span>{{ $authQueue->count() }} {{ \Illuminate\Support\Str::plural('pending authorization', $authQueue->count()) }}</span>
                        </div>
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="hist">
    <h3 class="ht">{{ __('Recently decided') }}</h3>
    <div class="tcard">
        <div class="txlist">
            <table>
                <tbody>
                    @forelse ($authDecided as $auth)
                        @php
                            $req = $auth->request;
                            $amt = (float) ($req?->partial_amount ?: ($req?->journalEntry?->total_debit ?? 0));
                            $isRejected = $req && $req->status === \App\Models\TransactionReversalRequest::STATUS_REJECTED;
                        @endphp
                        <tr>
                            <td><span class="ref">{{ $req?->reference_number ?: ($req?->journalEntry?->journal_number ?? '—') }}</span></td>
                            <td><span class="dmain">{{ __('Reverse') }} {{ $req?->journalEntry?->journal_number }} — {{ \Illuminate\Support\Str::limit($req?->reason, 50) }}</span></td>
                            <td>
                                <span class="who">
                                    <span class="avatar">{{ strtoupper(\Illuminate\Support\Str::substr($userNames[(int) $req?->requested_by] ?? '—', 0, 1)) }}</span>
                                    {{ $userNames[(int) $req?->requested_by] ?? '—' }}
                                </span>
                            </td>
                            <td class="r tot">{{ number_format($amt, 2) }}</td>
                            <td>
                                @if ($isRejected)
                                    <span class="pill rev"><i></i>{{ __('Rejected') }}</span>
                                @else
                                    <span class="pill ok"><i></i>{{ __('Approved') }}</span>
                                @endif
                            </td>
                            <td class="dsub">
                                {{ optional($auth->approved_date)->format('d M Y') ?? '—' }}
                                @if (! empty($userNames[(int) $auth->approved_by])) · {{ $userNames[(int) $auth->approved_by] }} @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td>
                                <div class="emptygate plain">
                                    <div class="t">{{ __('No decisions recorded yet') }}</div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
