<x-app-layout>
    <div class="je-wrap">
        <div class="je-crumbs">
            <a href="{{ route('accounting.journal-entries.index') }}">Journals</a>
            <span>›</span>
            <a href="{{ route('accounting.journal-entries.show', $journalEntry) }}">{{ $journalEntry->journal_number }}</a>
            <span>›</span>
            <span class="here">Edit</span>
        </div>

        <div class="je-page-head">
            <div>
                <h1>Edit {{ $journalEntry->journal_number }}</h1>
                <div class="sub">Only Draft journals are editable; posted journals must be reversed.</div>
            </div>
            <div style="display:flex;gap:10px">
                <a href="{{ route('accounting.journal-entries.show', $journalEntry) }}" class="je-btn je-btn-ghost">Cancel</a>
                <button type="submit" name="action" value="save_draft" form="je-form" class="je-btn je-btn-sec">Save Changes</button>
            </div>
        </div>

        @include('accounting.journal-entries._form', [
            'journalEntry' => $journalEntry,
            'accounts' => $accounts,
            'branches' => $branches,
            'costCenters' => $costCenters,
            'isEdit' => true,
        ])
    </div>
</x-app-layout>
