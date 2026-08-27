<x-app-layout>
    <div class="fa-wrap">
        <div class="fa-head">
            <div>
                <h1>Request Disposal</h1>
                <p style="font-size:.8125rem;color:var(--muted);margin-top:.25rem">Asset: {{ $asset->name }} ({{ $asset->asset_code }})</p>
            </div>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="fa-btn fa-btn-ghost">Cancel</a>
            </div>
        </div>

        <div class="fa-card">
            <div class="fa-sec">
                <div class="fa-sec-ic">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="3,6 5,6 21,6"/><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"/></svg>
                </div>
                <span class="fa-sec-text">Disposal Details</span>
            </div>

            <div class="fa-kpi-grid" style="margin-bottom:1.5rem">
                <div class="fa-kpi"><div class="fa-kpi-label">Cost</div><div class="fa-kpi-value">{{ format_number($asset->acquisition_cost) }}</div></div>
                <div class="fa-kpi"><div class="fa-kpi-label">Accum. Depreciation</div><div class="fa-kpi-value">{{ format_number($asset->accumulated_depreciation) }}</div></div>
                <div class="fa-kpi"><div class="fa-kpi-label">Net Book Value</div><div class="fa-kpi-value">{{ format_number($asset->net_book_value) }}</div></div>
            </div>

            <form method="POST" action="{{ route('accounting.fixed-assets.disposals.store', $asset->id) }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Disposal Date <span style="color:#b91c1c">*</span></label>
                        <input type="date" name="disposal_date" value="{{ old('disposal_date', now()->format('Y-m-d')) }}" class="fa-input" required>
                        @error('disposal_date') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Method <span style="color:#b91c1c">*</span></label>
                        <select name="disposal_method" class="fa-select" required>
                            <option value="">Select method…</option>
                            <option value="sale" {{ old('disposal_method') === 'sale' ? 'selected' : '' }}>Sale</option>
                            <option value="scrap" {{ old('disposal_method') === 'scrap' ? 'selected' : '' }}>Scrap</option>
                            <option value="donation" {{ old('disposal_method') === 'donation' ? 'selected' : '' }}>Donation</option>
                            <option value="destroyed" {{ old('disposal_method') === 'destroyed' ? 'selected' : '' }}>Destroyed</option>
                        </select>
                        @error('disposal_method') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Proceeds Amount</label>
                        <input type="number" name="proceeds_amount" value="{{ old('proceeds_amount', '0') }}" class="fa-input" step="0.01" min="0" placeholder="0.00">
                        @error('proceeds_amount') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Disposal Cost</label>
                        <input type="number" name="disposal_cost" value="{{ old('disposal_cost', '0') }}" class="fa-input" step="0.01" min="0" placeholder="0.00">
                        @error('disposal_cost') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="fa-field" style="margin-top:1rem">
                    <label class="fa-label">Reason</label>
                    <textarea name="reason" class="fa-textarea" rows="3" placeholder="Why is this asset being disposed?">{{ old('reason') }}</textarea>
                    @error('reason') <span class="fa-error">{{ $message }}</span> @enderror
                </div>
                <div style="margin-top:1.5rem;text-align:right">
                    <button type="submit" class="fa-btn fa-btn-primary">Submit Disposal Request</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
