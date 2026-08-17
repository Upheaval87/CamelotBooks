<x-app-layout>
    <div class="bu-wrap">
        <div class="bu-page-head">
            <div>
                <h1>Budget Settings</h1>
                <div class="sub">Global budgeting configuration for your company.</div>
            </div>
        </div>
        <x-budgeting-subnav active-tab="settings" />

        <form method="POST" action="{{ route('accounting.budgets.settings') }}" id="budget-settings-form">
            @csrf
            @method('PUT')

            <div class="bu-g2" style="align-items:start">
                <div class="bu-card">
                    <div class="bu-card-h"><h2>Budget Configuration</h2></div>
                    <div class="bu-pad">
                        <div class="bu-g2">
                            <div class="bu-f">
                                <label>Default Budget Period</label>
                                <select class="in" name="default_period">
                                    <option value="annual" {{ ($settings['default_period'] ?? '') === 'annual' ? 'selected' : '' }}>Annual</option>
                                    <option value="quarterly" {{ ($settings['default_period'] ?? '') === 'quarterly' ? 'selected' : '' }}>Quarterly</option>
                                    <option value="monthly" {{ ($settings['default_period'] ?? '') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                            <div class="bu-f">
                                <label>Distribution Method</label>
                                <select class="in" name="distribution_method">
                                    <option value="even" {{ ($settings['distribution_method'] ?? '') === 'even' ? 'selected' : '' }}>Even Distribution</option>
                                    <option value="weighted" {{ ($settings['distribution_method'] ?? '') === 'weighted' ? 'selected' : '' }}>Weighted</option>
                                    <option value="seasonal" {{ ($settings['distribution_method'] ?? '') === 'seasonal' ? 'selected' : '' }}>Seasonal</option>
                                </select>
                            </div>
                            <div class="bu-f">
                                <label>Variance Warning Threshold (%)</label>
                                <input class="in" name="warn_threshold" type="number" value="{{ $settings['warn_threshold'] ?? 10 }}">
                            </div>
                            <div class="bu-f">
                                <label>Variance Critical Threshold (%)</label>
                                <input class="in" name="crit_threshold" type="number" value="{{ $settings['crit_threshold'] ?? 25 }}">
                            </div>
                            <div class="bu-f">
                                <label>Approval Required Above (Amount)</label>
                                <input class="in" name="approval_above" type="number" value="{{ $settings['approval_above'] ?? 0 }}">
                            </div>
                            <div class="bu-f">
                                <label>Budget Lock After Approval</label>
                                <select class="in" name="lock_after_approval">
                                    <option value="0" {{ ($settings['lock_after_approval'] ?? 0) ? '' : 'selected' }}>No — allow edits</option>
                                    <option value="1" {{ ($settings['lock_after_approval'] ?? 0) ? 'selected' : '' }}>Yes — lock edits</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bu-card">
                    <div class="bu-card-h"><h2>Notifications</h2></div>
                    <div class="bu-pad">
                        <div class="bu-swrow">
                            <div>
                                <div class="t">Email on budget submitted</div>
                                <div class="s">Notify approvers when a budget is submitted</div>
                            </div>
                            <input type="hidden" name="email_submitted" value="0">
                            <label class="bu-sw {{ ($settings['email_submitted'] ?? 1) ? 'on' : '' }}">
                                <input type="checkbox" name="email_submitted" value="1" {{ ($settings['email_submitted'] ?? 1) ? 'checked' : '' }} style="display:none">
                            </label>
                        </div>
                        <div class="bu-swrow">
                            <div>
                                <div class="t">Email on approval / rejection</div>
                                <div class="s">Notify the preparer of the outcome</div>
                            </div>
                            <input type="hidden" name="email_decision" value="0">
                            <label class="bu-sw {{ ($settings['email_decision'] ?? 1) ? 'on' : '' }}">
                                <input type="checkbox" name="email_decision" value="1" {{ ($settings['email_decision'] ?? 1) ? 'checked' : '' }} style="display:none">
                            </label>
                        </div>
                        <div class="bu-swrow">
                            <div>
                                <div class="t">Email on threshold alert</div>
                                <div class="s">Warn when variance crosses thresholds</div>
                            </div>
                            <input type="hidden" name="email_threshold" value="0">
                            <label class="bu-sw {{ ($settings['email_threshold'] ?? 1) ? 'on' : '' }}">
                                <input type="checkbox" name="email_threshold" value="1" {{ ($settings['email_threshold'] ?? 1) ? 'checked' : '' }} style="display:none">
                            </label>
                        </div>
                        <div class="bu-swrow">
                            <div>
                                <div class="t">Email on adjustment request</div>
                                <div class="s">Notify when an adjustment is requested</div>
                            </div>
                            <input type="hidden" name="email_adjustment" value="0">
                            <label class="bu-sw {{ ($settings['email_adjustment'] ?? 1) ? 'on' : '' }}">
                                <input type="checkbox" name="email_adjustment" value="1" {{ ($settings['email_adjustment'] ?? 1) ? 'checked' : '' }} style="display:none">
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <div class="bu-card bu-actionbar">
                <a href="{{ route('accounting.budgets.dashboard') }}" class="bu-btn bu-btn-ghost">Discard</a>
                <button type="submit" class="bu-btn bu-btn-cta">Save Settings</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelectorAll('.bu-sw').forEach(function(label) {
            label.addEventListener('click', function(e) {
                e.preventDefault();
                var cb = this.querySelector('input[type="checkbox"]');
                cb.checked = !cb.checked;
                this.classList.toggle('on', cb.checked);
            });
            var cb = label.querySelector('input[type="checkbox"]');
            label.classList.toggle('on', cb.checked);
        });
    </script>
</x-app-layout>
