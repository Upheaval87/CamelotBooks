<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="dp py-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="dp-head">
                <div>
                    <h1 class="dp-title">{{ __('Deposit') }} <span class="dp-mono">{{ $deposit->deposit_no }}</span></h1>
                    <p class="dp-sub">
                        {{ $deposit->deposit_date?->format('M d, Y') ?? '—' }}
                        · {{ $deposit->bankAccount?->name ?? '—' }}
                        @if($deposit->isPosted())
                            · {{ __('Posted') }} {{ $deposit->posted_at?->format('M d, Y H:i') ?? '' }}
                        @elseif($deposit->isVoid())
                            · {{ __('Voided') }} {{ $deposit->voided_at?->format('M d, Y H:i') ?? '' }}
                        @endif
                    </p>
                </div>
                <div class="dp-head-actions">
                    <a href="{{ route('accounting.banking.deposits') }}" class="dp-btn dp-btn--ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <div class="dp-kpis">
                <div class="dp-kpi">
                    <span class="dp-kpi-label">{{ __('Status') }}</span>
                    <span class="dp-kpi-val">
                        @if($deposit->isPosted())
                            <span class="dp-badge dp-badge--act"><span class="dp-dot"></span>{{ __('Posted') }}</span>
                        @elseif($deposit->isVoid())
                            <span class="dp-badge dp-badge--gra">{{ __('Void') }}</span>
                        @else
                            <span class="dp-badge dp-badge--warn">{{ __('Draft') }}</span>
                        @endif
                    </span>
                </div>
                <div class="dp-kpi">
                    <span class="dp-kpi-label">{{ __('Deposit Total') }}</span>
                    <span class="dp-kpi-val">{{ $cs }}{{ format_number($deposit->total) }}</span>
                </div>
                <div class="dp-kpi">
                    <span class="dp-kpi-label">{{ __('Receipts') }}</span>
                    <span class="dp-kpi-val">{{ $deposit->lines->count() }}</span>
                </div>
                <div class="dp-kpi">
                    <span class="dp-kpi-label">{{ __('Bank Account') }}</span>
                    <span class="dp-kpi-val">{{ $deposit->bankAccount?->name ?? '—' }}</span>
                </div>
            </div>

            @if($deposit->isVoid() && $deposit->void_reason)
                <div class="dp-alert" role="alert"><strong>{{ __('Void reason:') }}</strong> {{ $deposit->void_reason }}</div>
            @endif

            <div class="dp-shell">
                <div class="dp-main">
                    <div class="dp-card dp-card--list">
                        <div class="dp-card-head">
                            <h2 class="dp-card-title">{{ __('Receipts in this Deposit') }}</h2>
                        </div>
                        <div class="dp-tbl-wrap">
                            <table class="dp-tbl">
                                <thead>
                                    <tr>
                                        <th style="width:26%">{{ __('Reference') }}</th>
                                        <th style="width:46%">{{ __('Description') }}</th>
                                        <th style="width:28%" class="dp-right">{{ __('Amount') }} ({{ $cs }})</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($deposit->lines as $line)
                                        <tr>
                                            <td class="dp-mono">{{ $line->reference ?? ($line->salesReceipt?->receipt_number ?? '—') }}</td>
                                            <td class="dp-desc">{{ $line->description ?? '—' }}</td>
                                            <td class="dp-right dp-amt">{{ $cs }}{{ format_number($line->amount) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3"><div class="dp-empty">{{ __('No receipts in this deposit.') }}</div></td></tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="dp-tfoot">
                                        <td colspan="2" class="dp-right">{{ __('Total') }}</td>
                                        <td class="dp-right dp-amt">{{ $cs }}{{ format_number($deposit->total) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>

                    @if($deposit->journalEntry)
                        <div class="dp-card dp-card--list">
                            <div class="dp-card-head">
                                <h2 class="dp-card-title">{{ __('Journal Entry') }}</h2>
                            </div>
                            <div class="dp-card-body">
                                <p>{{ __('Reference:') }} <span class="dp-mono">{{ $deposit->journalEntry->journal_number }}</span></p>
                                <a href="{{ route('accounting.journal-entries.show', $deposit->journalEntry->id) }}" class="dp-link">{{ __('View the journal entry') }} →</a>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="dp-rail">
                    <div class="dp-railcard">
                        <div class="dp-rail-group">
                            <div class="dp-rail-label">{{ __('Actions') }}</div>
                            @can('deposits.void')
                                @if($deposit->isPosted())
                                    <form method="POST" action="{{ route('accounting.banking.deposits.void', $deposit->id) }}"
                                          @submit="return window.fbConfirmSubmit ? window.fbConfirmSubmit(event, 'Void deposit {{ $deposit->deposit_no }}? This reverses its journal entry and returns the receipts to undeposited.', { type: 'danger' }) : true">
                                        @csrf
                                        <div class="dp-field">
                                            <label class="dp-label" for="void_reason">{{ __('Void Reason') }}</label>
                                            <input id="void_reason" type="text" name="reason" class="dp-input" maxlength="500" placeholder="{{ __('Optional') }}" />
                                        </div>
                                        <button type="submit" class="dp-btn dp-btn--danger dp-btn--block">{{ __('Void Deposit') }}</button>
                                    </form>
                                @endif
                            @endcan
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</x-app-layout>
