<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Dashboard\DashboardOverviewService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        /** @var User $user */
        $user = $request->user();

        /** @var DashboardOverviewService $service */
        $service = app(DashboardOverviewService::class);

        $companyId = (int) session('current_company_id');
        $preset = $service->normalizePreset($request->query('period'));

        $data = $service->build($companyId, $preset, $user->id);

        $greeting = match (true) {
            now()->hour < 12 => 'Good morning',
            now()->hour < 17 => 'Good afternoon',
            default => 'Good evening',
        };
        $firstName = trim(explode(' ', (string) $user->name)[0]);

        $gates = [
            'invoice' => $user->can('invoices.create'),
            'bill' => $user->can('bills.create'),
            'payment' => $user->can('customer-payments.create'),
            'journal' => $user->can('journal-entries.create'),
            'customer' => $user->can('customers.create'),
            'reconcile' => $user->can('bank-reconciliations.create'),
        ];

        return view('dashboard', [
            ...$data,
            'greeting' => $greeting . ', ' . $firstName . '.',
            'subhead' => 'Here\'s your cash, receivables and performance at a glance.',
            'can' => $gates,
            'exportUrl' => route('dashboard.export', ['period' => $preset]),
        ]);
    }

    public function export(Request $request)
    {
        /** @var DashboardOverviewService $service */
        $service = app(DashboardOverviewService::class);

        $companyId = (int) session('current_company_id');
        $preset = $service->normalizePreset($request->query('period'));

        $data = $service->build($companyId, $preset, $request->user()?->id);

        $rows = [
            [
                'Metric',
                'Period (' . $data['range_label'] . ')',
            ],
            ['Total Revenue', number_format($data['kpi']['revenue']['value'], $data['decimals'], '.', '')],
            ['Total Expenses', number_format($data['kpi']['expenses']['value'], $data['decimals'], '.', '')],
            ['Net Profit', number_format($data['kpi']['net']['value'], $data['decimals'], '.', '')],
            ['Margin (%)', $data['kpi']['net']['margin'] ?? 0],
            ['Outstanding Invoices', number_format($data['kpi']['outstanding']['value'], $data['decimals'], '.', '')],
            ['Bills Payable', number_format($data['kpi']['payables']['value'], $data['decimals'], '.', '')],
            ['Cash & Bank', number_format($data['kpi']['cash']['value'], $data['decimals'], '.', '')],
            ['Aging — Current', number_format($data['aging']['buckets'][0]['amount'], $data['decimals'], '.', '')],
            ['Aging — 1–30 days', number_format($data['aging']['buckets'][1]['amount'], $data['decimals'], '.', '')],
            ['Aging — 31–60 days', number_format($data['aging']['buckets'][2]['amount'], $data['decimals'], '.', '')],
            ['Aging — 60+ days', number_format($data['aging']['buckets'][3]['amount'], $data['decimals'], '.', '')],
        ];

        return response()->streamDownload(function () use ($rows) {
            $handle = fopen('php://output', 'w');
            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, 'dashboard-' . $preset . '-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }
}