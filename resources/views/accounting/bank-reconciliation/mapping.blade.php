<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Map Statement Columns') }}</h1>
                    <div class="sub">
                        {{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}
                        @if($reconciliation->period_start && $reconciliation->period_end)
                            &middot; {{ $reconciliation->period_start->format('M d, Y') }} – {{ $reconciliation->period_end->format('M d, Y') }}
                        @endif
                    </div>
                </div>
                <div class="br-cluster">
                    <a href="{{ route('accounting.bank-reconciliation.import', $reconciliation->id) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M3 5h6l2 2h10v12H3z" /><path d="M3 5h6l2 2h10v12H3z" opacity="0" /></svg>
                        {{ __('Change file') }}
                    </a>
                    <a href="{{ route('accounting.bank-reconciliation.workspace', $reconciliation->id) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:15px;height:15px"><path d="M10 19l-7-7m0 0l7-7m-7 7h18" /></svg>
                        {{ __('Back to Workspace') }}
                    </a>
                </div>
            </div>

            @if(isset($error))
                <div class="note-info" style="margin-bottom:16px">
                    <strong>{{ __('Mapping incomplete') }}:</strong>
                    {{ $error }}
                </div>
            @endif

            <div class="card" style="max-width:1120px;margin:0 auto">
                <div class="card-h">
                    <h2>{{ __('Map Statement Columns') }}</h2>
                    <div class="right">
                        <div class="fmtchips">
                            <span class="fmt on">{{ $preview['total_rows'] }} {{ __('rows') }}</span>
                            <span class="fmt on">{{ $originalName }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-b">
                    <div class="stepper">
                        <div class="stp"><span class="d done">&#10003;</span><span class="t">{{ __('Statement') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d cur">2</span><span class="t cur">{{ __('Import') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d todo">3</span><span class="t">{{ __('Matching') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d todo">4</span><span class="t">{{ __('Review') }}</span></div><span class="bar"></span>
                        <div class="stp"><span class="d todo">5</span><span class="t">{{ __('Complete') }}</span></div>
                    </div>

                    <div class="note-info" style="margin-bottom:16px">
                        {{ __('Match your statement columns to the system fields below. Columns are auto-suggested from the file header — adjust as needed.') }}
                    </div>

                    <div class="g2" style="margin-bottom:4px">
                        <div class="ro">
                            <div class="l">{{ __('Bank Account') }}</div>
                            <div class="v">{{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}</div>
                        </div>
                        <div class="ro">
                            <div class="l">{{ __('Statement Currency') }}</div>
                            <div class="v">{{ $reconciliation->currency ?? '—' }}</div>
                        </div>
                        <div class="ro">
                            <div class="l">{{ __('Statement Balance') }} ({{ $cs }})</div>
                            <div class="v">{{ format_number($reconciliation->statement_balance) }}</div>
                        </div>
                        <div class="ro">
                            <div class="l">{{ __('Period') }}</div>
                            <div class="v">{{ $reconciliation->period_start?->format('M d, Y') ?? '—' }} – {{ $reconciliation->period_end?->format('M d, Y') ?? '—' }}</div>
                        </div>
                    </div>

                    <form method="POST" action="{{ route('accounting.bank-reconciliation.import.submit', $reconciliation->id) }}" id="mapping-form">
                        @csrf
                        <input type="hidden" name="upload" value="{{ $storedName }}" />

                        <div style="margin:10px 0 6px">
                            <label style="display:inline-flex;align-items:center;gap:8px;font-size:0.893rem;font-weight:700;color:var(--ink,#0B2A2D);cursor:pointer">
                                <input type="hidden" name="has_header" value="0" />
                                <input type="checkbox" name="has_header" value="1" style="width:15px;height:15px" @checked(old('has_header', $hasHeader ?? true)) />
                                {{ __('This file has a header row (skip the first row)') }}
                            </label>
                        </div>

                        @php
                            $fields = [
                                'date'        => __('Transaction Date'),
                                'reference'   => __('Reference'),
                                'description' => __('Description'),
                                'debit'       => __('Debit (Withdrawal)'),
                                'credit'      => __('Credit (Deposit)'),
                                'amount'      => __('Amount (single column)'),
                                'balance'     => __('Balance'),
                            ];
                            $required = ['date'];
                        @endphp

                        @foreach($fields as $key => $label)
                            @php
                                $selected = old("map.$key", $defaults[$key] ?? null);
                                $isRequired = in_array($key, $required);
                            @endphp
                            <div class="maprow">
                                <span class="src">{{ $label }}@if($isRequired) <em style="color:var(--red-2,#B91C1C);font-style:normal">*</em>@endif</span>
                                <span class="arr">&rarr;</span>
                                <select
                                    name="map[{{ $key }}]"
                                    class="input"
                                    style="width:100%"
                                    {{ $isRequired ? 'required' : '' }}
                                >
                                    <option value="">— {{ __('Not mapped') }} —</option>
                                    @foreach($preview['header'] as $index => $cell)
                                        <option value="{{ $index }}" @selected($selected !== null && (string) $selected === (string) $index)>
                                            {{ $index + 1 }}. {{ trim((string) $cell) !== '' ? $cell : __('Column') . ' ' . ($index + 1) }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endforeach

                        <p class="hint" style="margin-top:10px">
                            {{ __('Dates and amounts are parsed automatically. When no single Amount column is mapped, the amount is derived from Credit minus Debit. Debit columns are stored as negative amounts.') }}
                        </p>

                        <div style="margin-top:18px;font-size:0.786rem;font-weight:800;letter-spacing:.08em;text-transform:uppercase;color:var(--muted,#5F7476)">
                            {{ __('File preview') }}
                        </div>
                        <div class="li-wrap" style="margin-top:8px">
                            <table>
                                <thead>
                                    <tr>
                                        @foreach($preview['header'] as $index => $cell)
                                            <th>{{ $index + 1 }}. {{ trim((string) $cell) !== '' ? $cell : __('Col') . ' ' . ($index + 1) }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($preview['samples'] as $row)
                                        <tr>
                                            @foreach($preview['header'] as $index => $cell)
                                                <td class="em" title="{{ isset($row[$index]) ? $row[$index] : '' }}">
                                                    {{ isset($row[$index]) ? mb_strimwidth($row[$index], 0, 40, '…') : '' }}
                                                </td>
                                            @endforeach
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="1"><div class="empty">{{ __('No preview rows available.') }}</div></td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                            <a href="{{ route('accounting.bank-reconciliation.import', $reconciliation->id) }}" class="btn ghost">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn cta">{{ __('Import Statement') }}</button>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
