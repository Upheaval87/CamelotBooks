<?php

namespace App\Services\Reporting;

use App\Models\ReportAuditLog;

class ReportAuditService
{
    /**
     * Map a route name to the report_key enum.
     */
    private const ROUTE_KEY_MAP = [
        'income-statement' => 'fin.income',
        'balance-sheet' => 'fin.position',
        'cash-flow' => 'fin.cashflow',
        'aging' => null, // handled separately (ar/ap)
    ];

    /**
     * Map an action string to the audit action constant.
     */
    private const ACTION_MAP = [
        'view' => ReportAuditLog::ACTION_VIEW,
        'index' => ReportAuditLog::ACTION_VIEW,
        'export-csv' => ReportAuditLog::ACTION_EXCEL,
        'export-pdf' => ReportAuditLog::ACTION_PDF,
        'pdf' => ReportAuditLog::ACTION_PDF,
        'preview' => ReportAuditLog::ACTION_PRINT,
        'email' => ReportAuditLog::ACTION_EMAIL,
        // aging summary/detail routes (ar-summary, ap-summary, ar-detail, ap-detail) are VIEW actions
        'ar-summary' => ReportAuditLog::ACTION_VIEW,
        'ap-summary' => ReportAuditLog::ACTION_VIEW,
        'ar-detail' => ReportAuditLog::ACTION_VIEW,
        'ap-detail' => ReportAuditLog::ACTION_VIEW,
    ];

    /**
     * Log a report audit entry.
     *
     * @param int         $userId      The acting user
     * @param int         $companyId   The current company
     * @param string      $routeName   The full route name (e.g. 'accounting.income-statement.index')
     * @param array       $filters     The filter parameters applied
     * @param string|null $outputFormat csv|pdf|html|null
     */
    public static function log(
        int $userId,
        int $companyId,
        string $routeName,
        array $filters,
        ?string $outputFormat = null
    ): void {
        $reportKey = self::resolveReportKey($routeName);
        $action = self::resolveAction($routeName);

        if ($reportKey === null || $action === null) {
            return; // Not a financial report route
        }

        ReportAuditLog::log(
            userId: $userId,
            companyId: $companyId,
            reportKey: $reportKey,
            action: $action,
            filters: $filters,
            outputFormat: $outputFormat,
        );
    }

    /**
     * Extract the report key from a route name.
     */
    private static function resolveReportKey(string $routeName): ?string
    {
        // aging routes: ar-summary, ar-detail, ap-summary, ap-detail, export-csv (with type param)
        if (str_contains($routeName, 'aging')) {
            return 'fin.aging'; // Will be refined in controller to fin.ar-aging or fin.ap-aging
        }

        foreach (self::ROUTE_KEY_MAP as $segment => $key) {
            if ($key !== null && str_contains($routeName, $segment)) {
                return $key;
            }
        }

        return null;
    }

    /**
     * Extract the action from a route name.
     */
    private static function resolveAction(string $routeName): ?string
    {
        foreach (self::ACTION_MAP as $segment => $action) {
            if (str_contains($routeName, $segment)) {
                return $action;
            }
        }

        return null;
    }
}
