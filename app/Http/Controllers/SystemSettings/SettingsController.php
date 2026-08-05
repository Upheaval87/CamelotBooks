<?php

namespace App\Http\Controllers\SystemSettings;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\ApprovalSetting;
use App\Models\ApprovalThreshold;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\EmailTemplate;
use App\Models\NumberingSequence;
use App\Models\SystemSetting;
use App\Services\FeatureManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index(Request $request, string $tab = 'company')
    {
        $companyId = session('current_company_id');
        $company = Company::findOrFail($companyId);
        $regional = SystemSetting::getMany('regional', $companyId);
        $currency = SystemSetting::getMany('currency', $companyId);
        $accounting = SystemSetting::getMany('accounting', $companyId);
        $accounts = Account::where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get();
        $mappings = DefaultAccountMapping::getAll($companyId);
        $approvalSetting = ApprovalSetting::firstOrCreate(['company_id' => $companyId], ['requires_approval' => false, 'threshold_amount' => 0]);
        $approvalThresholds = ApprovalThreshold::getAllForCompany($companyId);
        $sequences = NumberingSequence::where('company_id', $companyId)->orderBy('document_type')->get();
        $documentTypeLabels = NumberingSequence::documentTypeLabels();
        $notifications = SystemSetting::getMany('notifications', $companyId);
        $emailTemplates = EmailTemplate::where(function ($q) use ($companyId) {
            $q->where('company_id', $companyId)->orWhereNull('company_id');
        })->get();
        $eventLabels = EmailTemplate::eventLabels();

        return view('system-settings.index', compact(
            'company', 'regional', 'currency', 'accounting', 'accounts', 'mappings',
            'approvalSetting', 'approvalThresholds',
            'sequences', 'documentTypeLabels',
            'notifications', 'emailTemplates', 'eventLabels',
            'tab'
        ));
    }

    public function logs(Request $request)
    {
        $companyId = session('current_company_id');
        $company = Company::findOrFail($companyId);

        $query = AuditLog::where('company_id', $companyId)
            ->where('auditable_type', Company::class)
            ->where('action', 'settings.updated')
            ->with('user');

        if ($group = $request->input('group')) {
            $query->where('notes', $group);
        }

        if ($userId = $request->input('user_id')) {
            $query->where('user_id', $userId);
        }

        if ($from = $request->input('from')) {
            $query->where('created_at', '>=', $from);
        }

        if ($to = $request->input('to')) {
            $query->where('created_at', '<=', $to . ' 23:59:59');
        }

        $logs = $query->orderBy('created_at', 'desc')->paginate(25)->withQueryString();
        $users = \App\Models\User::whereHas('companies', fn($q) => $q->where('company_id', $companyId))->orderBy('name')->get();

        $groups = [
            'Company Profile',
            'Regional Settings',
            'Currency Settings',
            'Account Mappings',
            'Accounting Settings',
            'Approval Settings',
        ];

        return view('system-settings.audit-log', compact('logs', 'users', 'groups', 'company')); 
    }

    public function updateCompany(Request $request)
    {
        $companyId = session('current_company_id');
        $company = Company::findOrFail($companyId);

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $oldValues = $company->only([
            'name', 'legal_name', 'company_code', 'tax_id', 'address', 'city',
            'state', 'country', 'postal_code', 'phone', 'email', 'website',
            'fiscal_year_start_month', 'logo',
        ]);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'legal_name' => 'nullable|string|max:255',
            'company_code' => 'nullable|string|max:50|unique:companies,company_code,' . $companyId,
            'tax_id' => 'nullable|string|max:100',
            'address' => 'nullable|string|max:500',
            'city' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'fiscal_year_start_month' => 'required|integer|min:1|max:12',
        ]);

        if ($request->hasFile('logo')) {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validated['logo'] = $request->file('logo')->store('company-logos', 'public');
        } elseif ($request->input('remove_logo') === '1') {
            if ($company->logo) {
                Storage::disk('public')->delete($company->logo);
            }
            $validated['logo'] = null;
        } else {
            unset($validated['logo']);
        }

        $company->update($validated);

        $newValues = $company->only(array_keys($validated));
        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $oldValues, $newValues, 'Company Profile');

        return redirect()->route('system-settings.index', 'company')
            ->with('success', 'Company profile updated successfully.');
    }

    public function updateRegional(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $oldValues = SystemSetting::getMany('regional', $companyId);

        $validated = $request->validate([
            'country' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:10',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:10',
            'first_day_of_week' => 'required|integer|min:0|max:6',
            'number_format' => 'nullable|string|max:30',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::setValue('regional', $key, $value, $companyId);
        }

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $oldValues, $validated, 'Regional Settings');

        return redirect()->route('system-settings.index', 'regional')
            ->with('success', 'Regional settings updated successfully.');
    }

    public function updateCurrency(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $oldValues = array_merge(
            ['base_currency' => Company::findOrFail($companyId)->base_currency],
            SystemSetting::getMany('currency', $companyId)
        );

        $validated = $request->validate([
            'base_currency' => 'required|string|max:10',
            'decimal_places' => 'required|integer|min:0|max:4',
            'rate_source' => 'required|string|max:30',
            'currency_symbol' => 'nullable|string|max:10',
        ]);

        Company::where('id', $companyId)->update(['base_currency' => $validated['base_currency']]);

        foreach (['decimal_places', 'rate_source', 'currency_symbol'] as $key) {
            SystemSetting::setValue('currency', $key, $validated[$key], $companyId);
        }

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $oldValues, $validated, 'Currency Settings');

        return redirect()->route('system-settings.index', 'currency')
            ->with('success', 'Currency settings updated successfully.');
    }

    public function updateAccountMappings(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $oldMappings = DefaultAccountMapping::getAll($companyId);
        $oldAccountNames = collect($oldMappings)->mapWithKeys(function ($accountId, $key) {
            $account = Account::find($accountId);
            return [$key => $account ? "{$account->code} — {$account->name}" : null];
        })->toArray();

        $keys = DefaultAccountMapping::availableKeys();
        $rules = [];
        foreach ($keys as $key => $label) {
            $rules[$key] = 'nullable|integer|exists:accounts,id';
        }

        $validated = $request->validate($rules);

        $changes = [];
        foreach ($validated as $mappingKey => $accountId) {
            $oldAccount = $oldAccountNames[$mappingKey] ?? null;
            $newAccount = null;
            if ($accountId) {
                $account = Account::find($accountId);
                $newAccount = $account ? "{$account->code} — {$account->name}" : null;
            }
            if ($oldAccount !== $newAccount) {
                $changes[$mappingKey] = ['from' => $oldAccount, 'to' => $newAccount];
            }
            if ($accountId) {
                DefaultAccountMapping::setMapping($companyId, $mappingKey, (int) $accountId);
            } else {
                DefaultAccountMapping::where('company_id', $companyId)
                    ->where('mapping_key', $mappingKey)
                    ->delete();
            }
        }

        if (!empty($changes)) {
            AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $oldAccountNames, $changes, 'Account Mappings');
        }

        return redirect()->route('system-settings.index', 'accounts')
            ->with('success', 'Account mappings updated successfully.');
    }

    public function updateApproval(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $approvalSetting = ApprovalSetting::firstOrCreate(['company_id' => $companyId]);

        $oldGlobal = $approvalSetting->only(['requires_approval', 'threshold_amount']);
        $oldThresholds = ApprovalThreshold::getAllForCompany($companyId);

        $validated = $request->validate([
            'requires_approval' => 'required|boolean',
            'threshold_amount' => 'required|numeric|min:0',
        ]);

        $approvalSetting->update([
            'requires_approval' => (bool) $validated['requires_approval'],
            'threshold_amount' => $validated['threshold_amount'],
        ]);

        $docTypes = ApprovalThreshold::documentTypes();
        $newThresholds = [];
        foreach ($docTypes as $type => $label) {
            $amount = (float) ($request->input("thresholds.{$type}") ?? 0);
            $active = $request->boolean("active.{$type}", false);

            $old = $oldThresholds[$type] ?? null;
            $oldAmount = $old ? (float) $old['threshold_amount'] : 0;
            $oldActive = $old ? (bool) $old['is_active'] : false;

            if ($oldAmount != $amount || $oldActive != $active) {
                $newThresholds[$type] = ['threshold' => $amount, 'active' => $active];
            }

            ApprovalThreshold::updateOrCreate(
                ['company_id' => $companyId, 'document_type' => $type],
                ['threshold_amount' => $amount, 'is_active' => $active]
            );
        }

        $auditNew = array_merge(['global' => $validated], $newThresholds);
        $auditOld = array_merge(['global' => $oldGlobal], collect($oldThresholds)->mapWithKeys(fn($t) => [$t['document_type'] => ['threshold' => $t['threshold_amount'], 'active' => $t['is_active']]])->toArray());

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $auditOld, $auditNew, 'Approval Settings');

        return redirect()->route('system-settings.index', 'approval')
            ->with('success', 'Approval settings updated successfully.');
    }

    public function updateNotifications(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $oldValues = SystemSetting::getMany('notifications', $companyId);

        $validated = $request->validate([
            'sender_display_name' => 'nullable|string|max:255',
            'email_footer' => 'nullable|string|max:2000',
            'email_signature' => 'nullable|string|max:2000',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::setValue('notifications', $key, $value, $companyId);
        }

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $oldValues, $validated, 'Email & Notification Settings');

        return redirect()->route('system-settings.index', 'notifications')
            ->with('success', 'Email content settings updated successfully.');
    }

    public function updateAccounting(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $oldValues = SystemSetting::getMany('accounting', $companyId);

        $validated = $request->validate([
            'mandatory_narration' => 'required|boolean',
            'enforce_credit_limit' => 'required|boolean',
            'rounding_tolerance' => 'required|numeric|min:0|max:10',
            'allow_negative_inventory' => 'required|boolean',
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::setValue('accounting', $key, $value, $companyId);
        }

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $oldValues, $validated, 'Accounting Settings');

        return redirect()->route('system-settings.index', 'accounting')
            ->with('success', 'Accounting settings updated successfully.');
    }

    public function exportSettings(Request $request)
    {
        $companyId = session('current_company_id');
        $company = Company::findOrFail($companyId);
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $settings = [
            'exported_at' => now()->toIso8601String(),
            'company_name' => $company->name,
            'company_code' => $company->company_code,
            'version' => '1.0',
            'regional' => SystemSetting::getMany('regional', $companyId),
            'currency' => array_merge(
                ['base_currency' => $company->base_currency],
                SystemSetting::getMany('currency', $companyId)
            ),
            'accounting' => SystemSetting::getMany('accounting', $companyId),
            'account_mappings' => DefaultAccountMapping::getAll($companyId),
            'approval' => $approval = ApprovalSetting::where('company_id', $companyId)->first()?->only(['requires_approval', 'threshold_amount']) ?? [],
            'numbering' => NumberingSequence::where('company_id', $companyId)
                ->get()
                ->mapWithKeys(fn($s) => [$s->document_type => $s->only(['prefix', 'padding_width', 'reset_policy', 'is_active'])])
                ->toArray(),
            'approval_thresholds' => ApprovalThreshold::where('company_id', $companyId)
                ->get()
                ->mapWithKeys(fn($t) => [$t->document_type => $t->only(['threshold_amount', 'is_active'])])
                ->toArray(),
        ];

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.exported', null, null, 'Settings exported');

        $filename = 'settings-' . ($company->company_code ?? $company->id) . '-' . now()->format('Y-m-d') . '.json';
        return response()->streamDownload(function () use ($settings) {
            echo json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }

    public function importSettings(Request $request)
    {
        $companyId = session('current_company_id');
        $company = Company::findOrFail($companyId);
        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $request->validate([
            'settings_file' => 'required|file|mimes:json,txt|max:10240',
        ]);

        $content = file_get_contents($request->file('settings_file')->getRealPath());
        $data = json_decode($content, true);

        if (!is_array($data) || !isset($data['version'])) {
            return back()->with('error', 'Invalid settings file. Please export settings from a compatible version of CamelotBooks.');
        }

        $imported = 0;

        if (!empty($data['regional'])) {
            foreach ($data['regional'] as $key => $value) {
                SystemSetting::setValue('regional', $key, $value, $companyId);
                $imported++;
            }
        }

        if (!empty($data['currency'])) {
            $baseCurrency = $data['currency']['base_currency'] ?? null;
            if ($baseCurrency) {
                Company::where('id', $companyId)->update(['base_currency' => $baseCurrency]);
            }
            foreach (['decimal_places', 'rate_source'] as $key) {
                if (isset($data['currency'][$key])) {
                    SystemSetting::setValue('currency', $key, $data['currency'][$key], $companyId);
                    $imported++;
                }
            }
        }

        if (!empty($data['accounting'])) {
            foreach ($data['accounting'] as $key => $value) {
                SystemSetting::setValue('accounting', $key, $value, $companyId);
                $imported++;
            }
        }

        if (!empty($data['account_mappings'])) {
            foreach ($data['account_mappings'] as $mappingKey => $accountId) {
                if ($accountId) {
                    $account = Account::find($accountId);
                    if ($account && $account->company_id === $companyId) {
                        DefaultAccountMapping::setMapping($companyId, $mappingKey, (int) $accountId);
                        $imported++;
                    }
                }
            }
        }

        if (!empty($data['approval'])) {
            $approvalSetting = ApprovalSetting::firstOrCreate(['company_id' => $companyId]);
            $approvalSetting->update([
                'requires_approval' => $data['approval']['requires_approval'] ?? false,
                'threshold_amount' => $data['approval']['threshold_amount'] ?? 0,
            ]);
            $imported++;
        }

        if (!empty($data['numbering'])) {
            foreach ($data['numbering'] as $docType => $seq) {
                NumberingSequence::updateOrCreate(
                    ['company_id' => $companyId, 'document_type' => $docType],
                    $seq
                );
                $imported++;
            }
        }

        if (!empty($data['approval_thresholds'])) {
            foreach ($data['approval_thresholds'] as $docType => $threshold) {
                ApprovalThreshold::updateOrCreate(
                    ['company_id' => $companyId, 'document_type' => $docType],
                    $threshold
                );
                $imported++;
            }
        }

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.imported', null, ['records_imported' => $imported, 'source' => $data['company_name'] ?? 'Unknown'], 'Settings imported from ' . ($data['company_name'] ?? 'Unknown'));

        return redirect()->route('system-settings.index', 'import-export')
            ->with('success', "Settings imported successfully. {$imported} setting(s) updated.");
    }

    public function featuresIndex(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRole(['system_admin', 'company_admin']), 403);

        $features = FeatureManagement::getAvailableFeatures();
        $enabled = FeatureManagement::getEnabledFeatures($companyId);

        return view('system-settings.features', compact('features', 'enabled'));
    }
}
