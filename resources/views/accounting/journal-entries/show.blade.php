<x-app-layout>
    <div class="je-wrap">
        <div class="je-crumbs">
            <a href="{{ route('accounting.journal-entries.index') }}">Journals</a>
            <span>›</span>
            <span class="here">{{ $journalEntry->journal_number }}</span>
        </div>

        <div class="je-page-head">
            <div style="display:flex;align-items:center;gap:10px">
                <h1>Journal — {{ $journalEntry->journal_number }}</h1>
                @php
                    $statusClass = match($journalEntry->status) {
                        'draft' => 'je-b-draft',
                        'pending_approval' => 'je-b-pend',
                        'approved' => 'je-b-post',
                        'posted' => 'je-b-post',
                        'reversed' => 'je-b-rev',
                        default => 'je-b-draft',
                    };
                    $statusLabel = match($journalEntry->status) {
                        'pending_approval' => 'Pending',
                        default => ucfirst($journalEntry->status),
                    };
                @endphp
                <span class="je-badge {{ $statusClass }}"><span class="bdot"></span>{{ $statusLabel }}</span>
            </div>
            <div style="display:flex;gap:10px">
                @if($journalEntry->isDraft())
                <a href="{{ route('accounting.journal-entries.edit', $journalEntry) }}" class="je-btn je-btn-ghost">✎ Edit</a>
                @endif
                @if($journalEntry->isPosted())
                <form method="POST" action="{{ route('accounting.journal-entries.reverse', $journalEntry) }}" style="display:inline" onsubmit="return fbConfirmSubmit(event, 'Create a reversal entry for {{ $journalEntry->journal_number }}?', {type:'danger'})">
                    @csrf
                    <button type="submit" class="je-btn je-btn-ghost">↩ Reverse</button>
                </form>
                @endif
            </div>
        </div>

        <div class="je-card">
            <div class="je-card-h">
                <h2>{{ $journalEntry->memo ?: $journalEntry->journal_number }}</h2>
                <div class="right">
                    <span class="je-tchip">{{ $journalEntry->is_adjusting_entry ? 'Adjusting' : 'General' }}</span>
                    <span class="je-tchip">{{ $journalEntry->date->format('d M Y') }}</span>
                    @if($journalEntry->reference)
                    <span class="je-tchip">{{ $journalEntry->reference }}</span>
                    @endif
                </div>
            </div>
            <div class="je-li-wrap">
                <table class="je-table">
                    <thead>
                        <tr>
                            <th style="width:30%">Account</th>
                            <th style="width:30%">Description</th>
                            <th class="num" style="width:13%">Debit</th>
                            <th class="num" style="width:13%">Credit</th>
                            <th style="width:14%">Cost Centre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($journalEntry->lines as $line)
                        <tr>
                            <td>
                                <span class="je-acct">
                                    <span class="code">{{ $line->account?->code ?? '—' }}</span>
                                    <span class="name">{{ $line->account?->name ?? '' }}</span>
                                </span>
                            </td>
                            <td class="je-em">{{ $line->memo ?? '—' }}</td>
                            <td class="num">{{ $line->debit > 0 ? number_format($line->debit, 2) : '—' }}</td>
                            <td class="num">{{ $line->credit > 0 ? number_format($line->credit, 2) : '—' }}</td>
                            <td class="je-em">{{ $line->costCenter?->code ?? '—' }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="je-em" style="text-align:center;padding:24px">No lines.</td>
                        </tr>
                        @endforelse
                    </tbody>
                    <tfoot>
                        <tr>
                            <td colspan="2">Totals</td>
                            <td class="num">{{ number_format($journalEntry->total_debit, 2) }}</td>
                            <td class="num">{{ number_format($journalEntry->total_credit, 2) }}</td>
                            <td>
                                @if(abs($journalEntry->total_debit - $journalEntry->total_credit) < 0.01)
                                <span class="je-b-ok">✓ Balanced</span>
                                @else
                                <span class="je-okchip bad">Out {{ number_format(abs($journalEntry->total_debit - $journalEntry->total_credit), 2) }}</span>
                                @endif
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
            <div class="je-foot-meta">
                @if($journalEntry->createdBy)
                Created by {{ $journalEntry->createdBy->name }} · {{ $journalEntry->created_at->format('d M Y H:i') }}
                @endif
                @if($journalEntry->postedByUser)
                · Posted by {{ $journalEntry->postedByUser->name }} · {{ $journalEntry->posted_at?->format('d M Y H:i') }}
                @endif
                · Source: {{ $journalEntry->source_module ?: 'Manual entry' }}
            </div>
        </div>
    </div>
</x-app-layout>
