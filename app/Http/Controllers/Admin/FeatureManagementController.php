<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FeatureManagement;
use Illuminate\Http\Request;

class FeatureManagementController extends Controller
{
    public function index(Request $request)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $features = FeatureManagement::getAvailableFeatures();
        $enabled = FeatureManagement::getEnabledFeatures($companyId);

        return view('admin.features.index', compact('features', 'enabled'));
    }

    public function toggle(Request $request, string $feature)
    {
        $companyId = $request->user()->getActiveCompanyId();

        abort_unless($request->user()->hasAnyRoleInCompany(['system_admin', 'company_admin'], $companyId), 403);

        $available = FeatureManagement::getAvailableFeatures();

        if (!array_key_exists($feature, $available)) {
            abort(404);
        }

        if (FeatureManagement::isEnabled($companyId, $feature)) {
            FeatureManagement::disable($companyId, $feature);
            $status = 'disabled';
        } else {
            FeatureManagement::enable($companyId, $feature);
            $status = 'enabled';
        }

        return redirect()->route('admin.features.index')
            ->with('success', "{$available[$feature]} has been {$status}.");
    }
}
