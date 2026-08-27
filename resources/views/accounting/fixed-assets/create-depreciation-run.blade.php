<x-app-layout>
    <div class="fa-wrap">
        <div class="fa-head">
            <div>
                <h1>New Depreciation Run</h1>
                <p style="font-size:.8125rem;color:var(--muted);margin-top:.25rem">Calculate depreciation for {{ $activeAssets }} active asset(s).</p>
            </div>
            <div class="fa-actions">
                <a href="{{ route('accounting.fixed-assets.depreciation-runs') }}" class="fa-btn fa-btn-ghost">Cancel</a>
            </div>
        </div>

        <div class="fa-card">
            <div class="fa-sec">
                <div class="fa-sec-ic">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22,12 18,12 15,21 9,3 6,12 2,12"/></svg>
                </div>
                <span class="fa-sec-text">Run Parameters</span>
            </div>

            <form method="POST" action="{{ route('accounting.fixed-assets.depreciation-runs.store') }}">
                @csrf
                <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:1rem">
                    <div class="fa-field">
                        <label class="fa-label">Period <span style="color:#b91c1c">*</span></label>
                        <input type="text" name="period" value="{{ old('period', now()->format('Y-m')) }}" class="fa-input" required placeholder="e.g. 2026-01">
                        <span class="fa-hint">Format: YYYY-MM</span>
                        @error('period') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Book Type <span style="color:#b91c1c">*</span></label>
                        <select name="book_type" class="fa-select" required>
                            <option value="financial" {{ old('book_type', 'financial') === 'financial' ? 'selected' : '' }}>Financial</option>
                            <option value="tax" {{ old('book_type') === 'tax' ? 'selected' : '' }}>Tax</option>
                        </select>
                        @error('book_type') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Period Start <span style="color:#b91c1c">*</span></label>
                        <input type="date" name="period_start" value="{{ old('period_start', now()->startOfMonth()->format('Y-m-d')) }}" class="fa-input" required>
                        @error('period_start') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                    <div class="fa-field">
                        <label class="fa-label">Period End <span style="color:#b91c1c">*</span></label>
                        <input type="date" name="period_end" value="{{ old('period_end', now()->endOfMonth()->format('Y-m-d')) }}" class="fa-input" required>
                        @error('period_end') <span class="fa-error">{{ $message }}</span> @enderror
                    </div>
                </div>
                <div style="margin-top:1.5rem;text-align:right">
                    <button type="submit" class="fa-btn fa-btn-primary">Create Run</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
