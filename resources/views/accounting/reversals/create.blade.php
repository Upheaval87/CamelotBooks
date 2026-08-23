<x-app-layout>
<div class="rv-wrap py-6">
    {{-- Page Head --}}
    <div class="rv-head">
        <div>
            <h1 class="rv-title">{{ __('Request Transaction Reversal') }}</h1>
            <p class="rv-sub">{{ __('Search posted transactions and submit reversal requests for authorization.') }}</p>
        </div>
        <div class="rv-head-actions">
            <a href="{{ route('accounting.reversals.index') }}" class="rv-btn rv-btn--ghost">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="15 18 9 12 15 6"/></svg>
                {{ __('My Requests') }}
            </a>
        </div>
    </div>

    <div class="rv-shell">
        {{-- Main Content --}}
        <div>
            {{-- Filter Bar --}}
            <form method="GET" action="{{ route('accounting.reversals.create') }}" class="rv-filters">
                <div class="rv-field" style="min-width:10rem">
                    <label class="rv-label">{{ __('Date From') }}</label>
                    <input type="date" name="date_from" value="{{ request('date_from') }}" class="rv-input">
                </div>
                <div class="rv-field" style="min-width:10rem">
                    <label class="rv-label">{{ __('Date To') }}</label>
                    <input type="date" name="date_to" value="{{ request('date_to') }}" class="rv-input">
                </div>
                <div class="rv-field" style="min-width:10rem">
                    <label class="rv-label">{{ __('Type') }}</label>
                    <select name="type" class="rv-select">
                        <option value="">{{ __('All Types') }}</option>
                        @foreach($transactionTypes as $key => $label)
                            <option value="{{ $key }}" {{ request('type') === $key ? 'selected' : '' }}>{{ __($label) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="rv-field">
                    <label class="rv-label">{{ __('Search') }}</label>
                    <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ __('Journal #, memo...') }}" class="rv-input">
                </div>
                <div style="display:flex;gap:.5rem;align-items:flex-end;padding-bottom:1px">
                    <button type="submit" class="rv-btn rv-btn--sec rv-btn--sm">{{ __('Search') }}</button>
                    <a href="{{ route('accounting.reversals.create') }}" class="rv-btn rv-btn--ghost rv-btn--sm">{{ __('Clear') }}</a>
                </div>
            </form>

            {{-- Selected Transaction Preview --}}
            @if($selectedJE)
                <div class="rv-card" style="margin-top:1rem">
                    <div class="rv-card-head">
                        <span class="rv-card-title">{{ __('Transaction Selected') }}</span>
                        <span class="rv-mono">{{ $selectedJE->journal_number }}</span>
                    </div>

                    <div class="rv-detail" style="margin-bottom:1rem">
                        <div class="rv-detail-item">
                            <span class="rv-detail-label">{{ __('Date') }}</span>
                            <span class="rv-detail-value">{{ $selectedJE->date?->format('d M Y') }}</span>
                        </div>
                        <div class="rv-detail-item">
                            <span class="rv-detail-label">{{ __('Status') }}</span>
                            <span class="rv-badge {{ $selectedJE->isPosted() ? 'rv-badge--posted' : 'rv-badge--reversed' }}">
                                <span class="rv-dot"></span>
                                {{ ucfirst($selectedJE->status) }}
                            </span>
                        </div>
                        <div class="rv-detail-item">
                            <span class="rv-detail-label">{{ __('Source Module') }}</span>
                            <span class="rv-detail-value">{{ ucfirst(str_replace('_', ' ', $selectedJE->source_module ?? 'journal')) }}</span>
                        </div>
                        <div class="rv-detail-item">
                            <span class="rv-detail-label">{{ __('Created By') }}</span>
                            <span class="rv-detail-value">{{ $selectedJE->createdBy?->name ?? '—' }}</span>
                        </div>
                    </div>

                    {{-- Line Items --}}
                    <div class="rv-table-wrap">
                        <table class="rv-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Account') }}</th>
                                    <th class="rv-right">{{ __('Debit') }}</th>
                                    <th class="rv-right">{{ __('Credit') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($selectedJE->lines as $line)
                                    <tr>
                                        <td>
                                            <span class="rv-mono">{{ $line->account?->code }}</span>
                                            {{ $line->account?->name }}
                                            @if($line->memo)
                                                <br><span class="rv-mono" style="font-size:.75rem;color:var(--faint)">{{ Str::limit($line->memo, 50) }}</span>
                                            @endif
                                        </td>
                                        <td class="rv-right rv-numr">{{ $line->debit > 0 ? number_format($line->debit, 2) : '' }}</td>
                                        <td class="rv-right rv-numr">{{ $line->credit > 0 ? number_format($line->credit, 2) : '' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr style="font-weight:700;background:rgba(244,248,248,.55)">
                                    <td>{{ __('Total') }}</td>
                                    <td class="rv-right rv-numr">{{ number_format($selectedJE->total_debit, 2) }}</td>
                                    <td class="rv-right rv-numr">{{ number_format($selectedJE->total_credit, 2) }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>

                    {{-- Reversal Form --}}
                    @if($selectedJE->isPosted())
                        <form method="POST" action="{{ route('accounting.reversals.store') }}" style="margin-top:1.25rem">
                            @csrf
                            <input type="hidden" name="journal_entry_id" value="{{ $selectedJE->id }}">

                            <div class="rv-detail" style="margin-bottom:1rem">
                                <div class="rv-field">
                                    <label class="rv-label">{{ __('Reversal Date') }} *</label>
                                    <input type="date" name="reversal_date" value="{{ old('reversal_date', now()->format('Y-m-d')) }}" class="rv-input" required>
                                </div>
                                <div class="rv-field">
                                    <label class="rv-label">{{ __('Reversal Method') }} *</label>
                                    <select name="reversal_method" class="rv-select" required>
                                        <option value="full" {{ old('reversal_method') === 'full' ? 'selected' : '' }}>{{ __('Full Reversal') }}</option>
                                        <option value="partial" {{ old('reversal_method') === 'partial' ? 'selected' : '' }}>{{ __('Partial Reversal') }}</option>
                                    </select>
                                </div>
                                <div class="rv-field" id="partialAmountField" style="display:none">
                                    <label class="rv-label">{{ __('Partial Amount') }}</label>
                                    <input type="number" name="partial_amount" value="{{ old('partial_amount') }}" step="0.01" min="0" class="rv-input">
                                </div>
                            </div>

                            <div class="rv-field" style="margin-bottom:1rem">
                                <label class="rv-label">{{ __('Reversal Reason') }} *</label>
                                <textarea name="reason" class="rv-textarea" required minlength="10" placeholder="{{ __('Provide a detailed reason for the reversal request (min 10 characters)...') }}">{{ old('reason') }}</textarea>
                                @error('reason')<span class="rv-error">{{ $message }}</span>@enderror
                            </div>

                            <div style="display:flex;gap:.75rem">
                                <button type="submit" class="rv-btn rv-btn--cta">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="1 4 1 10 7 10"/><path d="M3.51 15a9 9 0 1 0 2.13-9.36L1 10"/></svg>
                                    {{ __('Submit Reversal Request') }}
                                </button>
                                <a href="{{ route('accounting.reversals.create') }}" class="rv-btn rv-btn--ghost">{{ __('Cancel') }}</a>
                            </div>
                        </form>
                    @endif
                </div>
            @endif

            {{-- Search Results --}}
            @if(!$selectedJE && $transactions->count() > 0)
                <div class="rv-table-wrap" style="margin-top:1rem">
                    <table class="rv-table">
                        <thead>
                            <tr>
                                <th>{{ __('Journal #') }}</th>
                                <th>{{ __('Date') }}</th>
                                <th>{{ __('Description') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th class="rv-right">{{ __('Amount') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="rv-right">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($transactions as $je)
                                <tr>
                                    <td>
                                        <a href="{{ route('accounting.reversals.create', ['select' => $je->id]) }}" class="rv-table--link">{{ $je->journal_number }}</a>
                                    </td>
                                    <td>{{ $je->date?->format('d M Y') }}</td>
                                    <td>{{ Str::limit($je->memo ?? '—', 40) }}</td>
                                    <td><span class="rv-mono">{{ ucfirst(str_replace('_', ' ', $je->source_module ?? 'journal')) }}</span></td>
                                    <td class="rv-right rv-numr">{{ number_format($je->total_debit, 2) }}</td>
                                    <td>
                                        <span class="rv-badge {{ $je->isPosted() ? 'rv-badge--posted' : 'rv-badge--reversed' }}">
                                            <span class="rv-dot"></span>
                                            {{ ucfirst($je->status) }}
                                        </span>
                                    </td>
                                    <td class="rv-right">
                                        <a href="{{ route('accounting.reversals.create', ['select' => $je->id]) }}" class="rv-btn rv-btn--sec rv-btn--xs">{{ __('Select') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div style="margin-top:1rem">{{ $transactions->withQueryString()->links() }}</div>
            @endif

            @if(!$selectedJE && $transactions->isEmpty())
                <div class="rv-card" style="margin-top:1rem">
                    <div class="rv-empty">
                        <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                        <span class="rv-empty-title">{{ __('No transactions found') }}</span>
                        <span class="rv-empty-text">{{ __('Adjust your filters or search criteria to find posted transactions eligible for reversal.') }}</span>
                    </div>
                </div>
            @endif
        </div>

        {{-- Rail --}}
        <aside class="rv-rail">
            <div class="rv-rail-sec">
                <div class="rv-rail-head">
                    <span class="rv-rail-ic">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </span>
                    <span class="rv-rail-title">{{ __('Quick Nav') }}</span>
                </div>
                <div class="rv-vlist">
                    <a href="{{ route('accounting.reversals.create') }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
                        {{ __('Search') }}
                    </a>
                    <a href="{{ route('accounting.reversals.index') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg></span>
                        {{ __('My Requests') }}
                    </a>
                    <a href="{{ route('accounting.reversals.index') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
                        {{ __('Reversal History') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const method = document.querySelector('[name="reversal_method"]');
    const partial = document.getElementById('partialAmountField');
    if (method && partial) {
        const toggle = () => partial.style.display = method.value === 'partial' ? '' : 'none';
        method.addEventListener('change', toggle);
        toggle();
    }
});
</script>
@endpush
</x-app-layout>
