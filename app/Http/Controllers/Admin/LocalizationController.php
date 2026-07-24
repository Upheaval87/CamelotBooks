<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class LocalizationController extends Controller
{
    private const GROUP = 'localization';

    private const DATE_FORMATS = [
        'Y-m-d' => 'YYYY-MM-DD (2026-01-15)',
        'd/m/Y' => 'DD/MM/YYYY (15/01/2026)',
        'm/d/Y' => 'MM/DD/YYYY (01/15/2026)',
        'd-M-Y' => 'DD-Mon-YYYY (15-Jan-2026)',
        'd M Y' => 'DD Month YYYY (15 January 2026)',
    ];

    private const NUMBER_FORMATS = [
        '1,234.56' => '1,234.56 (dot decimal, comma thousands)',
        '1.234,56' => '1.234,56 (comma decimal, dot thousands)',
        '1 234.56' => '1 234.56 (dot decimal, space thousands)',
    ];

    private const TIMEZONES = [
        'UTC' => 'UTC',
        'Africa/Blantyre' => 'Africa/Blantyre (CAT)',
        'Africa/Johannesburg' => 'Africa/Johannesburg (SAST)',
        'Africa/Nairobi' => 'Africa/Nairobi (EAT)',
        'Europe/London' => 'Europe/London (GMT/BST)',
        'America/New_York' => 'America/New_York (EST/EDT)',
        'America/Chicago' => 'America/Chicago (CST/CDT)',
        'America/Denver' => 'America/Denver (MST/MDT)',
        'America/Los_Angeles' => 'America/Los_Angeles (PST/PDT)',
        'Asia/Dubai' => 'Asia/Dubai (GST)',
        'Asia/Kolkata' => 'Asia/Kolkata (IST)',
    ];

    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $settings = SystemSetting::getMany(self::GROUP, $companyId);

        return view('admin.localization.index', compact('settings'))
            ->with('dateFormats', self::DATE_FORMATS)
            ->with('numberFormats', self::NUMBER_FORMATS)
            ->with('timezones', self::TIMEZONES);
    }

    public function update(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $validated = $request->validate([
            'date_format' => 'required|string',
            'number_format' => 'required|string',
            'timezone' => 'required|string',
            'currency_display' => 'nullable|in:symbol,code,none',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::setValue(self::GROUP, $key, $value, $companyId);
        }

        return redirect()->route('admin.localization.index')->with('success', 'Localization settings updated successfully.');
    }
}
