<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\UserPreference;
use App\Services\Reporting\ReportRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class ReportCenterController extends Controller
{
    private const ICON_PATHS = [
        'financial_statements' => 'M12 3v17.25m0 0c-1.472 0-2.882.265-4.185.75M12 20.25c1.472 0 2.882.265 4.185.75M18.75 4.97A48.416 48.416 0 0012 4.5c-2.291 0-4.545.16-6.75.47m13.5 0c1.01.143 2.01.317 3 .52m-3-.52l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.988 5.988 0 01-2.031.352 5.988 5.988 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L18.75 4.971zm-16.5.52c.99-.203 1.99-.377 3-.52m0 0l2.62 10.726c.122.499-.106 1.028-.589 1.202a5.989 5.989 0 01-2.031.352 5.989 5.989 0 01-2.031-.352c-.483-.174-.711-.703-.59-1.202L5.25 4.971z',
        'sales' => 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z',
        'purchasing' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121-2.3 2.1-4.684 2.924-7.138a60.114 60.114 0 00-16.536-1.84M7.5 14.25L5.106 5.272M6 20.25a.75.75 0 11-1.5 0 .75.75 0 011.5 0zm12.75 0a.75.75 0 11-1.5 0 .75.75 0 011.5 0z',
        'inventory' => 'm21 7.5-9-5.25L3 7.5m18 0-9 5.25m9-5.25v9l-9 5.25M3 7.5l9 5.25M3 7.5v9l9 5.25m0-9v9',
        'banking' => 'M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z',
        'fixed_assets' => 'M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21',
        'payroll' => 'M15 9h3.75M15 12h3.75M15 15h3.75M4.5 19.5h15a2.25 2.25 0 002.25-2.25V6.75A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25v10.5A2.25 2.25 0 004.5 19.5zm6-10.125a1.875 1.875 0 11-3.75 0 1.875 1.875 0 013.75 0zm1.294 6.336a6.721 6.721 0 01-3.17.789 6.721 6.721 0 01-3.168-.789 3.376 3.376 0 016.338 0z',
        'budgeting' => 'M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V13.5zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25V18zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V13.5zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007V18zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V18zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008V13.5zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z',
        'compliance' => 'M9 12.75L11.25 15 15 9.75m-3-7.036A11.959 11.959 0 013.598 6 11.99 11.99 0 003 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285z',
        'analytics' => 'M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5M9 11.25v1.5M12 9v3.75m3-6v6',
        'bi' => 'M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z',
        'pos' => 'M15.75 10.5V6a3.75 3.75 0 10-7.5 0v4.5m11.356-1.993l1.263 12c.07.665-.45 1.243-1.119 1.243H4.25a1.125 1.125 0 01-1.12-1.243l1.264-12A1.125 1.125 0 015.513 7.5h12.974c.576 0 1.059.435 1.119 1.007zM8.625 10.5a.375.375 0 11-.75 0 .375.375 0 01.75 0zm7.5 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z',
    ];

    private const CSV_REPORT_KEYS = [
        'trial_balance', 'general_ledger', 'income_statement', 'balance_sheet',
        'equity_statement', 'cash_flow', 'ar_aging', 'ap_aging',
        'inventory_valuation', 'low_stock', 'audit_log',
    ];

    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $user = $request->user();

        $favoriteKeys = $this->favoriteKeysFor($user);

        $favoriteReports = [];
        foreach ($favoriteKeys as $favKey) {
            $report = ReportRegistry::get($favKey);
            if ($report && ReportRegistry::isAccessible($favKey, $user, $companyId) && ReportRegistry::isRouteDefined($report['route'])) {
                $report['url'] = $this->reportUrl($report['route']);
                $favoriteReports[] = $report;
            }
        }

        $groups = [];
        foreach (ReportRegistry::getCategories() as $catKey => $catLabel) {
            $catReports = array_values(ReportRegistry::getByCategory($catKey));
            if (empty($catReports)) {
                continue;
            }

            $icon = self::ICON_PATHS[$catKey] ?? self::ICON_PATHS['analytics'];
            $groups[] = [
                'key' => $catKey,
                'label' => $catLabel,
                'icon' => $icon,
                'reports' => array_map(function ($r) use ($icon, $favoriteKeys) {
                    return [
                        'key' => $r['key'],
                        'name' => $r['name'],
                        'description' => $r['description'],
                        'icon' => $icon,
                        'formats' => in_array($r['key'], self::CSV_REPORT_KEYS, true) ? ['PDF', 'CSV'] : ['PDF'],
                        'url' => $this->reportUrl($r['route']),
                        'is_favorite' => in_array($r['key'], $favoriteKeys, true),
                    ];
                }, $catReports),
            ];
        }

        $favorites = collect($favoriteReports)->map(fn ($r) => [
            'key' => $r['key'],
            'name' => $r['name'],
            'url' => $r['url'],
        ])->values()->all();

        return view('report-center.index', [
            'groups' => $groups,
            'favorites' => $favorites,
            'initialSearch' => (string) $request->input('search', ''),
            'initialCategory' => (string) $request->input('category', ''),
        ]);
    }

    public function toggleFavorite(Request $request, string $key)
    {
        $user = $request->user();

        if (!ReportRegistry::get($key)) {
            abort(404);
        }

        $prefs = UserPreference::firstOrCreate(['user_id' => $user->id]);
        $favorites = (array) ($prefs->report_favourites ?? []);
        $favorited = !in_array($key, $favorites, true);

        if ($favorited) {
            $favorites[] = $key;
        } else {
            $favorites = array_values(array_diff($favorites, [$key]));
        }

        $prefs->update(['report_favourites' => array_values(array_unique($favorites))]);

        return response()->json(['favorited' => $favorited]);
    }

    private function favoriteKeysFor($user): array
    {
        $prefs = UserPreference::firstOrCreate(['user_id' => $user->id]);

        return array_values(array_unique(array_filter((array) ($prefs->report_favourites ?? []))));
    }

    private function reportUrl(?string $routeName): ?string
    {
        if (!$routeName || !Route::has($routeName)) {
            return null;
        }

        $routeObj = Route::getRoutes()->getByName($routeName);
        if (!$routeObj || count($routeObj->parameterNames()) !== 0) {
            return null;
        }

        return route($routeName);
    }
}
