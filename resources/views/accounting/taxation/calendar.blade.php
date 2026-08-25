<x-app-layout>
    <div class="max-w-8xl mx-auto sm:px-6 lg:px-8 py-6 tx-wrap">
        <div class="tx-opt-tag">{{ __('4') }} &middot; {{ __('Compliance Calendar & Obligations') }}</div>

        <div class="tx-page-head">
            <div>
                <h1>{{ __('Compliance Calendar') }}</h1>
                <p class="sub">{{ __('Filing deadlines, payment obligations and compliance tracking.') }}</p>
            </div>
        </div>

        @php
            $overdue = $obligations->where('status', 'overdue');
            $urgent = $obligations->where('status', 'urgent');
            $upcoming = $obligations->where('status', 'upcoming');
            $future = $obligations->where('status', 'future');
            $completed = $obligations->where('status', 'completed');
        @endphp

        <div class="tx-chips">
            <div class="tx-chipbox" style="border-color:rgba(185,28,28,.35);background:rgba(185,28,28,.04)">
                <span class="l">{{ __('Overdue') }}</span>
                <span class="v" style="color:var(--red-2,#b91c1c)">{{ $overdue->count() }}</span>
            </div>
            <div class="tx-chipbox warn">
                <span class="l">{{ __('Due This Week') }}</span>
                <span class="v" style="color:var(--amber-2,#b45309)">{{ $urgent->count() }}</span>
            </div>
            <div class="tx-chipbox">
                <span class="l">{{ __('Upcoming') }}</span>
                <span class="v">{{ $upcoming->count() }}</span>
            </div>
            <div class="tx-chipbox">
                <span class="l">{{ __('Completed') }}</span>
                <span class="v" style="color:var(--green,#15803d)">{{ $completed->count() }}</span>
            </div>
        </div>

        @if ($overdue->isNotEmpty())
            <div class="tx-card" style="margin-bottom:16px">
                <div class="tx-card-h">
                    <span class="ic" style="background:rgba(185,28,28,.1);color:var(--red-2,#b91c1c)">&#9888;</span>
                    <h2>{{ __('Overdue') }}</h2>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:760px">
                        <thead>
                            <tr>
                                <th>{{ __('Period') }}</th>
                                <th>{{ __('Tax Type') }}</th>
                                <th>{{ __('Filing Due') }}</th>
                                <th class="num">{{ __('Days Overdue') }}</th>
                                <th>{{ __('Period Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($overdue as $item)
                                <tr>
                                    <td class="tx-name">{{ $item['label'] }}</td>
                                    <td><span class="tx-tchip tx-t-vat">{{ $item['tax_type_name'] ?? $item['tax_type_code'] }}</span></td>
                                    <td class="tx-em">{{ \Illuminate\Support\Carbon::parse($item['filing_due_date'])->format('d M Y') }}</td>
                                    <td class="num" style="color:var(--red-2,#b91c1c)">{{ abs($item['days_left']) }}d</td>
                                    <td class="tx-em">{{ $item['period_status'] }}</td>
                                    <td class="tx-row-act">
                                        <a href="{{ $item['working_paper_url'] }}" class="tx-btn tx-btn-sm tx-btn-ghost">{{ __('File Now') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($urgent->isNotEmpty())
            <div class="tx-card" style="margin-bottom:16px">
                <div class="tx-card-h">
                    <span class="ic" style="background:rgba(217,119,6,.1);color:var(--amber-2,#b45309)">&#9888;</span>
                    <h2>{{ __('Due This Week') }}</h2>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:760px">
                        <thead>
                            <tr>
                                <th>{{ __('Period') }}</th>
                                <th>{{ __('Tax Type') }}</th>
                                <th>{{ __('Filing Due') }}</th>
                                <th class="num">{{ __('Days Left') }}</th>
                                <th>{{ __('Period Status') }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($urgent as $item)
                                <tr>
                                    <td class="tx-name">{{ $item['label'] }}</td>
                                    <td><span class="tx-tchip tx-t-vat">{{ $item['tax_type_name'] ?? $item['tax_type_code'] }}</span></td>
                                    <td class="tx-em">{{ \Illuminate\Support\Carbon::parse($item['filing_due_date'])->format('d M Y') }}</td>
                                    <td class="num" style="color:var(--amber-2,#b45309)">{{ $item['days_left'] }}d</td>
                                    <td class="tx-em">{{ $item['period_status'] }}</td>
                                    <td class="tx-row-act">
                                        <a href="{{ $item['working_paper_url'] }}" class="tx-btn tx-btn-sm tx-btn-ghost">{{ __('Prepare') }}</a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($upcoming->isNotEmpty() || $future->isNotEmpty())
            <div class="tx-card" style="margin-bottom:16px">
                <div class="tx-card-h">
                    <span class="ic">&#128197;</span>
                    <h2>{{ __('Upcoming & Future') }}</h2>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:760px">
                        <thead>
                            <tr>
                                <th>{{ __('Period') }}</th>
                                <th>{{ __('Tax Type') }}</th>
                                <th>{{ __('Filing Due') }}</th>
                                <th class="num">{{ __('Days Left') }}</th>
                                <th>{{ __('Period Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($upcoming->merge($future) as $item)
                                <tr>
                                    <td class="tx-name">{{ $item['label'] }}</td>
                                    <td><span class="tx-tchip tx-t-vat">{{ $item['tax_type_name'] ?? $item['tax_type_code'] }}</span></td>
                                    <td class="tx-em">{{ \Illuminate\Support\Carbon::parse($item['filing_due_date'])->format('d M Y') }}</td>
                                    <td class="num">{{ $item['days_left'] }}d</td>
                                    <td class="tx-em">{{ $item['period_status'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($completed->isNotEmpty())
            <div class="tx-card">
                <div class="tx-card-h">
                    <span class="ic" style="background:rgba(22,163,74,.1);color:var(--green,#15803d)">&#10003;</span>
                    <h2>{{ __('Completed') }}</h2>
                </div>
                <div class="tx-li-wrap">
                    <table class="tx-table" style="min-width:760px">
                        <thead>
                            <tr>
                                <th>{{ __('Period') }}</th>
                                <th>{{ __('Tax Type') }}</th>
                                <th>{{ __('Filing Due') }}</th>
                                <th>{{ __('Period Status') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($completed as $item)
                                <tr>
                                    <td class="tx-name">{{ $item['label'] }}</td>
                                    <td><span class="tx-tchip tx-t-vat">{{ $item['tax_type_name'] ?? $item['tax_type_code'] }}</span></td>
                                    <td class="tx-em">{{ \Illuminate\Support\Carbon::parse($item['filing_due_date'])->format('d M Y') }}</td>
                                    <td><span class="tx-badge tx-b-ok"><span class="bdot"></span>{{ __('Filed') }}</span></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($obligations->isEmpty())
            <div class="tx-card">
                <div class="tx-pad" style="text-align:center;padding:48px 24px;">
                    <p style="color:var(--sub,#41585c);font-size:13.5px;">{{ __('No tax periods with filing deadlines found.') }}</p>
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
