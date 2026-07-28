<?php

namespace App\Http\Controllers\SystemSettings;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Company;
use App\Models\DefaultAccountMapping;
use App\Models\SystemSetting;
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

        return view('system-settings.index', compact(
            'company', 'regional', 'currency', 'accounting', 'accounts', 'mappings', 'tab'
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
        ];

        return view('system-settings.audit-log', compact('logs', 'users', 'groups', 'company', 'tab'));
    }

    public function updateCompany(Request $request)
    {
        $companyId = session('current_company_id');
        $company = Company::findOrFail($companyId);

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

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

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $oldValues = SystemSetting::getMany('regional', $companyId);

        $validated = $request->validate([
            'country' => 'nullable|string|max:100',
            'language' => 'nullable|string|max:10',
            'timezone' => 'required|string|max:50',
            'date_format' => 'required|string|max:20',
            'time_format' => 'required|string|max:10',
            'first_day_of_week' => 'required|integer|min:0|max:6',
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

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $oldValues = array_merge(
            ['base_currency' => Company::findOrFail($companyId)->base_currency],
            SystemSetting::getMany('currency', $companyId)
        );

        $validated = $request->validate([
            'base_currency' => 'required|string|max:10',
            'decimal_places' => 'required|integer|min:0|max:4',
            'rate_source' => 'required|string|max:30',
        ]);

        Company::where('id', $companyId)->update(['base_currency' => $validated['base_currency']]);

        foreach (['decimal_places', 'rate_source'] as $key) {
            SystemSetting::setValue('currency', $key, $validated[$key], $companyId);
        }

        AuditLog::log($companyId, $request->user()->id, Company::class, $companyId, 'settings.updated', $oldValues, $validated, 'Currency Settings');

        return redirect()->route('system-settings.index', 'currency')
            ->with('success', 'Currency settings updated successfully.');
    }

    public function updateAccountMappings(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

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

    public function updateAccounting(Request $request)
    {
        $companyId = session('current_company_id');

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

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
}
