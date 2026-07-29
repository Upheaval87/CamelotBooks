<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\SystemSetting;
use Illuminate\Http\Request;

class NotificationSettingsController extends Controller
{
    private const SMTP_GROUP = 'smtp';

    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $smtpSettings = SystemSetting::getMany(self::SMTP_GROUP, $companyId);

        $templates = EmailTemplate::where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        })->get();

        $eventLabels = EmailTemplate::eventLabels();

        return view('admin.notifications.index', compact('smtpSettings', 'templates', 'eventLabels'));
    }

    public function update(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $validated = $request->validate([
            'smtp.host' => 'nullable|string|max:255',
            'smtp.port' => 'nullable|integer|min:1|max:65535',
            'smtp.username' => 'nullable|string|max:255',
            'smtp.password' => 'nullable|string|max:255',
            'smtp.encryption' => 'nullable|in:tls,ssl,none',
            'smtp.from_address' => 'nullable|email|max:255',
            'smtp.from_name' => 'nullable|string|max:255',
        ]);

        foreach ($validated['smtp'] ?? [] as $key => $value) {
            SystemSetting::setValue(self::SMTP_GROUP, $key, $value, $companyId);
        }

        return redirect()->route('admin.notifications.index')->with('success', 'Notification settings updated successfully.');
    }

    public function editTemplate(Request $request, string $template)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $emailTemplate = EmailTemplate::where('company_id', $companyId)
            ->where('event_type', $template)
            ->firstOrCreate([
                'company_id' => $companyId,
                'event_type' => $template,
            ], array_merge(
                EmailTemplate::defaultTemplates()[$template] ?? ['subject' => '', 'body' => ''],
                ['company_id' => $companyId, 'event_type' => $template]
            ));

        $eventLabels = EmailTemplate::eventLabels();

        return view('admin.notifications.template-edit', compact('emailTemplate', 'eventLabels'));
    }

    public function updateTemplate(Request $request, EmailTemplate $template)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($template->company_id === $companyId, 403);
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $validated = $request->validate([
            'subject' => 'required|string|max:255',
            'body' => 'required|string',
            'is_enabled' => 'boolean',
        ]);

        $validated['is_enabled'] = $validated['is_enabled'] ?? false;

        $template->update($validated);

        return redirect()->route('admin.notifications.index')->with('success', 'Email template updated successfully.');
    }
}
