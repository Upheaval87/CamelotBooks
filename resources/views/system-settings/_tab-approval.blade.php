<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'approval'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="approval-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('Save Approval Settings') }}
            </button>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-approval') }}" id="approval-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <h2>{{ __('Approval Workflow') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Configure when transactions require approval before posting. The master toggle enables or disables the entire approval workflow. Per-document thresholds let you set different trigger amounts for each transaction type.</p>

            <x-settings.toggle
                name="requires_approval"
                label="Enable Approval Workflow"
                description="When disabled, all transactions are posted directly without requiring approval. When enabled, transactions exceeding their threshold require approval from an authorized user."
                :checked="old('requires_approval', $approvalSetting->requires_approval ? '1' : '0') === '1'" />

            <div class="g2" style="margin-top: 18px;">
                <div class="field">
                    <label for="global_threshold" class="label">
                        Global Fallback Threshold ({{ \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$') }})
                    </label>
                    <input type="number" step="0.01" min="0" name="threshold_amount" id="global_threshold"
                        value="{{ old('threshold_amount', $approvalSetting->threshold_amount) }}"
                        class="input" style="max-width: 200px;" />
                    <p class="hint">Used as the default threshold for any document type that does not have its own threshold configured below. Set to <strong>0</strong> to require approval for all transactions of that type.</p>
                </div>
            </div>
        </div>

        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg></span>
                <h2>{{ __('Per-Document Thresholds') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Override the global threshold for specific transaction types. Enable a threshold to set a custom trigger amount for that document type.</p>

            @foreach(\App\Models\ApprovalThreshold::documentTypes() as $type => $label)
                @php
                    $existing = $approvalThresholds[$type] ?? null;
                    $threshold = $existing ? (float) $existing['threshold_amount'] : 0;
                    $active = $existing ? (bool) $existing['is_active'] : false;
                @endphp
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 p-3 border border-[#dceaea] rounded-xl" style="margin-bottom: 10px;">
                    <div class="flex items-center gap-2 sm:w-56">
                        <input type="hidden" name="active.{{ $type }}" value="0">
                        <input type="checkbox" name="active.{{ $type }}" value="1"
                            id="active_{{ $type }}" {{ $active ? 'checked' : '' }}
                            class="rounded border-[#dceaea] accent-[#128f8e] focus:ring-[#128f8e]" />
                        <label for="active_{{ $type }}" class="text-sm font-medium text-[#0b2a2d]">{{ $label }}</label>
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-[#8aa5a7]">Threshold:</span>
                            <input type="number" step="0.01" min="0" name="thresholds.{{ $type }}"
                                value="{{ $threshold }}"
                                class="input" style="max-width: 160px; height: 34px;"
                                {{ !$active ? 'disabled' : '' }} />
                            <span class="text-xs text-[#8aa5a7]">0 = always require approval</span>
                        </div>
                    </div>
                    <div class="text-xs text-[#8aa5a7] sm:w-36 sm:text-right">
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

            <div style="margin-top: 16px;">
                <x-settings.callout variant="info">
                    Approval workflows also block period closes and fiscal year closes until all pending items are resolved.
                </x-settings.callout>
            </div>
        </div>
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
