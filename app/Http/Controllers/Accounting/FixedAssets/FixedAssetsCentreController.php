<?php

namespace App\Http\Controllers\Accounting\FixedAssets;

use App\Http\Controllers\Controller;
use App\Services\Tenancy\TenantConnectionResolver;
use Illuminate\Support\Facades\DB;

class FixedAssetsCentreController extends Controller
{
    private function tenantConnection(): string
    {
        return TenantConnectionResolver::connectionName() ?? config('database.default');
    }

    public function index()
    {
        $companyId = (int) session('current_company_id');

        $assets = DB::connection($this->tenantConnection())->table('fa_assets')
            ->where('company_id', $companyId);

        $totalAssets   = (clone $assets)->count();
        $activeAssets  = (clone $assets)->where('is_active', true)->count();
        $draftAssets   = (clone $assets)->where('status', 'draft')->count();

        $costTotal     = (clone $assets)->sum('acquisition_cost');
        $nbvTotal      = (clone $assets)->sum('net_book_value');
        $depTotal      = (clone $assets)->sum('accumulated_depreciation');
        $impairTotal   = (clone $assets)->sum('accumulated_impairment');

        $recentAssets = (clone $assets)
            ->where('is_active', true)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'asset_code', 'name', 'status', 'acquisition_cost', 'net_book_value', 'created_at']);

        $categories = DB::connection($this->tenantConnection())->table('fa_categories')
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->get();

        $categoryCounts = [];
        $categoryValues = [];
        foreach ($categories as $cat) {
            $catAssets = (clone $assets)->where('category_id', $cat->id);
            $categoryCounts[$cat->id] = (clone $catAssets)->count();
            $categoryValues[$cat->id] = (clone $catAssets)->sum('acquisition_cost');
        }

        $depBooks = DB::connection($this->tenantConnection())->table('fa_dep_books')
            ->where('company_id', $companyId)
            ->where('book_type', 'financial');

        $lastDepRun   = (clone $depBooks)->max('last_run_date');
        $dueForDep    = $totalAssets > 0 && $activeAssets > 0
            ? (clone $assets)->where('is_active', true)
                ->where(function ($q) use ($lastDepRun) {
                    if ($lastDepRun) {
                        $q->where('updated_at', '>=', $lastDepRun);
                    } else {
                        $q->where('status', 'active');
                    }
                })->count()
            : 0;

        return view('accounting.fixed-assets.dashboard', compact(
            'totalAssets', 'activeAssets', 'draftAssets',
            'costTotal', 'nbvTotal', 'depTotal', 'impairTotal',
            'recentAssets', 'categories', 'categoryCounts', 'categoryValues',
            'lastDepRun', 'dueForDep',
        ));
    }
}
