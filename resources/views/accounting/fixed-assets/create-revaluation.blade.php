<x-app-layout>
    <div class="fa-wrap">
        <div class="fa-head">
            <div>
                <h1>Request Revaluation</h1>
                <p style="font-size:.8125rem;color:var(--muted);margin-top:.25rem">Asset: {{ $asset->name }} ({{ $asset->asset_code }})</p>
            </div>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="fa-btn fa-btn-ghost">Cancel</a>
            </div>
        </div>

        <div class="fa-card">
            <div class="fa-sec">
                <div class="fa-sec-ic">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23,6 13.5,15.5 8.5,10.5 1,18"/><polyline points="17,6 23,6 23,12"/></svg>
                </div>
                <span class="fa-sec-text">Revaluation Details</span>
            </div>

            <div class="fa-kpi-grid" style="margin-bottom:1.5rem">
                <div class="fa-kpi"><div class="fa-kpi-label">Current NBV</div><div class="fa-kpi-value">{{ format_number($asset->net_book_value) }}</div></div>
                <div class="fa-kpi"><div class="fa-kpi-label">Acquisition Cost</div><div class="fa-kpi-value">{{ format_number($asset->acquisition_cost) }}</div></div>
                <div class="fa-kpi">
                    <div class="fa-kpi-label">Last Revalued</div>
                    <div class="fa-kpi-value" style="font-size:1rem">{{ $asset->is_revalued ? 'Yes' : 'No' }}</div>
                </div>
            </div>

            <form method="POST" action="{{ route('accounting.fixed-assets.revaluations.store', $asset->id) }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Revaluation Date <span style="color:#b91c1c">*</span></label>
                        <input type="date" name="revaluation_date" value="{{ old('revaluation_date', now()->format('Y-m-d')) }}" class="fa-input" required>
                        @error('revaluation_date') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">New Fair Value <span style="color:#b91c1c">*</span></label>
                        <input type="number" name="new_value" value="{{ old('new_value') }}" class="fa-input" step="0.01" min="0" required placeholder="0.00">
                        <span class="fa-hint">Current NBV: {{ format_number($asset->net_book_value) }}</span>
                        @error('new_value') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="fa-field" style="margin-top:1rem">
                    <label class="fa-label">Reason <span style="color:#b91c1c">*</span></label>
                    <textarea name="reason" class="fa-textarea" rows="3" placeholder="Explain the revaluation basis…">{{ old('reason') }}</textarea>
                    @error('reason') <span class="fa-error">{{ $message }}</span> @enderror
                </div>
                <div style="margin-top:1.5rem;text-align:right">
                    <button type="submit" class="fa-btn fa-btn-primary">Submit Revaluation Request</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
