<x-app-layout>
<div class="rv-wrap py-6">
    <div class="rv-head">
        <div>
            <h1 class="rv-title">{{ __('Authorization Rules & Audit') }}</h1>
            <p class="rv-sub">{{ __('Manage approval thresholds and review the complete audit trail.') }}</p>
        </div>
    </div>

    <div class="rv-kpis">
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Active Rules') }}</span>
            <span class="rv-kpi-value">{{ $stats['activeRules'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Multi-Step Required') }}</span>
            <span class="rv-kpi-value rv-kpi-value--amber">{{ $stats['multiStepRequired'] }}</span>
        </div>
        <div class="rv-kpi">
            <span class="rv-kpi-label">{{ __('Total Reversals (All Time)') }}</span>
            <span class="rv-kpi-value">{{ $stats['totalReversals'] }}</span>
        </div>
    </div>

    <div class="rv-shell">
        <div>
            {{-- Tabs: Rules / Audit --}}
            <div class="rv-tabs">
                <a href="{{ route('accounting.reversals.rules') }}" class="rv-tab is-active">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    {{ __('Rules') }}
                </a>
                <a href="{{ route('accounting.reversals.audit') }}" class="rv-tab">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                    {{ __('Audit Trail') }}
                </a>
            </div>

            {{-- New Rule Form --}}
            <div class="rv-card" style="margin-top:1rem">
                    <div class="rv-card-head">
                        <span class="rv-card-title">{{ __('Create Authorization Rule') }}</span>
                    </div>
                    <form method="POST" action="{{ route('accounting.reversals.rules.store') }}" class="rv-form">
                        @csrf
                        <div class="rv-detail">
                            <div class="rv-field">
                                <label class="rv-label">{{ __('Minimum Amount') }} *</label>
                                <input type="number" name="minimum_amount" value="{{ old('minimum_amount') }}" step="0.01" min="0" class="rv-input" required>
                                @error('minimum_amount')<span class="rv-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="rv-field">
                                <label class="rv-label">{{ __('Maximum Amount') }}</label>
                                <input type="number" name="maximum_amount" value="{{ old('maximum_amount') }}" step="0.01" min="0" class="rv-input" placeholder="{{ __('Unlimited') }}">
                                @error('maximum_amount')<span class="rv-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="rv-field">
                                <label class="rv-label">{{ __('Required Approvals') }} *</label>
                                <input type="number" name="required_approvals" value="{{ old('required_approvals', 1) }}" min="1" max="10" class="rv-input" required>
                                @error('required_approvals')<span class="rv-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="rv-field">
                                <label class="rv-label">{{ __('Approver Role') }} *</label>
                                <select name="approver_role" class="rv-select" required>
                                    <option value="">{{ __('Select role...') }}</option>
                                    @foreach($roles as $key => $label)
                                        <option value="{{ $key }}" {{ old('approver_role') === $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                @error('approver_role')<span class="rv-error">{{ $message }}</span>@enderror
                            </div>
                        </div>
                        <div style="display:flex;gap:.75rem;margin-top:1rem">
                            <button type="submit" class="rv-btn rv-btn--sec">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                                {{ __('Create Rule') }}
                            </button>
                        </div>
                    </form>
                </div>

                {{-- Existing Rules --}}
                <div class="rv-table-wrap" style="margin-top:1rem">
                    @if($rules->count() > 0)
                        <table class="rv-table">
                            <thead>
                                <tr>
                                    <th>{{ __('Amount Range') }}</th>
                                    <th>{{ __('Required Approvals') }}</th>
                                    <th>{{ __('Approver Role') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th class="rv-right">{{ __('Actions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($rules as $rule)
                                    <tr>
                                        <td class="rv-mono">{{ $rule->amountRangeLabel() }}</td>
                                        <td>{{ $rule->required_approvals }}</td>
                                        <td>
                                            <span class="rv-chip">{{ ucfirst(str_replace('_', ' ', $rule->approver_role)) }}</span>
                                        </td>
                                        <td>
                                            <span class="rv-badge {{ $rule->active ? 'rv-badge--approved' : 'rv-badge--reversed' }}">
                                                <span class="rv-dot"></span>
                                                {{ $rule->active ? __('Active') : __('Inactive') }}
                                            </span>
                                        </td>
                                        <td class="rv-right">
                                            <form method="POST" action="{{ route('accounting.reversals.rules.toggle', $rule->id) }}" style="display:inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="rv-btn rv-btn--ghost rv-btn--xs">
                                                    {{ $rule->active ? __('Deactivate') : __('Activate') }}
                                                </button>
                                            </form>
                                            <form method="POST" action="{{ route('accounting.reversals.rules.delete', $rule->id) }}" style="display:inline" data-fb-confirm="return confirm('Delete this rule?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rv-btn rv-btn--danger-o rv-btn--xs">{{ __('Delete') }}</button>
                                            </form>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    @else
                        <div class="rv-empty">
                            <svg width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                            <span class="rv-empty-title">{{ __('No rules configured') }}</span>
                            <span class="rv-empty-text">{{ __('Create an authorization rule above to set approval thresholds.') }}</span>
                        </div>
                    @endif
                </div>
        </div>

        <aside class="rv-rail">
            <div class="rv-rail-sec">
                <div class="rv-rail-head">
                    <span class="rv-rail-ic">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </span>
                    <span class="rv-rail-title">{{ __('Quick Nav') }}</span>
                </div>
                <div class="rv-vlist">
                    <a href="{{ route('accounting.reversals.rules') }}" class="rv-vitem is-active">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06"/></svg></span>
                        {{ __('Rules') }}
                    </a>
                    <a href="{{ route('accounting.reversals.audit', ['tab' => 'audit']) }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg></span>
                        {{ __('Audit') }}
                    </a>
                    <a href="{{ route('accounting.reversals.auth') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg></span>
                        {{ __('Authorization Dashboard') }}
                    </a>
                    <a href="{{ route('accounting.reversals.index') }}" class="rv-vitem">
                        <span class="ic"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/></svg></span>
                        {{ __('Reversal Dashboard') }}
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>
</x-app-layout>
