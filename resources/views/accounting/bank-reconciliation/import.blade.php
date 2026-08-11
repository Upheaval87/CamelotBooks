<x-app-layout>
    @php
        $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$');
    @endphp

    <div class="suite pb-6">
        <div class="max-w-8xl mx-auto sm:px-6 lg:px-8">

            <div class="sticky-head">
                <div>
                    <h1>{{ __('Import Bank Statement') }} <span class="mono-chip">{{ $bankAccount->code }}</span></h1>
                    <div class="sub">{{ $bankAccount->name }} · {{ $bankAccount->bank_name }}</div>
                </div>
                <div class="tbtns">
                    <button type="submit" form="import-form" class="btn cta">{{ __('Import Statement') }}</button>
                    <a href="{{ route('accounting.bank-reconciliation.index', $bankAccount->id) }}" class="btn btn-ghost">{{ __('Back') }}</a>
                </div>
            </div>

            <form id="import-form" method="POST" action="{{ route('accounting.bank-reconciliation.import', $bankAccount->id) }}" enctype="multipart/form-data">
                @csrf

                <section class="card card-sec">
                    <div class="sec-head">
                        <span class="sec-ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="M12 18v-6"/><path d="M9 15l3 3 3-3"/></svg></span>
                        <h2>{{ __('Statement Upload') }}</h2>
                        <span class="rule"></span>
                    </div>

                    <div class="g4">
                        <div class="field">
                            <label class="label" for="statement_file">{{ __('Statement File') }} <span class="req">*</span></label>
                            <input type="file" id="statement_file" name="statement_file" accept=".csv,.txt,text/csv" class="input">
                            <p class="hint">CSV or TXT with a header row. Date, Description, Debit, Credit, Reference, Balance are detected from column headers.</p>
                            @error('statement_file')<p class="error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label class="label" for="statement_date">{{ __('Statement Date') }} <span class="req">*</span></label>
                            <input type="date" id="statement_date" name="statement_date" value="{{ old('statement_date', now()->toDateString()) }}" class="input">
                            <p class="hint">Statement period end date.</p>
                            @error('statement_date')<p class="error">{{ $message }}</p>@enderror
                        </div>

                        <div class="field">
                            <label class="label" for="statement_end_balance">{{ __('Ending Balance') }} ({{ $cs }}) <span class="req">*</span></label>
                            <input type="number" step="0.01" id="statement_end_balance" name="statement_end_balance" value="{{ old('statement_end_balance') }}" placeholder="0.00" class="input">
                            <p class="hint">The closing balance shown on your bank statement.</p>
                            @error('statement_end_balance')<p class="error">{{ $message }}</p>@enderror
                        </div>
                    </div>

                    <div class="g4" style="margin-top:4px">
                        <div class="field">
                            <label class="label">{{ __('Book Balance') }} ({{ $cs }})</label>
                            <div class="v" style="padding-top:11px">{{ format_number($bankAccount->current_balance) }}</div>
                        </div>
                    </div>
                </section>
            </form>
        </div>
    </div>
</x-app-layout>
