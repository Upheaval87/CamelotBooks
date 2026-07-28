<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Reporting\ReportRegistry;
use Illuminate\Http\Request;

class ReportCenterController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $user = $request->user();
        $search = $request->input('search', '');
        $favorites = session('report_favorites', []);

        $grouped = ReportRegistry::getAccessibleGrouped($user, $companyId);

        if ($search) {
            $search = strtolower($search);
            foreach ($grouped as $catKey => &$catData) {
                $catData['reports'] = array_filter($catData['reports'], function ($r) use ($search) {
                    return str_contains(strtolower($r['name']), $search)
                        || str_contains(strtolower($r['description']), $search);
                });
                $catData['reports'] = array_values($catData['reports']);
            }
            $grouped = array_filter($grouped, fn ($cat) => !empty($cat['reports']));
        }

        $favoriteReports = [];
        foreach ($favorites as $favKey) {
            $report = ReportRegistry::get($favKey);
            if ($report && ReportRegistry::isAccessible($favKey, $user, $companyId) && ReportRegistry::isRouteDefined($report['route'])) {
                $favoriteReports[] = $report;
            }
        }

        return view('report-center.index', compact('grouped', 'favoriteReports', 'search'));
    }

    public function toggleFavorite(Request $request, string $key)
    {
        $favorites = session('report_favorites', []);

        if (in_array($key, $favorites)) {
            $favorites = array_diff($favorites, [$key]);
        } else {
            $favorites[] = $key;
        }

        session(['report_favorites' => array_values($favorites)]);

        return response()->json([
            'favorited' => in_array($key, session('report_favorites', [])),
        ]);
    }
}
