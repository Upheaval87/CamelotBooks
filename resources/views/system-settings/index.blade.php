<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">System Settings</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if(session('success'))
                <div class="mb-4 p-4 bg-green-100 border border-green-300 text-green-800 rounded-md">{{ session('success') }}</div>
            @endif

            @if($errors->any())
                <div class="mb-4 p-4 bg-red-100 border border-red-300 text-red-800 rounded-md">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- Tab Navigation --}}
            <div class="mb-6 border-b border-gray-200">
                <nav class="flex space-x-8 overflow-x-auto" aria-label="Settings Tabs">
                    <a href="{{ route('system-settings.index', 'company') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'company' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Company Profile
                    </a>
                    <a href="{{ route('system-settings.index', 'regional') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'regional' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Regional Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'currency') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'currency' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Currency Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'accounts') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'accounts' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Account Mappings
                    </a>
                    <a href="{{ route('system-settings.index', 'accounting') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'accounting' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Accounting Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'approval') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'approval' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Approval Settings
                    </a>
                    <a href="{{ route('system-settings.index', 'numbering') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'numbering' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Numbering
                    </a>
                    <a href="{{ route('system-settings.index', 'notifications') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'notifications' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Email
                    </a>
                    <a href="{{ route('system-settings.index', 'branches') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'branches' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Branches
                    </a>
                    <a href="{{ route('system-settings.index', 'fiscal-years') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'fiscal-years' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Fiscal Years
                    </a>
                    <a href="{{ route('system-settings.index', 'data-hub') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'data-hub' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Data Hub
                    </a>
                    <a href="{{ route('system-settings.index', 'import-export') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'import-export' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Import/Export
                    </a>
                    <a href="{{ route('system-settings.index', 'backups') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm {{ $tab === 'backups' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300' }}">
                        Backups
                    </a>
                    <a href="{{ route('system-settings.audit-log') }}"
                       class="whitespace-nowrap py-3 px-1 border-b-2 font-medium text-sm border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300">
                        Audit Log
                    </a>
                </nav>
            </div>

            {{-- Company Profile Tab --}}
            @if($tab === 'company')
            <form method="POST" action="{{ route('system-settings.update-company') }}" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Company Profile</h3>
                        <p class="mt-1 text-sm text-gray-600">Legal and contact information for this company. The logo appears on all printed documents (invoices, statements, payslips).</p>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="name" class="block text-sm font-medium text-gray-700">Company Name *</label>
                                <input type="text" name="name" id="name" value="{{ old('name', $company->name) }}" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="legal_name" class="block text-sm font-medium text-gray-700">Legal Name</label>
                                <input type="text" name="legal_name" id="legal_name" value="{{ old('legal_name', $company->legal_name) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="company_code" class="block text-sm font-medium text-gray-700">Company Code</label>
                                <input type="text" name="company_code" id="company_code" value="{{ old('company_code', $company->company_code) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="tax_id" class="block text-sm font-medium text-gray-700">Tax ID / Registration Number</label>
                                <input type="text" name="tax_id" id="tax_id" value="{{ old('tax_id', $company->tax_id) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div class="md:col-span-2">
                                <label for="address" class="block text-sm font-medium text-gray-700">Address</label>
                                <input type="text" name="address" id="address" value="{{ old('address', $company->address) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="city" class="block text-sm font-medium text-gray-700">City</label>
                                <input type="text" name="city" id="city" value="{{ old('city', $company->city) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="state" class="block text-sm font-medium text-gray-700">State / Province</label>
                                <input type="text" name="state" id="state" value="{{ old('state', $company->state) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="country" class="block text-sm font-medium text-gray-700">Country</label>
                                <input type="text" name="country" id="country" value="{{ old('country', $company->country) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="postal_code" class="block text-sm font-medium text-gray-700">Postal Code</label>
                                <input type="text" name="postal_code" id="postal_code" value="{{ old('postal_code', $company->postal_code) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="phone" class="block text-sm font-medium text-gray-700">Phone</label>
                                <input type="text" name="phone" id="phone" value="{{ old('phone', $company->phone) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="email" class="block text-sm font-medium text-gray-700">Email</label>
                                <input type="email" name="email" id="email" value="{{ old('email', $company->email) }}"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="website" class="block text-sm font-medium text-gray-700">Website</label>
                                <input type="url" name="website" id="website" value="{{ old('website', $company->website ?? '') }}" placeholder="https://example.com"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="fiscal_year_start_month" class="block text-sm font-medium text-gray-700">Fiscal Year Start Month *</label>
                                <select name="fiscal_year_start_month" id="fiscal_year_start_month" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach(range(1, 12) as $m)
                                        <option value="{{ $m }}" {{ old('fiscal_year_start_month', $company->fiscal_year_start_month) == $m ? 'selected' : '' }}>
                                            {{ \Carbon\Carbon::create()->month($m)->format('F') }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Logo --}}
                        <div class="border-t border-gray-200 pt-6">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Company Logo</label>
                            <p class="text-xs text-gray-500 mb-3">Appears on invoices, quotations, statements, payslips, and other printed documents. Recommended: 300x80px, PNG or SVG.</p>
                            <div class="flex items-start gap-4">
                                <div class="shrink-0">
                                    @if($company->logo)
                                        <div class="relative">
                                            <img src="{{ asset('storage/' . $company->logo) }}" alt="Company Logo" class="h-20 w-auto border border-gray-200 rounded p-1 bg-white" />
                                        </div>
                                    @else
                                        <div class="h-20 w-32 border-2 border-dashed border-gray-300 rounded flex items-center justify-center text-xs text-gray-400">
                                            No logo
                                        </div>
                                    @endif
                                </div>
                                <div class="flex-1">
                                    <input type="file" name="logo" id="logo" accept="image/*"
                                        class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                    @if($company->logo)
                                        <label class="inline-flex items-center gap-1 mt-2 text-xs text-red-600 cursor-pointer">
                                            <input type="checkbox" name="remove_logo" value="1" class="rounded border-gray-300 text-red-600" />
                                            Remove current logo
                                        </label>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Save Company Profile
                        </button>
                    </div>
                </div>
            </form>
            @endif

            {{-- Regional Settings Tab --}}
            @if($tab === 'regional')
            <form method="POST" action="{{ route('system-settings.update-regional') }}">
                @csrf
                @method('PUT')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Regional Settings</h3>
                        <p class="mt-1 text-sm text-gray-600">Language, timezone, and formatting preferences for this company.</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="reg_country" class="block text-sm font-medium text-gray-700">Country</label>
                                <input type="text" name="country" id="reg_country" value="{{ old('country', $regional['country'] ?? $company->country ?? '') }}"
                                    placeholder="e.g. Malawi, Kenya, United States"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                            </div>
                            <div>
                                <label for="language" class="block text-sm font-medium text-gray-700">Language</label>
                                <select name="language" id="language"
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach(['en' => 'English', 'fr' => 'French', 'es' => 'Spanish', 'pt' => 'Portuguese', 'sw' => 'Swahili', 'zh' => 'Chinese', 'ar' => 'Arabic'] as $code => $label)
                                        <option value="{{ $code }}" {{ old('language', $regional['language'] ?? 'en') === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="timezone" class="block text-sm font-medium text-gray-700">Timezone *</label>
                                <select name="timezone" id="timezone" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach([
                                        'UTC' => 'UTC',
                                        'Africa/Blantyre' => 'Africa/Blantyre (CAT)',
                                        'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
                                        'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
                                        'Africa/Lagos' => 'Africa/Lagos (WAT)',
                                        'Africa/Cairo' => 'Africa/Cairo (EET)',
                                        'Europe/London' => 'Europe/London (GMT/BST)',
                                        'Europe/Paris' => 'Europe/Paris (CET/CEST)',
                                        'America/New_York' => 'America/New_York (EST/EDT)',
                                        'America/Chicago' => 'America/Chicago (CST/CDT)',
                                        'America/Denver' => 'America/Denver (MST/MDT)',
                                        'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
                                        'Asia/Dubai' => 'Asia/Dubai (GST)',
                                        'Asia/Kolkata' => 'Asia/Kolkata (IST)',
                                        'Asia/Shanghai' => 'Asia/Shanghai (CST)',
                                        'Asia/Tokyo' => 'Asia/Tokyo (JST)',
                                        'Australia/Sydney' => 'Australia/Sydney (AEST/AEDT)',
                                    ] as $tz => $tzLabel)
                                        <option value="{{ $tz }}" {{ old('timezone', $regional['timezone'] ?? 'UTC') === $tz ? 'selected' : '' }}>{{ $tzLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="date_format" class="block text-sm font-medium text-gray-700">Date Format *</label>
                                <select name="date_format" id="date_format" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach([
                                        'Y-m-d' => 'YYYY-MM-DD (2026-01-15)',
                                        'd/m/Y' => 'DD/MM/YYYY (15/01/2026)',
                                        'm/d/Y' => 'MM/DD/YYYY (01/15/2026)',
                                        'd-M-Y' => 'DD-Mon-YYYY (15-Jan-2026)',
                                        'd M Y' => 'DD Month YYYY (15 January 2026)',
                                    ] as $fmt => $fmtLabel)
                                        <option value="{{ $fmt }}" {{ old('date_format', $regional['date_format'] ?? 'Y-m-d') === $fmt ? 'selected' : '' }}>{{ $fmtLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="time_format" class="block text-sm font-medium text-gray-700">Time Format *</label>
                                <select name="time_format" id="time_format" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="24h" {{ old('time_format', $regional['time_format'] ?? '24h') === '24h' ? 'selected' : '' }}>24-hour (14:30)</option>
                                    <option value="12h" {{ old('time_format', $regional['time_format'] ?? '24h') === '12h' ? 'selected' : '' }}>12-hour (2:30 PM)</option>
                                </select>
                            </div>
                            <div>
                                <label for="first_day_of_week" class="block text-sm font-medium text-gray-700">First Day of Week *</label>
                                <select name="first_day_of_week" id="first_day_of_week" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach([0 => 'Sunday', 1 => 'Monday', 6 => 'Saturday'] as $day => $dayLabel)
                                        <option value="{{ $day }}" {{ old('first_day_of_week', $regional['first_day_of_week'] ?? '1') == $day ? 'selected' : '' }}>{{ $dayLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Save Regional Settings
                        </button>
                    </div>
                </div>
            </form>
            @endif

            {{-- Currency Settings Tab --}}
            @if($tab === 'currency')
            <form method="POST" action="{{ route('system-settings.update-currency') }}">
                @csrf
                @method('PUT')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Currency Settings</h3>
                        <p class="mt-1 text-sm text-gray-600">Configure the base currency and display preferences for monetary values.</p>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="base_currency" class="block text-sm font-medium text-gray-700">Base Currency *</label>
                                <select name="base_currency" id="base_currency" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach([
                                        'USD' => 'USD - US Dollar',
                                        'EUR' => 'EUR - Euro',
                                        'GBP' => 'GBP - British Pound',
                                        'MWK' => 'MWK - Malawian Kwacha',
                                        'KES' => 'KES - Kenyan Shilling',
                                        'ZMW' => 'ZMW - Zambian Kwacha',
                                        'ZWL' => 'ZWL - Zimbabwean Dollar',
                                        'PHP' => 'PHP - Philippine Peso',
                                        'JPY' => 'JPY - Japanese Yen',
                                        'INR' => 'INR - Indian Rupee',
                                        'ZAR' => 'ZAR - South African Rand',
                                        'BWP' => 'BWP - Botswana Pula',
                                        'TZS' => 'TZS - Tanzanian Shilling',
                                        'UGX' => 'UGX - Ugandan Shilling',
                                        'NGN' => 'NGN - Nigerian Naira',
                                        'GHS' => 'GHS - Ghanaian Cedi',
                                        'CAD' => 'CAD - Canadian Dollar',
                                        'AUD' => 'AUD - Australian Dollar',
                                        'CHF' => 'CHF - Swiss Franc',
                                        'CNY' => 'CNY - Chinese Yuan',
                                    ] as $code => $label)
                                        <option value="{{ $code }}" {{ old('base_currency', $company->base_currency) === $code ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">All journal entries balance in this currency. Foreign currency transactions are converted at the exchange rate.</p>
                            </div>
                            <div>
                                <label for="decimal_places" class="block text-sm font-medium text-gray-700">Decimal Places for Display *</label>
                                <select name="decimal_places" id="decimal_places" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    @foreach([0, 2, 3, 4] as $dp)
                                        <option value="{{ $dp }}" {{ old('decimal_places', $currency['decimal_places'] ?? '2') == $dp ? 'selected' : '' }}>
                                            {{ $dp }}{{ $dp === 0 ? ' (whole numbers)' : ($dp === 2 ? ' (standard)' : '') }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Number of decimal places for display purposes. Journals always use full precision.</p>
                            </div>
                            <div>
                                <label for="rate_source" class="block text-sm font-medium text-gray-700">Exchange Rate Source *</label>
                                <select name="rate_source" id="rate_source" required
                                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                                    <option value="manual" {{ old('rate_source', $currency['rate_source'] ?? 'manual') === 'manual' ? 'selected' : '' }}>Manual Entry Only</option>
                                </select>
                                <p class="mt-1 text-xs text-gray-500">Currently, exchange rates are entered manually via the Exchange Rates screen. Live rate feeds are not yet available.</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Save Currency Settings
                        </button>
                    </div>
                </div>
            </form>
            @endif

            {{-- Account Mappings Tab --}}
            @if($tab === 'accounts')
            <form method="POST" action="{{ route('system-settings.update-account-mappings') }}">
                @csrf
                @method('PUT')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Default Account Mappings</h3>
                        <p class="mt-1 text-sm text-gray-600">Map system operations to your Chart of Accounts. Every journal entry posted by the system uses these mappings. If a mapping is empty, the relevant operation will fail until one is assigned.</p>
                    </div>
                    <div class="p-6">
                        @php
                            $accountOptions = $accounts->mapWithKeys(fn($a) => [$a->id => "{$a->code} — {$a->name}"])->toArray();
                        @endphp
                        <div class="space-y-4">
                            @foreach(DefaultAccountMapping::availableKeys() as $key => $label)
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 items-center py-2 border-b border-gray-100 last:border-0">
                                <div class="md:col-span-1">
                                    <label class="block text-sm font-medium text-gray-700">{{ $label }}</label>
                                    <span class="text-xs text-gray-400">{{ $key }}</span>
                                </div>
                                <div class="md:col-span-2">
                                    <select name="{{ $key }}" class="block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm">
                                        <option value="">— Not mapped —</option>
                                        @foreach($accountOptions as $id => $label)
                                            <option value="{{ $id }}" {{ ($mappings[$key] ?? null) == $id ? 'selected' : '' }}>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Save Account Mappings
                        </button>
                    </div>
                </div>
            </form>
            @endif

            {{-- Accounting Settings Tab --}}
            @if($tab === 'accounting')
            <form method="POST" action="{{ route('system-settings.update-accounting') }}">
                @csrf
                @method('PUT')
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h3 class="text-lg font-medium text-gray-900">Accounting Settings</h3>
                        <p class="mt-1 text-sm text-gray-600">Company-wide accounting controls and defaults.</p>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input type="hidden" name="mandatory_narration" value="0">
                                    <input type="checkbox" name="mandatory_narration" value="1" id="mandatory_narration"
                                        {{ old('mandatory_narration', $accounting['mandatory_narration'] ?? '0') == '1' ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label for="mandatory_narration" class="block text-sm font-medium text-gray-700">Mandatory Narration on Journal Entries</label>
                                    <p class="text-xs text-gray-500 mt-0.5">Require a description/memo on every journal entry before posting.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input type="hidden" name="enforce_credit_limit" value="0">
                                    <input type="checkbox" name="enforce_credit_limit" value="1" id="enforce_credit_limit"
                                        {{ old('enforce_credit_limit', $accounting['enforce_credit_limit'] ?? '0') == '1' ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label for="enforce_credit_limit" class="block text-sm font-medium text-gray-700">Enforce Customer Credit Limits</label>
                                    <p class="text-xs text-gray-500 mt-0.5">Block new invoices when a customer exceeds their credit limit.</p>
                                </div>
                            </div>
                            <div class="flex items-start gap-3 p-4 bg-gray-50 rounded-lg">
                                <div class="flex-shrink-0 mt-0.5">
                                    <input type="hidden" name="allow_negative_inventory" value="0">
                                    <input type="checkbox" name="allow_negative_inventory" value="1" id="allow_negative_inventory"
                                        {{ old('allow_negative_inventory', $accounting['allow_negative_inventory'] ?? ($company->allow_negative_stock ? '1' : '0')) == '1' ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                </div>
                                <div>
                                    <label for="allow_negative_inventory" class="block text-sm font-medium text-gray-700">Allow Negative Inventory</label>
                                    <p class="text-xs text-gray-500 mt-0.5">Permit selling items when stock is at zero or below. Disabled by default.</p>
                                </div>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-lg">
                                <label for="rounding_tolerance" class="block text-sm font-medium text-gray-700">Rounding Tolerance ({{ \App\Models\SystemSetting::getValue('localization', 'currency_symbol', session('current_company_id'), '$') }})</label>
                                <input type="number" step="0.01" min="0" max="10" name="rounding_tolerance" id="rounding_tolerance"
                                    value="{{ old('rounding_tolerance', $accounting['rounding_tolerance'] ?? '0.05') }}"
                                    class="mt-1 block w-32 border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" />
                                <p class="text-xs text-gray-500 mt-1">Max amount a journal entry can be off due to rounding before being rejected. Default: 0.05</p>
                            </div>
                        </div>
                        <div class="p-4 bg-blue-50 rounded-lg border border-blue-200">
                            <p class="text-sm text-blue-800"><strong>Note:</strong> Posting to closed accounting periods is always blocked by period locking — this is a hard rule and cannot be bypassed.</p>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                        <button type="submit" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 transition">
                            Save Accounting Settings
                        </button>
                    </div>
                </div>
            </form>
            @endif

            {{-- Approval Settings Tab --}}
            @if($tab === 'approval')
            @include('system-settings._approval-tab')
            @endif

            {{-- Numbering Overrides Tab --}}
            @if($tab === 'numbering')
            @include('system-settings._numbering-tab')
            @endif

            {{-- Email & Notifications Tab --}}
            @if($tab === 'notifications')
            @include('system-settings._notifications-tab')
            @endif

            {{-- Branch Management Tab --}}
            @if($tab === 'branches')
            @include('system-settings._branches-tab')
            @endif

            {{-- Fiscal Years Tab --}}
            @if($tab === 'fiscal-years')
            @include('system-settings._fiscal-years-tab')
            @endif

            {{-- Data Hub Tab --}}
            @if($tab === 'data-hub')
            @include('system-settings._data-hub-tab')
            @endif

            {{-- Import/Export Tab --}}
            @if($tab === 'import-export')
            @include('system-settings._import-export-tab')
            @endif

            {{-- Backups Tab --}}
            @if($tab === 'backups')
            @include('system-settings._backups-tab')
            @endif
        </div>
    </div>
</x-app-layout>
