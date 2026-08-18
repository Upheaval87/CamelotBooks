<x-app-layout>
    <div class="ac-wrap">
        <div class="ac-page-head">
            <nav class="ac-crumbs">
                <a href="{{ route('accounting.fiscal-years.index') }}">Fiscal Years</a> <span>›</span> <span class="here">New</span>
            </nav>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.fiscal-years.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
            </div>
        </div>

        <h1 class="ac-page-head-title">New Fiscal Year</h1>

        <div class="ac-card">
            <div class="ac-pad">
                <form method="POST" action="{{ route('accounting.fiscal-years.store') }}">
                    @csrf
                    <div class="ac-g2">
                        <div class="ac-f">
                            <label>Label</label>
                            <input class="in" name="label" value="{{ old('label') }}" placeholder="e.g. FY 2026" required>
                        </div>
                        <div class="ac-f">
                            <label>&nbsp;</label>
                            <div style="height:44px"></div>
                        </div>
                        <div class="ac-f">
                            <label>Start Date</label>
                            <input class="in" type="date" name="start_date" value="{{ old('start_date') }}" required>
                        </div>
                        <div class="ac-f">
                            <label>End Date</label>
                            <input class="in" type="date" name="end_date" value="{{ old('end_date') }}" required>
                        </div>
                    </div>
                    <div style="display:flex;gap:10px;margin-top:20px">
                        <a href="{{ route('accounting.fiscal-years.index') }}" class="ac-btn ac-btn-ghost ac-btn-sm">Cancel</a>
                        <button type="submit" class="ac-btn ac-btn-cta ac-btn-sm">Create Fiscal Year</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
