<x-app-layout>
<div class="pr max-w-8xl mx-auto sm:px-6 lg:px-8 py-6">

    {{-- Breadcrumbs --}}
    <nav class="pr-crumbs mb-4">
        <a href="{{ route('accounting.payroll.employees.index') }}">{{ __('Payroll') }}</a>
        <span>›</span>
        <span class="here">{{ __('Statutory Setup') }}</span>
    </nav>

    {{-- Page head --}}
    <div class="pr-page-head">
        <div>
            <h1>{{ __('Statutory Setup') }}</h1>
            <div class="sub">{{ __('PAYE tax tables, pension schemes and statutory deductions.') }}</div>
        </div>
    </div>

    {{-- PAYE Tax Tables --}}
    <div class="paye" style="margin-bottom:20px">
        <div class="paye-card">
            <div class="paye-pad">
                <div class="paye-eyebrow">{{ __('Tax Configuration') }}</div>
                <h1 class="paye-h1">{{ __('PAYE Tax Tables') }}</h1>
                <div class="paye-sub">{{ __('Define income tax bands for statutory PAYE calculations.') }}</div>

                @if($payeTables->count())
                    <div class="paye-li-wrap" style="margin-bottom:20px">
                        <table class="paye-table">
                            <thead class="paye-thead"><tr>
                                <th>{{ __('Version Name') }}</th>
                                <th>{{ __('Effective From') }}</th>
                                <th>{{ __('Effective To') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="paye-ctr">{{ __('Bands') }}</th>
                                <th class="paye-ctr" style="width:100px">{{ __('Actions') }}</th>
                            </tr></thead>
                            <tbody class="paye-tbody">
                                @foreach($payeTables as $table)
                                    <tr>
                                        <td style="font-weight:700;color:var(--paye-ink)">{{ $table->version_name }}</td>
                                        <td>{{ $table->effective_from?->format('d M Y') ?? '—' }}</td>
                                        <td>{{ $table->effective_to?->format('d M Y') ?? '—' }}</td>
                                        <td>
                                            @if($table->is_active)
                                                <span class="pr-badge pr-b-act"><span class="pr-bdot"></span>{{ __('Current') }}</span>
                                            @else
                                                <span class="pr-badge pr-b-lock"><span class="pr-bdot"></span>{{ __('Old') }}</span>
                                            @endif
                                        </td>
                                        <td class="paye-ctr">{{ $table->bands?->count() ?? 0 }}</td>
                                        <td class="paye-ctr"><button type="button" class="paye-ibtn" title="{{ __('Edit') }}">✎</button></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p style="margin-bottom:20px;color:var(--paye-muted)">{{ __('No PAYE tax tables configured yet.') }}</p>
                @endif

                <div x-data="payeTableForm()">
                    <button type="button" class="paye-btn paye-btn-cta paye-btn-sm" @click="showForm = !showForm" x-show="!showForm">
                        {{ __('＋ Create PAYE Table') }}
                    </button>

                    <div x-show="showForm" x-cloak>
                        <form x-ref="formEl" method="POST" action="{{ route('accounting.payroll.statutory.store') }}" @submit.prevent="submit()">
                            @csrf
                            <input type="hidden" name="type" value="paye_table">

                            <div class="paye-g3">
                                <div class="paye-f">
                                    <label>{{ __('Version Name') }} <span class="paye-req">*</span></label>
                                    <input class="paye-in" type="text" name="name" required placeholder="{{ __('e.g. 2026 Tax Year') }}">
                                </div>
                                <div class="paye-f">
                                    <label>{{ __('Effective From') }} <span class="paye-req">*</span></label>
                                    <input class="paye-in" type="date" name="effective_from" required>
                                </div>
                                <div class="paye-f">
                                    <label>{{ __('Effective To') }}</label>
                                    <input class="paye-in" type="date" name="effective_to">
                                </div>
                            </div>

                            <div class="paye-sect">
                                <span class="paye-t">{{ __('Tax Bands') }}</span>
                                <button type="button" class="paye-btn paye-btn-sec paye-btn-sm" @click="addBand()">＋ {{ __('Add Band') }}</button>
                            </div>

                            <div class="paye-li-wrap">
                                <table class="paye-table">
                                    <thead class="paye-thead"><tr>
                                        <th class="paye-ctr" style="width:9%">{{ __('Order') }}</th>
                                        <th style="width:26%">{{ __('Band From (K)') }}</th>
                                        <th style="width:26%">{{ __('Band To (K)') }}</th>
                                        <th style="width:12%">{{ __('Rate (%)') }}</th>
                                        <th style="width:190px">{{ __('Cumulative (K)') }}<span class="paye-autotag">⚙ {{ __('computed') }}</span></th>
                                        <th class="paye-ctr" style="width:70px">{{ __('Actions') }}</th>
                                    </tr></thead>
                                    <tbody class="paye-tbody">
                                        <template x-for="(band, index) in bands" :key="band._k">
                                            <tr>
                                                <td class="paye-ctr">
                                                    <span class="paye-ord" x-text="index + 1"></span>
                                                    <span class="paye-reorder">
                                                        <button type="button" @click="moveUp(index)" :disabled="index === 0" title="{{ __('Move up') }}">▲</button>
                                                        <button type="button" @click="moveDown(index)" :disabled="index === bands.length - 1" title="{{ __('Move down') }}">▼</button>
                                                    </span>
                                                </td>
                                                <td><input class="paye-in" type="text" inputmode="decimal" :name="'bands['+index+'][threshold]'" x-model="band.threshold"></td>
                                                <td><input class="paye-in" type="text" inputmode="decimal" :name="'bands['+index+'][upper_limit]'" x-model="band.upper_limit" placeholder="{{ __('No limit') }}"></td>
                                                <td><input class="paye-in" type="text" inputmode="decimal" :name="'bands['+index+'][rate]'" x-model="band.rate"></td>
                                                <td class="paye-auto" :title="{{ __('Auto-computed — read only') }}">
                                                    <span class="paye-lock">🔒</span><span x-text="cumulative(index)"></span>
                                                </td>
                                                <td class="paye-ctr">
                                                    <button type="button" class="paye-del" @click="removeBand(index)" title="{{ __('Remove band') }}" :disabled="bands.length <= 1">✕</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>

                            <div class="paye-hint">
                                <b>{{ __('Cumulative (K)') }}</b> {{ __('is auto-computed from Band From / Band To / Rate and is') }} <b>{{ __('read-only') }}</b>. {{ __('Leave Band To empty for the open-ended top band.') }}
                            </div>
                            <div class="paye-foot">
                                <button type="button" class="paye-btn paye-btn-ghost" @click="showForm = false">{{ __('Cancel') }}</button>
                                <button type="submit" class="paye-btn paye-btn-cta">{{ __('Save PAYE Table') }}</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Pension Schemes --}}
    <div class="pr-formcard">
        <div class="pr-fc-hd">
            <div class="kick">{{ __('Benefits Configuration') }}</div>
            <h1>{{ __('Pension Schemes') }}</h1>
            <div class="sub">{{ __('Manage employer and employee pension contribution schemes.') }}</div>
        </div>
        <div class="pr-fc-bd">
            {{-- Existing schemes --}}
            @if($pensionSchemes->count())
                <div class="pr-li-wrap" style="margin-bottom:20px">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Registration #') }}</th>
                                <th class="num">{{ __('Employee Rate') }}</th>
                                <th class="num">{{ __('Employer Rate') }}</th>
                                <th class="num">{{ __('Max Salary') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th style="width:100px">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pensionSchemes as $scheme)
                                <tr>
                                    <td style="font-weight:700;color:var(--ink)">{{ $scheme->name }}</td>
                                    <td class="pr-mono">{{ $scheme->registration_number ?? '—' }}</td>
                                    <td class="pr-numr">{{ format_number($scheme->employee_rate) }}%</td>
                                    <td class="pr-numr">{{ format_number($scheme->employer_rate) }}%</td>
                                    <td class="pr-numr">{{ $scheme->max_contributory_salary ? format_number($scheme->max_contributory_salary) : '—' }}</td>
                                    <td>
                                        @if($scheme->is_active)
                                            <span class="pr-badge pr-b-act"><span class="pr-bdot"></span>{{ __('Active') }}</span>
                                        @else
                                            <span class="pr-badge pr-b-lock"><span class="pr-bdot"></span>{{ __('Inactive') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="pr-row-act">
                                            <button class="pr-ibtn" title="{{ __('Edit') }}">✎</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="pr-em" style="margin-bottom:20px">{{ __('No pension schemes configured yet.') }}</p>
            @endif

            {{-- Create Pension Scheme form --}}
            <div x-data="{ showForm: false }">
                <button class="pr-btn pr-btn-cta pr-btn-sm" @click="showForm = !showForm" x-show="!showForm">
                    {{ __('＋ Create Pension Scheme') }}
                </button>

                <div x-show="showForm" x-cloak style="margin-top:16px">
                    <form method="POST" action="{{ route('accounting.payroll.statutory.store') }}">
                        @csrf
                        <input type="hidden" name="type" value="pension">
                        <div class="pr-fgrid">
                            <div class="pr-field">
                                <label>{{ __('Scheme Name') }} <span class="pr-req">*</span></label>
                                <input class="pr-field-in" type="text" name="name" required placeholder="{{ __('e.g. National Pension Scheme') }}">
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Registration Number') }}</label>
                                <input class="pr-field-in" type="text" name="registration_number" placeholder="{{ __('Optional') }}">
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Employee Rate (%)') }} <span class="pr-req">*</span></label>
                                <input class="pr-field-in" type="number" name="employee_rate" step="0.01" min="0" max="100" required placeholder="{{ __('e.g. 5') }}">
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Employer Rate (%)') }} <span class="pr-req">*</span></label>
                                <input class="pr-field-in" type="number" name="employer_rate" step="0.01" min="0" max="100" required placeholder="{{__('e.g. 10')}}">
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Max Contributory Salary') }} <span class="pr-opt">{{ __('optional') }}</span></label>
                                <input class="pr-field-in" type="number" name="max_contributory_salary" step="0.01" min="0" placeholder="{{ __('No limit') }}">
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Effective From') }}</label>
                                <input class="pr-field-in" type="date" name="effective_from">
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Effective To') }}</label>
                                <input class="pr-field-in" type="date" name="effective_to">
                            </div>
                        </div>
                        <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:24px">
                            <button type="button" class="pr-btn pr-btn-ghost pr-btn-sm" @click="showForm = false">{{ __('Cancel') }}</button>
                            <button type="submit" class="pr-btn pr-btn-cta pr-btn-sm">{{ __('Save Pension Scheme') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

</div>

@push('scripts')
<script>
function payeTableForm() {
    var _k = 0;
    return {
        showForm: false,
        bands: [
            { threshold: '0.00', upper_limit: '', rate: '0.00', _k: _k++ }
        ],
        fmt: function(n) {
            return n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        },
        parse: function(v) {
            return parseFloat(String(v).replace(/,/g, '')) || 0;
        },
        cumulative: function(index) {
            var cum = 0;
            for (var i = 0; i < index; i++) {
                var lower = this.parse(this.bands[i].threshold);
                var upper = this.parse(this.bands[i].upper_limit);
                var rate  = this.parse(this.bands[i].rate);
                if (upper > 0) cum += (rate / 100) * (upper - lower);
            }
            return this.fmt(cum);
        },
        addBand: function() {
            var last = this.bands[this.bands.length - 1];
            var lower = last ? (this.parse(last.upper_limit) || this.parse(last.threshold)) : 0;
            this.bands.push({
                threshold: this.fmt(lower),
                upper_limit: '',
                rate: '0.00',
                _k: _k++
            });
        },
        removeBand: function(index) {
            if (this.bands.length > 1) this.bands.splice(index, 1);
        },
        moveUp: function(index) {
            if (index > 0) {
                var tmp = this.bands[index];
                this.bands.splice(index, 1);
                this.bands.splice(index - 1, 0, tmp);
            }
        },
        moveDown: function(index) {
            if (index < this.bands.length - 1) {
                var tmp = this.bands[index];
                this.bands.splice(index, 1);
                this.bands.splice(index + 1, 0, tmp);
            }
        },
        submit: function() {
            this.bands.forEach(function(b) {
                var p = function(v) { return parseFloat(String(v).replace(/,/g, '')) || 0; };
                b.threshold   = String(p(b.threshold));
                b.upper_limit = b.upper_limit === '' ? '' : String(p(b.upper_limit));
                b.rate        = String(p(b.rate));
            });
            var self = this;
            this.$nextTick(function() { self.$refs.formEl.submit(); });
        }
    };
}
</script>
@endpush
</x-app-layout>
