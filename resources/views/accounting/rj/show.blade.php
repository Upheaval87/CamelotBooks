<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap">
                        <h1>{{ $template->name }}</h1>
                        @if($template->reference)
                            <span class="mono-chip">{{ $template->reference }}</span>
                        @endif
                        <span class="badge {{ $template->statusBadgeClass() }}"><span class="bdot"></span>{{ ucfirst($template->status) }}</span>
                    </div>
                    <div class="sub">{{ ucfirst(str_replace('_', ' ', $template->frequency)) }} · {{ $template->journal_type }} · Created {{ $template->created_at?->format('d M Y') }}</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.edit', $template) }}" class="btn btn-ghost btn-sm">✎ Edit</a>

                    <form method="POST" action="{{ route('accounting.rj.toggle', $template) }}" style="display:inline">
                        @csrf
                        @method('PATCH')
                        @if($template->status === 'active')
                            <button type="submit" class="btn btn-sec btn-sm">⏸ Pause</button>
                        @elseif($template->status === 'paused')
                            <button type="submit" class="btn btn-sec btn-sm">▶ Resume</button>
                        @endif
                    </form>

                    <form method="POST" action="{{ route('accounting.rj.duplicate', $template) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-sm">⧉ Duplicate</button>
                    </form>

                    @if($template->status === 'active')
                        <form method="POST" action="{{ route('accounting.rj.run-now', $template) }}" style="display:inline">
                            @csrf
                            <button type="submit" class="btn btn-cta btn-sm">▶ Run Now</button>
                        </form>
                    @endif

                    <form method="POST" action="{{ route('accounting.rj.test-run', $template) }}" style="display:inline">
                        @csrf
                        <button type="submit" class="btn btn-sec btn-sm">🧪 Test Run</button>
                    </form>

                    @if($recentRuns->where('is_test', false)->count() === 0)
                        <form method="POST" action="{{ route('accounting.rj.destroy', $template) }}" style="display:inline"
                              onsubmit="return fbConfirmSubmit(event, 'Delete this recurring journal? This cannot be undone.', {type:'danger'})">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger-o btn-sm">🗑 Delete</button>
                        </form>
                    @endif
                </div>
            </div>

            <div class="kpis" style="margin-bottom:16px">
                <div class="kpi hero">
                    <div class="l">Total Amount</div>
                    <div class="v">{{ number_format($template->total_amount, 2) }}</div>
                    <div class="n">{{ $template->currency ?? 'USD' }} per run</div>
                </div>
                <div class="kpi">
                    <div class="l">Generated Count</div>
                    <div class="v">{{ $template->generated_count }}</div>
                    <div class="n">successful generations</div>
                </div>
                <div class="kpi @if($template->failed_count > 0) red @endif">
                    <div class="l">Failed Count</div>
                    <div class="v">{{ $template->failed_count }}</div>
                    <div class="n">@if($template->failed_count > 0) requires attention @else no failures @endif</div>
                </div>
                <div class="kpi">
                    <div class="l">Next Run</div>
                    <div class="v">{{ $template->next_run_date?->format('d M Y') ?? '—' }}</div>
                    <div class="n">@if($template->next_run_date && $template->next_run_date->isFuture()) in {{ $template->next_run_date->diffForHumans() }} @else no upcoming run @endif</div>
                </div>
            </div>

            <div class="card">
                <div class="card-h">Template Details</div>
                <div class="card-sec">
                    <div class="g3">
                        <div class="field">
                            <label>Journal Name</label>
                            <div class="ci">{{ $template->name }}</div>
                        </div>
                        <div class="field">
                            <label>Reference</label>
                            <div class="ci mono">{{ $template->reference ?? '—' }}</div>
                        </div>
                        <div class="field">
                            <label>Description</label>
                            <div class="ci">{{ $template->description ?: '—' }}</div>
                        </div>
                        <div class="field">
                            <label>Type</label>
                            <div class="ci"><span class="tchip {{ $template->typeChipClass() }}">{{ $template->journal_type }}</span></div>
                        </div>
                        <div class="field">
                            <label>Frequency</label>
                            <div class="ci">{{ ucfirst(str_replace('_', ' ', $template->frequency)) }}</div>
                        </div>
                        <div class="field">
                            <label>Start Date</label>
                            <div class="ci">{{ $template->start_date?->format('d M Y') ?? '—' }}</div>
                        </div>
                        <div class="field">
                            <label>End Date</label>
                            <div class="ci">{{ $template->end_date?->format('d M Y') ?? 'No end date' }}</div>
                        </div>
                        <div class="field">
                            <label>Day of Month</label>
                            <div class="ci">{{ $template->day_of_month ?? '—' }}</div>
                        </div>
                        <div class="field">
                            <label>Generation Mode</label>
                            <div class="ci"><span class="tchip">{{ str_replace('_', ' ', $template->generation_mode) }}</span></div>
                        </div>
                        <div class="field">
                            <label>Currency</label>
                            <div class="ci mono">{{ $template->currency ?? 'USD' }}</div>
                        </div>
                        <div class="field">
                            <label>Email Notification</label>
                            <div class="ci">{{ str_replace('_', ' ', $template->email_notification ?? 'none') }}</div>
                        </div>
                        <div class="field">
                            <label>Status</label>
                            <div class="ci"><span class="badge {{ $template->statusBadgeClass() }}"><span class="bdot"></span>{{ ucfirst($template->status) }}</span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-h">Journal Lines</div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Description</th>
                                <th class="num">Debit</th>
                                <th class="num">Credit</th>
                                <th>Department</th>
                                <th>Cost Centre</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($template->templateLines as $line)
                                <tr>
                                    <td>
                                        <span class="mono" style="font-weight:700">{{ $line->account?->code ?? '—' }}</span>
                                        <span style="margin-left:6px;color:var(--muted)">{{ $line->account?->name ?? '' }}</span>
                                    </td>
                                    <td class="em">{{ $line->memo ?? '—' }}</td>
                                    <td class="numr">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                                    <td class="numr">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                                    <td class="em">{{ $line->branch?->name ?? '—' }}</td>
                                    <td class="em">{{ $line->costCenter?->name ?? '—' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="em" style="text-align:center;padding:24px">No lines defined.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="2">Totals</td>
                                <td class="numr">{{ number_format($template->templateLines->sum('debit'), 2) }}</td>
                                <td class="numr">{{ number_format($template->templateLines->sum('credit'), 2) }}</td>
                                <td colspan="2">
                                    @php
                                        $totalDebit = $template->templateLines->sum('debit');
                                        $totalCredit = $template->templateLines->sum('credit');
                                    @endphp
                                    @if(abs($totalDebit - $totalCredit) < 0.01)
                                        <span class="okchip ok">✓ Balanced</span>
                                    @else
                                        <span class="okchip bad">Out {{ number_format(abs($totalDebit - $totalCredit), 2) }}</span>
                                    @endif
                                </td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-h">Recent Generated Journals</div>
                @if($recentRuns->isEmpty())
                    <div class="card-sec">
                        <div class="em" style="text-align:center;padding:32px">No journals have been generated from this template yet.</div>
                    </div>
                @else
                    <div class="li-wrap" style="margin-top:0">
                        <table>
                            <thead>
                                <tr>
                                    <th>Journal №</th>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th class="num">Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentRuns as $run)
                                    <tr>
                                        <td class="mono" style="font-weight:700">{{ $run->reference }}</td>
                                        <td class="em">{{ $run->run_date?->format('d M Y') ?? '—' }}</td>
                                        <td class="mono">{{ $run->journalEntry?->journal_number ?? '—' }}</td>
                                        <td class="numr bold">{{ number_format($run->total_debit, 2) }}</td>
                                        <td><span class="badge {{ $run->statusBadgeClass() }}"><span class="bdot"></span>{{ ucfirst(str_replace('_', ' ', $run->status)) }}</span></td>
                                        <td>
                                            <div class="row-act">
                                                @if($run->status === 'pending_approval')
                                                    <form method="POST" action="{{ route('accounting.rj.approve-run', $run) }}" style="display:inline">
                                                        @csrf
                                                        <button type="submit" class="ibtn" title="Approve" style="color:var(--green)">✓</button>
                                                    </form>
                                                    <div class="more">
                                                        <button class="ibtn" onclick="this.parentElement.classList.toggle('open')">⋯</button>
                                                        <div class="more-menu">
                                                            <form method="POST" action="{{ route('accounting.rj.reject-run', $run) }}" style="display:inline" class="reject-form">
                                                                @csrf
                                                                <div style="padding:8px 12px">
                                                                    <input type="text" name="reason" class="input" placeholder="Rejection reason" required style="margin-bottom:6px">
                                                                    <button type="submit" class="btn btn-danger-o btn-xs">Reject</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @endif
                                                @if($run->journalEntry)
                                                    <a href="{{ route('accounting.journal-entries.show', $run->journalEntry) }}" class="ibtn" title="View Journal Entry">👁</a>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-h">Audit Trail</div>
                <div class="card-sec">
                    <div class="audit">
                        @forelse($recentHistory as $entry)
                            <div class="arow">
                                <div class="when">{{ $entry->happened_at?->format('d M H:i') ?? '—' }}</div>
                                <div class="who">{{ $entry->actor?->name ?? 'Engine' }}</div>
                                <div class="what">@html($entry->description)</div>
                            </div>
                        @empty
                            <div class="em" style="text-align:center;padding:24px">No history recorded yet.</div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
