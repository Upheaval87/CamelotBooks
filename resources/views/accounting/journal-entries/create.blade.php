<x-app-layout>
    <div class="je-wrap">
        <div class="je-crumbs">
            <a href="{{ route('accounting.journal-entries.index') }}">Journals</a>
            <span>›</span>
            <span class="here">New</span>
        </div>

        <div class="je-page-head">
            <div>
                <h1>New Journal Entry</h1>
                <div class="sub">Create a balanced manual posting.</div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.journal-entries.index') }}" class="je-btn je-btn-ghost">Cancel</a>
                <button type="submit" name="action" value="save_draft" form="je-form" class="je-btn je-btn-sec">Save Draft</button>
                <button type="submit" name="action" value="post" form="je-form" class="je-btn je-btn-cta">Submit Approval</button>
            </div>
        </div>

        @include('accounting.journal-entries._form', [
            'journalEntry' => null,
            'accounts' => $accounts,
            'branches' => $branches,
            'costCenters' => $costCenters,
            'isEdit' => false,
        ])
    </div>
</x-app-layout>
