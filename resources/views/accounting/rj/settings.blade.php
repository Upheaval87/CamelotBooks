<x-app-layout>
    <div class="rj-wrap rj-rebuild">
        <div class="wrap">
            <div class="page-head">
                <div>
                    <h1>Recurring Journals Settings</h1>
                    <div class="sub">Configure numbering, approvals, notifications and defaults for the automation engine.</div>
                </div>
                <div style="display:flex;gap:10px;flex-wrap:wrap">
                    <a href="{{ route('accounting.rj.dashboard') }}" class="btn btn-ghost btn-sm">← Back to Dashboard</a>
                </div>
            </div>

            <form method="POST" action="{{ route('accounting.rj.settings.update') }}">
                @csrf
                @method('PUT')

                <section class="card">
                    <div class="card-h" style="display:flex;align-items:center;justify-content:space-between;padding:16px 24px;border-bottom:1px solid var(--line,#E2ECEC)">
                        <h2 style="font-size:14px;font-weight:800;color:var(--ink);margin:0">Recurring Journals Settings</h2>
                        <span class="fmt">admin</span>
                    </div>

                    <div class="card-sec">
                        <div class="g3">
                            <div class="field">
                                <label for="numbering_pattern">Journal Numbering</label>
                                <input class="input" type="text" id="numbering_pattern" name="numbering_pattern" value="{{ old('numbering_pattern', $settings->numbering_pattern ?? 'RJ-{YYYY}-{NNN}') }}">
                                <span class="em" style="display:block;margin-top:4px;font-size:11px">Pattern for auto-generated journal numbers (e.g. RJ-{YYYY}-{NNN})</span>
                            </div>

                            <div class="field">
                                <label for="approval_required">Approval Required</label>
                                <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
                                    <input type="hidden" name="approval_required" value="0">
                                    <input type="checkbox" id="approval_required" name="approval_required" value="1" @if(old('approval_required', $settings->approval_required ?? 0)) checked @endif style="width:18px;height:18px">
                                    <span class="em">Require approval before posting</span>
                                </div>
                            </div>

                            <div class="field">
                                <label for="approval_threshold">Approval Threshold</label>
                                <input class="input" type="number" id="approval_threshold" name="approval_threshold" value="{{ old('approval_threshold', $settings->approval_threshold ?? 0) }}" min="0" step="0.01">
                                <span class="em" style="display:block;margin-top:4px;font-size:11px">Auto-approve entries below this amount (0 = always require approval)</span>
                            </div>

                            <div class="field">
                                <label for="notification_email">Notifications</label>
                                <select class="input" id="notification_email" name="notification_email">
                                    <option value="before_posting" @if(old('notification_email', $settings->notification_email ?? 'before_posting') === 'before_posting') selected @endif>Before Posting</option>
                                    <option value="after_posting" @if(old('notification_email', $settings->notification_email ?? '') === 'after_posting') selected @endif>After Posting</option>
                                    <option value="none" @if(old('notification_email', $settings->notification_email ?? '') === 'none') selected @endif>None</option>
                                </select>
                                <span class="em" style="display:block;margin-top:4px;font-size:11px">When to send email notifications</span>
                            </div>

                            <div class="field">
                                <label for="block_locked_periods">Block Locked Periods</label>
                                <div style="display:flex;align-items:center;gap:10px;margin-top:8px">
                                    <input type="hidden" name="block_locked_periods" value="0">
                                    <input type="checkbox" id="block_locked_periods" name="block_locked_periods" value="1" @if(old('block_locked_periods', $settings->block_locked_periods ?? 0)) checked @endif style="width:18px;height:18px">
                                    <span class="em">Prevent posting to locked accounting periods</span>
                                </div>
                            </div>

                            <div class="field">
                                <label for="default_suspense_account_id">Default Suspense Account</label>
                                <select class="input" id="default_suspense_account_id" name="default_suspense_account_id">
                                    <option value="">— None —</option>
                                    @foreach($accounts as $account)
                                        <option value="{{ $account->id }}" @if(old('default_suspense_account_id', $settings->default_suspense_account_id ?? '') == $account->id) selected @endif>{{ $account->code }} — {{ $account->name }}</option>
                                    @endforeach
                                </select>
                                <span class="em" style="display:block;margin-top:4px;font-size:11px">Fallback account for suspense entries</span>
                            </div>
                        </div>
                    </div>

                    <div class="card-sec" style="border-top:1px solid var(--line,#E2ECEC);padding:16px 24px;display:flex;justify-content:flex-end">
                        <button type="submit" class="btn btn-cta btn-sm">💾 Save Settings</button>
                    </div>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
