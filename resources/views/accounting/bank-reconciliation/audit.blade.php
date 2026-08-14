<x-app-layout>
    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sticky-head">
                <div>
                    <h1>{{ __('Audit Trail') }}</h1>
                    <div class="sub">
                        {{ $reconciliation->bankAccount?->code }} — {{ $reconciliation->bankAccount?->name }}
                        &middot; {{ __('Reconciliation') }} #{{ $reconciliation->id }}
                    </div>
                </div>
                <div class="tbtns">
                    <a href="{{ route('accounting.bank-reconciliation.show', $reconciliation->id) }}" class="btn ghost">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                        {{ __('Back') }}
                    </a>
                </div>
            </div>

            <section class="card card-sec">
                <div class="sec-head">
                    <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z"/></svg></span>
                    <h2>{{ __('Activity') }}</h2>
                    <span class="chip-t">{{ $auditLogs->count() }} {{ __('events') }}</span>
                    <span class="rule"></span>
                </div>
                <div class="li-wrap" style="margin-top:0">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:16%">{{ __('Date / Time') }}</th>
                                <th style="width:18%">{{ __('User') }}</th>
                                <th style="width:18%">{{ __('Action') }}</th>
                                <th>{{ __('Details') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                                <tr>
                                    <td class="mono em">{{ $log->created_at?->format('M d, Y g:i:s A') ?? '—' }}</td>
                                    <td class="em">{{ $log->user?->name ?? '—' }}</td>
                                    <td>
                                        <span class="badge
                                            @if(in_array($log->action, ['approved','completed'], true)) b-mint
                                            @elseif(in_array($log->action, ['reversed'], true)) b-red
                                            @elseif(in_array($log->action, ['matched','statement_imported'], true)) b-teal
                                            @elseif(in_array($log->action, ['ready_for_review'], true)) b-draft
                                            @else b-gray
                                            @endif"><span class="bdot"></span>{{ \App\Models\ReconciliationAuditLog::actionLabel($log->action) }}</td>
                                    <td class="em" style="white-space:normal">
                                        @if(!empty($log->details))
                                            <span style="color:var(--muted,#5F7476)">
                                                @foreach($log->details as $key => $value)
                                                    <strong>{{ ucwords(str_replace('_', ' ', $key)) }}:</strong>
                                                    {{ is_array($value) ? json_encode($value) : $value }}&nbsp;
                                                @endforeach
                                            </span>
                                        @else
                                            —
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4"><div class="empty">No activity recorded.</div></td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </section>

        </div>
    </div>
</x-app-layout>
