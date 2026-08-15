<x-app-layout>
    @php $cs = \App\Models\SystemSetting::getValue('currency', 'currency_symbol', session('current_company_id'), '$'); @endphp

    <div class="ex-suite wrap" style="display:flex;justify-content:center">
        <div class="card" style="max-width:860px;width:100%">
            <div class="card-h">
                <h2>{{ __('New Expense Claim') }}</h2>
                <span class="badge b-sub" style="margin-left:auto"><span class="bdot"></span>{{ __('Employee claim') }}</span>
            </div>
            <div class="card-sec">
                <form method="POST" action="{{ route('accounting.expenses.claims.store') }}" enctype="multipart/form-data" id="claim-form">
                    @csrf
                    @if($errors->any())
                        <div class="note-warn" role="alert" style="margin-bottom:14px">
                            @foreach($errors->all() as $e)
                                <div>{{ $e }}</div>
                            @endforeach
                        </div>
                    @endif

                    <div class="g4" style="grid-template-columns:1fr 1fr">
                        <div class="field">
                            <label>{{ __('Employee') }} *</label>
                            <select class="input h44" name="employee_id" required>
                                <option value="">{{ __('Select employee') }}</option>
                                @foreach($employees as $employee)
                                    <option value="{{ $employee->id }}" {{ old('employee_id') == $employee->id ? 'selected' : '' }}>{{ $employee->full_name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Expense Date') }} *</label>
                            <input class="input h44" type="date" name="expense_date" value="{{ old('expense_date', now()->format('Y-m-d')) }}" required>
                        </div>
                        <div class="field">
                            <label>{{ __('Category') }}</label>
                            <select class="input h44" name="category_id">
                                <option value="">{{ __('Select category') }}</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Amount') }} ({{ $cs }}) *</label>
                            <input class="input h44" type="number" step="0.01" min="0.01" name="amount" value="{{ old('amount') }}" placeholder="0.00" required>
                        </div>
                        <div class="field" style="grid-column:1/-1">
                            <label>{{ __('Description') }}</label>
                            <input class="input h44" name="description" value="{{ old('description') }}" placeholder="{{ __('What was this for?') }}">
                        </div>
                        <div class="field">
                            <label>{{ __('Branch') }}</label>
                            <select class="input h44" name="branch_id">
                                <option value="">{{ __('All / none') }}</option>
                                @foreach($branches as $branch)
                                    <option value="{{ $branch->id }}" {{ old('branch_id') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Cost Centre') }}</label>
                            <select class="input h44" name="cost_center_id">
                                <option value="">{{ __('All / none') }}</option>
                                @foreach($costCenters as $cc)
                                    <option value="{{ $cc->id }}" {{ old('cost_center_id') == $cc->id ? 'selected' : '' }}>{{ $cc->code }} — {{ $cc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Currency') }}</label>
                            <select class="input h44" name="currency">
                                <option value="">{{ __('Base currency') }}</option>
                                @foreach($currencies as $currency)
                                    <option value="{{ $currency->code }}" {{ old('currency') === $currency->code ? 'selected' : '' }}>{{ $currency->code }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Exchange Rate') }}</label>
                            <input class="input h44" type="number" step="0.000001" min="0" name="exchange_rate" value="{{ old('exchange_rate') }}" placeholder="1.000000">
                        </div>
                        <div class="field">
                            <label>{{ __('Payment Method') }}</label>
                            <select class="input h44" name="payment_method">
                                <option value="">{{ __('Select method') }}</option>
                                @foreach(['personal_funds' => 'Personal Funds', 'bank_transfer' => 'Bank Transfer', 'mobile_money' => 'Mobile Money', 'cash' => 'Cash'] as $pk => $pv)
                                    <option value="{{ $pk }}" {{ old('payment_method') === $pk ? 'selected' : '' }}>{{ $pv }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="field">
                            <label>{{ __('Reimburse To') }}</label>
                            <input class="input h44" name="reimburse_to" value="{{ old('reimburse_to') }}" placeholder="{{ __('Bank or mobile money details') }}">
                        </div>
                        <div class="field" style="grid-column:1/-1">
                            <label>{{ __('Memo') }}</label>
                            <textarea class="input" name="memo" rows="2" placeholder="{{ __('Additional notes for the approver.') }}">{{ old('memo') }}</textarea>
                        </div>
                    </div>

                    <div class="attchips">
                        <span class="att" id="claim-attchips-empty">{{ __('No receipts attached yet.') }}</span>
                        @foreach($errors->get('files.*') as $ferr)
                            <span class="att" style="color:var(--red-2)">{{ $ferr[0] }}</span>
                        @endforeach
                        <label class="btn btn-ghost btn-xs" for="claim-files">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path d="M12 5v14M5 12h14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
                            {{ __('Attach Receipt') }}
                            <input type="file" id="claim-files" name="files[]" multiple accept="image/*,.pdf" class="hidden" style="display:none">
                        </label>
                    </div>

                    <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px">
                        <button type="button" class="btn btn-ghost" onclick="window.history.back()">{{ __('Cancel') }}</button>
                        <button type="submit" class="btn btn-ghost" name="action" value="save_draft">{{ __('Save Draft') }}</button>
                        <button type="submit" class="btn btn-cta" name="action" value="submit">
                            {{ __('Submit Claim') }} <svg width="14" height="14" viewBox="0 0 24 24" fill="none"><path d="M7 17L17 7M17 7H8m9 0v9" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
