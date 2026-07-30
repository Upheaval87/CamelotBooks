<form method="POST" action="{{ route('system-settings.update-approval') }}">
    @csrf
    @method('PUT')

    <div class="settings-section-header">
        <div class="settings-section-eyebrow">06 · APPROVAL WORKFLOW</div>
        <div class="settings-section-title">Approval Workflow Settings</div>
        <p class="settings-section-desc">Configure when transactions require approval before posting. The master toggle enables or disables the entire approval workflow. Per-document thresholds let you set different trigger amounts for each transaction type.</p>
        <hr class="settings-section-divider">
    </div>

    <div class="space-y-4">
        <x-settings.toggle
            name="requires_approval"
            label="Enable Approval Workflow"
            description="When disabled, all transactions are posted directly without requiring approval. When enabled, transactions exceeding their threshold require approval from an authorized user."
            :checked="old('requires_approval', $approvalSetting->requires_approval ? '1' : '0') === '1'" />

        <div class="settings-card">
            <div class="settings-field">
                <label for="global_threshold" class="settings-field-label">
                    Global Fallback Threshold ({{ \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$') }})
                </label>
                <input type="number" step="0.01" min="0" name="threshold_amount" id="global_threshold"
                    value="{{ old('threshold_amount', $approvalSetting->threshold_amount) }}"
                    class="settings-field-input" style="max-width: 200px;" />
                <p class="settings-field-hint">Used as the default threshold for any document type that does not have its own threshold configured below. Set to <strong>0</strong> to require approval for all transactions of that type.</p>
            </div>
        </div>

        <div class="settings-card">
            <div class="settings-field" style="margin-bottom: 1rem;">
                <span class="settings-field-label">Per-Document Thresholds</span>
                <p class="settings-field-hint">Override the global threshold for specific transaction types. Enable a threshold to set a custom trigger amount for that document type.</p>
            </div>

            <div class="space-y-3">
                @foreach(\App\Models\ApprovalThreshold::documentTypes() as $type => $label)
                    @php
                        $existing = $approvalThresholds[$type] ?? null;
                        $threshold = $existing ? (float) $existing['threshold_amount'] : 0;
                        $active = $existing ? (bool) $existing['is_active'] : false;
                    @endphp
                    <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 border border-line rounded-lg">
                        <div class="flex items-center gap-2 sm:w-56">
                            <input type="hidden" name="active.{{ $type }}" value="0">
                            <input type="checkbox" name="active.{{ $type }}" value="1"
                                id="active_{{ $type }}" {{ $active ? 'checked' : '' }}
                                class="rounded border-gray-300 text-gold focus:ring-gold" />
                            <label for="active_{{ $type }}" class="text-sm font-medium text-ink">{{ $label }}</label>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2">
                                <span class="text-xs text-ink-faint">Threshold:</span>
                                <input type="number" step="0.01" min="0" name="thresholds.{{ $type }}"
                                    value="{{ $threshold }}"
                                    class="settings-field-input" style="max-width: 160px;"
                                    {{ !$active ? 'disabled' : '' }} />
                                <span class="text-xs text-ink-faint">0 = always require approval</span>
                            </div>
                        </div>
                        <div class="text-xs text-ink-faint sm:w-36 sm:text-right">
                            @if($active && $threshold > 0)
                                ≥ {{ number_format($threshold, 2) }} triggers approval
                            @elseif($active && $threshold == 0)
                                Always requires approval
                            @else
                                Uses global threshold
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>

    <x-settings.callout variant="info" class="mt-4">
        Approval workflows also block period closes and fiscal year closes until all pending items are resolved.
    </x-settings.callout>

    <div class="flex justify-end mt-4">
        <button type="submit" class="btn-primary">Save Approval Settings</button>
    </div>
</form>

@push('scripts')
<script>
document.querySelectorAll('input[type="checkbox"][name^="active."]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var type = this.name.replace('active[', '').replace(']', '').replace('active.', '');
        var input = document.querySelector('input[name="thresholds.' + type + '"]');
        if (input) {
            input.disabled = !this.checked;
        }
    });
});
</script>
@endpush
