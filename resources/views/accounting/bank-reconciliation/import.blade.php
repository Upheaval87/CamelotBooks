<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="br-head">
                <div>
                    <h1>{{ __('Import Bank Statement') }}</h1>
                    <div class="sub">
                        {{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}
                        @if($reconciliation->period_start && $reconciliation->period_end)
                            &middot; {{ $reconciliation->period_start->format('M d, Y') }} – {{ $reconciliation->period_end->format('M d, Y') }}
                        @endif
                    </div>
                </div>
                <div class="br-cluster">
                    <a href="{{ route('accounting.bank-reconciliation.workspace', $reconciliation->id) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('Back to Workspace') }}
                    </a>
                </div>
            </div>

            @if($errors->any())
                <div class="note-info" style="margin-bottom:16px">
                    <strong>{{ __('Import failed') }}:</strong>
                    <ul style="margin:6px 0 0 18px;list-style:disc">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card" style="max-width:1120px;margin:0 auto">
                <div class="card-h">
                    <h2>{{ __('Import Bank Statement') }}</h2>
                    <div class="right">
                        <div class="fmtchips">
                            <span class="fmt on">XLSX</span>
                            <span class="fmt on">CSV</span>
                            <span class="fmt" title="On the roadmap">OFX</span>
                            <span class="fmt" title="On the roadmap">QFX</span>
                            <span class="fmt" title="On the roadmap">MT940</span>
                            <span class="fmt" title="On the roadmap">CAMT.053</span>
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
                        Start with Excel and CSV. The engine architecture allows OFX, MT940 and CAMT.053 later without touching core accounting logic.
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

                    <div class="maprow">
                        <span class="src">{{ __('Date') }}</span><span class="arr">&rarr;</span>
                        <span class="map-to">{{ __('Transaction Date') }}</span>
                    </div>
                    <div class="maprow">
                        <span class="src">{{ __('Reference') }}</span><span class="arr">&rarr;</span>
                        <span class="map-to">{{ __('Reference') }}</span>
                    </div>
                    <div class="maprow">
                        <span class="src">{{ __('Description') }}</span><span class="arr">&rarr;</span>
                        <span class="map-to">{{ __('Description') }}</span>
                    </div>
                    <div class="maprow">
                        <span class="src">{{ __('Debit') }}</span><span class="arr">&rarr;</span>
                        <span class="map-to">{{ __('Withdrawal') }}</span>
                    </div>
                    <div class="maprow">
                        <span class="src">{{ __('Credit') }}</span><span class="arr">&rarr;</span>
                        <span class="map-to">{{ __('Deposit') }}</span>
                    </div>
                    <div class="maprow">
                        <span class="src">{{ __('Balance') }}</span><span class="arr">&rarr;</span>
                        <span class="map-to">{{ __('Balance') }}</span>
                    </div>
                    <p class="hint" style="margin-top:6px">{{ __('After uploading, you will be able to map your file columns to these system fields.') }}</p>

                    <form method="POST" action="{{ route('accounting.bank-reconciliation.import.preview', $reconciliation->id) }}" enctype="multipart/form-data">
                        @csrf
                        <div class="field" style="margin-top:14px">
                            <label for="statement_file">{{ __('Statement file') }}</label>
                            <input
                                id="statement_file"
                                type="file"
                                name="statement_file"
                                accept=".csv,.xlsx,.xls"
                                class="dropz"
                                required
                            />
                            @error('statement_file')<div class="err">{{ $message }}</div>@enderror
                            <div class="hint">Supported formats: CSV, XLSX, XLS (max 10 MB).</div>
                        </div>
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                            <a href="{{ route('accounting.bank-reconciliation.workspace', $reconciliation->id) }}" class="btn ghost">{{ __('Cancel') }}</a>
                            <button type="submit" class="btn cta">{{ __('Preview & Map Columns') }} &rarr;</button>
                        </div>
                    </form>
                </div>
            </div>

            <section class="card" style="margin-top:16px;max-width:1120px;margin-left:auto;margin-right:auto">
                <div class="card-h">
                    <h2>{{ __('Recent Imports') }}</h2>
                    <span class="n">{{ $reconciliation->imports->count() }} {{ __('file(s)') }}</span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:28%">{{ __('File') }}</th>
                                <th class="num" style="width:12%">{{ __('Lines') }}</th>
                                <th style="width:20%">{{ __('Imported') }}</th>
                                <th style="width:20%">{{ __('By') }}</th>
                                <th style="width:20%">{{ __('Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($reconciliation->imports->sortByDesc('id') as $import)
                                <tr>
                                    <td class="em" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap" title="{{ $import->filename }}">{{ $import->filename }}</td>
                                    <td class="numr">{{ $import->line_count }}</td>
                                    <td class="em">{{ $import->created_at?->format('M d, Y g:i A') ?? '—' }}</td>
                                    <td class="em">{{ $import->importedBy?->name ?? '—' }}</td>
                                    <td class="num"><span class="badge b-teal"><span class="bdot"></span>{{ __('Imported') }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5"><div class="empty">No statements imported yet.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
