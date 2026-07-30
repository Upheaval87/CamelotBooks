<form method="POST" action="{{ route('system-settings.update-regional') }}">
    @csrf
    @method('PUT')

    <div class="settings-section-header">
        <div class="settings-section-eyebrow">02 · REGIONAL SETTINGS</div>
        <div class="settings-section-title">Regional Settings</div>
        <p class="settings-section-desc">Language, timezone, and formatting preferences for this company.</p>
        <hr class="settings-section-divider">
    </div>

    <div class="settings-card">
        <div class="settings-grid">
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

    <div class="flex justify-end">
        <button type="submit" class="btn-primary">Save Regional Settings</button>
    </div>
</form>
