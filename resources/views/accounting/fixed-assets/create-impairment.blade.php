<x-app-layout>
    <div class="fa-wrap">
        <div class="fa-head">
            <div>
                <h1>Record Impairment</h1>
                <p style="font-size:.8125rem;color:var(--muted);margin-top:.25rem">Asset: {{ $asset->name }} ({{ $asset->asset_code }})</p>
            </div>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="fa-btn fa-btn-ghost">Cancel</a>
            </div>
        </div>

        <div class="fa-card">
            <div class="fa-sec">
                <div class="fa-sec-ic">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                </div>
                <span class="fa-sec-text">Impairment Details</span>
            </div>

            <div class="fa-kpi-grid" style="margin-bottom:1.5rem">
                <div class="fa-kpi"><div class="fa-kpi-label">Net Book Value</div><div class="fa-kpi-value">{{ format_number($asset->net_book_value) }}</div></div>
                <div class="fa-kpi"><div class="fa-kpi-label">Accum. Depreciation</div><div class="fa-kpi-value">{{ format_number($asset->accumulated_depreciation) }}</div></div>
                <div class="fa-kpi"><div class="fa-kpi-label">Acquisition Cost</div><div class="fa-kpi-value">{{ format_number($asset->acquisition_cost) }}</div></div>
            </div>

            <form method="POST" action="{{ route('accounting.fixed-assets.impairments.store', $asset->id) }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Impairment Date <span style="color:#b91c1c">*</span></label>
                        <input type="date" name="impairment_date" value="{{ old('impairment_date', now()->format('Y-m-d')) }}" class="fa-input" required>
                        @error('impairment_date') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Recoverable Amount <span style="color:#b91c1c">*</span></label>
                        <input type="number" name="recoverable_amount" value="{{ old('recoverable_amount') }}" class="fa-input" step="0.01" min="0" required placeholder="0.00">
                        <span class="fa-hint">The higher of fair value less costs to sell and value in use.</span>
                        @error('recoverable_amount') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="fa-field" style="margin-top:1rem">
                    <label class="fa-label">Reason <span style="color:#b91c1c">*</span></label>
                    <textarea name="reason" class="fa-textarea" rows="3" placeholder="Explain the impairment indicator…">{{ old('reason') }}</textarea>
                    @error('reason') <span class="fa-error">{{ $message }}</span> @enderror
                </div>
                <div style="margin-top:1.5rem;text-align:right">
                    <button type="submit" class="fa-btn fa-btn-primary">Record Impairment</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
