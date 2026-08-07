<form method="POST" action="{{ route('system-settings.update-approval') }}">
    @csrf
    @method('PUT')
    <div class="card">
        <div class="px-6 py-4 border-b border-gray-200">
            <div class="form-section-label">1 · Approval Workflow Settings</div>
            <p class="mt-1 text-sm text-ink-soft">Configure when transactions require approval before posting. The master toggle enables or disables the entire approval workflow. Per-document thresholds let you set different trigger amounts for each transaction type.</p>
        </div>
        <div class="p-6 space-y-6">
            {{-- Master Toggle --}}
            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                <div class="flex-shrink-0 mt-0.5">
                    <input type="hidden" name="requires_approval" value="0">
                    <input type="checkbox" name="requires_approval" value="1" id="requires_approval"
                        {{ old('requires_approval', $approvalSetting->requires_approval ? '1' : '0') == '1' ? 'checked' : '' }}
                        class="rounded border-gray-300 text-gold-700 focus:ring-gold-500" />
                </div>
                <div>
                    <label for="requires_approval" class="block text-sm font-medium text-gray-700">Enable Approval Workflow</label>
                    <p class="text-xs text-gray-500 mt-0.5">When disabled, all transactions are posted directly without requiring approval. When enabled, transactions exceeding their threshold require approval from an authorized user.</p>
                </div>
            </div>

            {{-- Global Fallback Threshold --}}
            <div class="p-4 bg-gray-50 rounded-lg">
                <label for="global_threshold" class="block text-sm font-medium text-gray-700">
                    Global Fallback Threshold ({{ \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$') }})
                </label>
                <input type="number" step="0.01" min="0" name="threshold_amount" id="global_threshold"
                    value="{{ old('threshold_amount', $approvalSetting->threshold_amount) }}"
                    class="mt-1 block w-48 border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm" />
                <p class="text-xs text-gray-500 mt-1">Used as the default threshold for any document type that does not have its own threshold configured below. Set to <strong>0</strong> to require approval for all transactions of that type.</p>
            </div>

            {{-- Per-Document Thresholds --}}
            <div>
                <h4 class="text-sm font-medium text-gray-900 mb-3">Per-Document Thresholds</h4>
                <p class="text-xs text-gray-500 mb-4">Override the global threshold for specific transaction types. Enable a threshold to set a custom trigger amount for that document type.</p>

                <div class="space-y-3">
                    @foreach(\App\Models\ApprovalThreshold::documentTypes() as $type => $label)
                        @php
                            $existing = $approvalThresholds[$type] ?? null;
                            $threshold = $existing ? (float) $existing['threshold_amount'] : 0;
                            $active = $existing ? (bool) $existing['is_active'] : false;
                        @endphp
                        <div class="flex items-center gap-4 p-3 bg-white border border-gray-200 rounded-lg">
                            <div class="flex items-center gap-2 w-56">
                                <input type="hidden" name="active.{{ $type }}" value="0">
                                <input type="checkbox" name="active.{{ $type }}" value="1"
                                    id="active_{{ $type }}"
                                    {{ $active ? 'checked' : '' }}
                                    class="rounded border-gray-300 text-gold-700 focus:ring-gold-500" />
                                <label for="active_{{ $type }}" class="text-sm font-medium text-gray-700">{{ $label }}</label>
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-xs text-gray-400">Threshold:</span>
                                    <input type="number" step="0.01" min="0" name="thresholds.{{ $type }}"
                                        value="{{ $threshold }}"
                                        class="block w-40 border-gray-300 focus:border-gold-500 focus:ring-gold-500 rounded-md shadow-sm text-sm"
                                        {{ !$active ? 'disabled' : '' }}
                                        x-ref="threshold_{{ $type }}" />
                                    <span class="text-xs text-gray-400">0 = always require approval</span>
                                </div>
                            </div>
                            <div class="text-xs text-gray-400 w-36 text-right">
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

            <div class="p-4 bg-gold-soft rounded-lg border border-gold-line">
                <p class="text-sm text-gold-800"><strong>Note:</strong> Approval workflows also block period closes and fiscal year closes until all pending items are resolved.</p>
            </div>
        </div>
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
            <x-button variant="primary" type="submit">Save Approval Settings</x-button>
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
