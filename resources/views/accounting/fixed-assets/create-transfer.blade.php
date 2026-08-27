<x-app-layout>
    <div class="fa-wrap">
        <div class="fa-head">
            <div>
                <h1>Request Transfer</h1>
                <p style="font-size:.8125rem;color:var(--muted);margin-top:.25rem">Asset: {{ $asset->name }} ({{ $asset->asset_code }}) — Currently at {{ $asset->branch?->name ?? $asset->location ?? 'Unknown' }}</p>
            </div>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.show', $asset->id) }}" class="fa-btn fa-btn-ghost">Cancel</a>
            </div>
        </div>

        <div class="fa-card">
            <div class="fa-sec">
                <div class="fa-sec-ic">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
                </div>
                <span class="fa-sec-text">Transfer Details</span>
            </div>

            <form method="POST" action="{{ route('accounting.fixed-assets.transfers.store', $asset->id) }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Transfer Date <span style="color:#b91c1c">*</span></label>
                        <input type="date" name="transfer_date" value="{{ old('transfer_date', now()->format('Y-m-d')) }}" class="fa-input" required>
                        @error('transfer_date') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">To Branch</label>
                        <select name="to_branch_id" class="fa-select">
                            <option value="">Keep current</option>
                            @foreach ($branches as $b)
                                <option value="{{ $b->id }}" {{ old('to_branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                            @endforeach
                        </select>
                        @error('to_branch_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">To Cost Centre</label>
                        <select name="to_cost_center_id" class="fa-select">
                            <option value="">Keep current</option>
                            @foreach ($costCenters as $cc)
                                <option value="{{ $cc->id }}" {{ old('to_cost_center_id') == $cc->id ? 'selected' : '' }}>{{ $cc->name }}</option>
                            @endforeach
                        </select>
                        @error('to_cost_center_id') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">New Custodian</label>
                        <input type="text" name="to_custodian" value="{{ old('to_custodian') }}" class="fa-input" placeholder="Keep current if blank">
                        @error('to_custodian') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">New Location</label>
                        <input type="text" name="to_location" value="{{ old('to_location') }}" class="fa-input" placeholder="Keep current if blank">
                        @error('to_location') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div class="fa-field" style="margin-top:1rem">
                    <label class="fa-label">Reason</label>
                    <textarea name="reason" class="fa-textarea" rows="3" placeholder="Why is this asset being transferred?">{{ old('reason') }}</textarea>
                    @error('reason') <span class="fa-error">{{ $message }}</span> @enderror
                </div>
                <div style="margin-top:1.5rem;text-align:right">
                    <button type="submit" class="fa-btn fa-btn-primary">Submit Transfer Request</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
