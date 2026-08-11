<div class="sticky-head">
    @include('system-settings._tabnav', ['active' => 'regional'])
    <div>
        <div class="glabel">{{ __('Actions') }}</div>
        <div class="tbtns">
            <button type="submit" form="regional-form" class="btn cta">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                {{ __('Save Regional Settings') }}
            </button>
        </div>
    </div>
</div>

<form method="POST" action="{{ route('system-settings.update-regional') }}" id="regional-form">
    @csrf
    @method('PUT')

    <div class="card">
        <div class="card-sec">
            <div class="sec-head">
                <span class="sec-ic"><svg fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></span>
                <h2>{{ __('Regional Settings') }}</h2>
                <div class="rule"></div>
            </div>
            <p class="sub">Language, timezone, and formatting preferences for this company.</p>

            <div class="g3">
                <x-settings.field label="Country" name="country" type="text" :value="old('country', $regional['country'] ?? $company->country ?? '')" placeholder="e.g. Malawi, Kenya, United States" />
                <x-settings.field label="Language" name="language" type="select">
                    @foreach(['en' => 'English', 'fr' => 'French', 'es' => 'Spanish', 'pt' => 'Portuguese', 'sw' => 'Swahili', 'zh' => 'Chinese', 'ar' => 'Arabic'] as $code => $label)
                        <option value="{{ $code }}" {{ old('language', $regional['language'] ?? 'en') === $code ? 'selected' : '' }}>{{ $label }}</option>
                    @endforeach
                </x-settings.field>
                <x-settings.field label="Timezone" name="timezone" type="select" required>
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
                </x-settings.field>
                <x-settings.field label="Date Format" name="date_format" type="select" required>
                    @foreach([
                        'Y-m-d' => 'YYYY-MM-DD (2026-01-15)',
                        'd/m/Y' => 'DD/MM/YYYY (15/01/2026)',
                        'm/d/Y' => 'MM/DD/YYYY (01/15/2026)',
                        'd-M-Y' => 'DD-Mon-YYYY (15-Jan-2026)',
                        'd M Y' => 'DD Month YYYY (15 January 2026)',
                    ] as $fmt => $fmtLabel)
                        <option value="{{ $fmt }}" {{ old('date_format', $regional['date_format'] ?? 'Y-m-d') === $fmt ? 'selected' : '' }}>{{ $fmtLabel }}</option>
                    @endforeach
                </x-settings.field>
                <x-settings.field label="Time Format" name="time_format" type="select" required>
                    <option value="24h" {{ old('time_format', $regional['time_format'] ?? '24h') === '24h' ? 'selected' : '' }}>24-hour (14:30)</option>
                    <option value="12h" {{ old('time_format', $regional['time_format'] ?? '24h') === '12h' ? 'selected' : '' }}>12-hour (2:30 PM)</option>
                </x-settings.field>
                <x-settings.field label="First Day of Week" name="first_day_of_week" type="select" required>
                    @foreach([0 => 'Sunday', 1 => 'Monday', 6 => 'Saturday'] as $day => $dayLabel)
                        <option value="{{ $day }}" {{ old('first_day_of_week', $regional['first_day_of_week'] ?? '1') == $day ? 'selected' : '' }}>{{ $dayLabel }}</option>
                    @endforeach
                </x-settings.field>
                <x-settings.field label="Number Format" name="number_format" type="select" hint="Controls how numbers are formatted for display.">
                    @foreach([
                        '1,234.56' => '1,234.56 (dot decimal, comma thousands)',
                        '1.234,56' => '1.234,56 (comma decimal, dot thousands)',
                        '1 234.56' => '1 234.56 (dot decimal, space thousands)',
                    ] as $fmt => $fmtLabel)
                        <option value="{{ $fmt }}" {{ old('number_format', $regional['number_format'] ?? '1,234.56') === $fmt ? 'selected' : '' }}>{{ $fmtLabel }}</option>
                    @endforeach
                </x-settings.field>
            </div>
        </div>
    </div>
</form>
