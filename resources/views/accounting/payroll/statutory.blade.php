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
    <div class="pr-formcard" style="margin-bottom:20px">
        <div class="pr-fc-hd">
            <div class="kick">{{ __('Tax Configuration') }}</div>
            <h1>{{ __('PAYE Tax Tables') }}</h1>
            <div class="sub">{{ __('Define income tax bands for statutory PAYE calculations.') }}</div>
        </div>
        <div class="pr-fc-bd">
            {{-- Existing tables --}}
            @if($payeTables->count())
                <div class="pr-li-wrap" style="margin-bottom:20px">
                    <table>
                        <thead>
                            <tr>
                                <th>{{ __('Version Name') }}</th>
                                <th>{{ __('Effective From') }}</th>
                                <th>{{ __('Effective To') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th class="num">{{ __('Bands') }}</th>
                                <th style="width:100px">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($payeTables as $table)
                                <tr>
                                    <td class="pr-mono">{{ $table->version_name }}</td>
                                    <td class="pr-em">{{ $table->effective_from?->format('d M Y') ?? '—' }}</td>
                                    <td class="pr-em">{{ $table->effective_to?->format('d M Y') ?? '—' }}</td>
                                    <td>
                                        @if($table->is_active)
                                            <span class="pr-badge pr-b-act"><span class="pr-bdot"></span>{{ __('Current') }}</span>
                                        @else
                                            <span class="pr-badge pr-b-lock"><span class="pr-bdot"></span>{{ __('Old') }}</span>
                                        @endif
                                    </td>
                                    <td class="pr-numr">{{ $table->bands?->count() ?? 0 }}</td>
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
                <p class="pr-em" style="margin-bottom:20px">{{ __('No PAYE tax tables configured yet.') }}</p>
            @endif

            {{-- Create PAYE Table form --}}
            <div x-data="payeTableForm()">
                <button class="pr-btn pr-btn-cta pr-btn-sm" @click="showForm = !showForm" x-show="!showForm">
                    {{ __('＋ Create PAYE Table') }}
                </button>

                <div x-show="showForm" x-cloak style="margin-top:16px">
                    <form method="POST" action="{{ route('accounting.payroll.statutory.store') }}">
                        @csrf
                        <input type="hidden" name="type" value="paye">
                        <div class="pr-fgrid" style="margin-bottom:20px">
                            <div class="pr-field">
                                <label>{{ __('Version Name') }} <span class="pr-req">*</span></label>
                                <input class="pr-field-in" type="text" name="version_name" required placeholder="{{ __('e.g. 2026 Tax Year') }}">
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Effective From') }} <span class="pr-req">*</span></label>
                                <input class="pr-field-in" type="date" name="effective_from" required>
                            </div>
                            <div class="pr-field">
                                <label>{{ __('Effective To') }}</label>
                                <input class="pr-field-in" type="date" name="effective_to">
                            </div>
                        </div>

                        {{-- Tax Bands --}}
                        <div style="margin-bottom:16px">
                            <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:12px">
                                <span style="font-size:11px;font-weight:800;letter-spacing:.09em;text-transform:uppercase;color:var(--muted)">{{ __('Tax Bands') }}</span>
                                <button type="button" class="pr-btn pr-btn-ghost pr-btn-xs" @click="addBand()">＋ {{ __('Add Band') }}</button>
                            </div>

                            <div class="pr-li-wrap">
                                <table style="min-width:600px">
                                    <thead>
                                        <tr>
                                            <th style="width:60px">{{ __('Order') }}</th>
                                            <th>{{ __('Threshold') }}</th>
                                            <th>{{ __('Upper Limit') }}</th>
                                            <th>{{ __('Rate (%)') }}</th>
                                            <th style="width:60px"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <template x-for="(band, index) in bands" :key="index">
                                            <tr>
                                                <td>
                                                    <input class="pr-field-in" type="number" :name="'bands['+index+'][sort_order]'" x-model="band.sort_order" min="0" style="height:36px;font-size:12px;border-radius:8px">
                                                </td>
                                                <td>
                                                    <input class="pr-field-in" type="number" :name="'bands['+index+'][threshold]'" x-model="band.threshold" step="0.01" min="0" required style="height:36px;font-size:12px;border-radius:8px">
                                                </td>
                                                <td>
                                                    <input class="pr-field-in" type="number" :name="'bands['+index+'][upper_limit]'" x-model="band.upper_limit" step="0.01" min="0" style="height:36px;font-size:12px;border-radius:8px" placeholder="{{ __('No limit') }}">
                                                </td>
                                                <td>
                                                    <input class="pr-field-in" type="number" :name="'bands['+index+'][rate]'" x-model="band.rate" step="0.01" min="0" max="100" required style="height:36px;font-size:12px;border-radius:8px">
                                                </td>
                                                <td>
                                                    <button type="button" class="pr-ibtn" @click="removeBand(index)" title="{{ __('Remove') }}" style="color:var(--red)">✕</button>
                                                </td>
                                            </tr>
                                        </template>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div style="display:flex;gap:10px;justify-content:flex-end">
                            <button type="button" class="pr-btn pr-btn-ghost pr-btn-sm" @click="showForm = false">{{ __('Cancel') }}</button>
                            <button type="submit" class="pr-btn pr-btn-cta pr-btn-sm">{{ __('Save PAYE Table') }}</button>
                        </div>
                    </form>
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
    return {
        showForm: false,
        bands: [
            { threshold: 0, upper_limit: '', rate: 0, sort_order: 0 }
        ],
        addBand() {
            this.bands.push({ threshold: 0, upper_limit: '', rate: 0, sort_order: this.bands.length });
        },
        removeBand(index) {
            if (this.bands.length > 1) {
                this.bands.splice(index, 1);
            }
        }
    };
}
</script>
@endpush
</x-app-layout>
