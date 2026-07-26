<?php

namespace App\Services\POS;

use App\Models\PosCashierSession;
use App\Models\PosPayment;
use App\Models\PosPaymentMethod;
use App\Models\PosReturn;
use App\Models\PosSale;
use App\Models\PosTerminal;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PosReportService
{
    public function xReport(int $companyId, int $sessionId): array
    {
        $session = PosCashierSession::where('company_id', $companyId)
            ->with(['terminal', 'user'])
            ->findOrFail($sessionId);

        $sales = PosSale::where('cashier_session_id', $session->id)
            ->where('status', 'posted')
            ->get();

        $payments = DB::table('pos_payments AS pp')
            ->join('pos_sales AS ps', 'ps.id', '=', 'pp.pos_sale_id')
            ->join('pos_payment_methods AS pm', 'pm.id', '=', 'pp.payment_method_id')
            ->where('ps.cashier_session_id', $session->id)
            ->where('ps.status', 'posted')
            ->select('pm.name AS method_name', 'pm.type AS method_type', DB::raw('SUM(pp.amount) AS total_amount'), DB::raw('COUNT(DISTINCT ps.id) AS sale_count'))
            ->groupBy('pm.name', 'pm.type')
            ->get();

        $returns = DB::table('pos_returns AS pr')
            ->join('pos_sales AS ps', 'ps.id', '=', 'pr.pos_sale_id')
            ->where('ps.cashier_session_id', $session->id)
            ->where('pr.status', 'posted')
            ->sum('pr.total');

        $cashPayments = $payments->where('method_type', 'cash')->sum('total_amount');

        return [
            'session' => $session,
            'sales_count' => $sales->count(),
            'sales_subtotal' => $sales->sum('subtotal'),
            'sales_tax' => $sales->sum('tax_total'),
            'sales_total' => $sales->sum('total'),
            'payments_by_method' => $payments,
            'cash_payments' => $cashPayments,
            'returns_total' => $returns,
            'opening_float' => (float) $session->opening_float,
            'expected_cash' => (float) $session->opening_float + $cashPayments - $returns,
        ];
    }

    public function zReport(int $companyId, int $sessionId): array
    {
        $session = PosCashierSession::where('company_id', $companyId)
            ->with(['terminal', 'user'])
            ->findOrFail($sessionId);

        $sales = PosSale::where('cashier_session_id', $session->id)
            ->where('status', 'posted')
            ->get();

        $payments = DB::table('pos_payments AS pp')
            ->join('pos_sales AS ps', 'ps.id', '=', 'pp.pos_sale_id')
            ->join('pos_payment_methods AS pm', 'pm.id', '=', 'pp.payment_method_id')
            ->where('ps.cashier_session_id', $session->id)
            ->where('ps.status', 'posted')
            ->select('pm.name AS method_name', 'pm.type AS method_type', DB::raw('SUM(pp.amount) AS total_amount'), DB::raw('COUNT(DISTINCT ps.id) AS sale_count'))
            ->groupBy('pm.name', 'pm.type')
            ->get();

        $returnsTotal = DB::table('pos_returns AS pr')
            ->join('pos_sales AS ps', 'ps.id', '=', 'pr.pos_sale_id')
            ->where('ps.cashier_session_id', $session->id)
            ->where('pr.status', 'posted')
            ->sum('pr.total');
        $cashPayments = $payments->where('method_type', 'cash')->sum('total_amount');

        return [
            'session' => $session,
            'sales_count' => $sales->count(),
            'sales_subtotal' => $sales->sum('subtotal'),
            'sales_tax' => $sales->sum('tax_total'),
            'sales_total' => $sales->sum('total'),
            'payments_by_method' => $payments,
            'cash_payments' => $cashPayments,
            'returns_count' => DB::table('pos_returns AS pr')
                ->join('pos_sales AS ps', 'ps.id', '=', 'pr.pos_sale_id')
                ->where('ps.cashier_session_id', $session->id)
                ->where('pr.status', 'posted')
                ->count(),
            'returns_total' => $returnsTotal,
            'net_sales' => $sales->sum('total') - $returnsTotal,
            'opening_float' => (float) $session->opening_float,
            'expected_cash' => (float) $session->opening_float + $cashPayments - $returnsTotal,
            'actual_cash_count' => $session->actual_cash_count !== null ? (float) $session->actual_cash_count : null,
            'variance' => $session->variance !== null ? (float) $session->variance : null,
        ];
    }

    public function salesByTerminal(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        $terminals = PosTerminal::where('company_id', $companyId)->get();

        $results = [];
        foreach ($terminals as $terminal) {
            $sales = PosSale::where('company_id', $companyId)
                ->where('terminal_id', $terminal->id)
                ->where('status', 'posted')
                ->whereBetween('created_at', [$from, $to])
                ->get();

            $returns = PosReturn::where('company_id', $companyId)
                ->where('terminal_id', $terminal->id)
                ->where('status', 'posted')
                ->whereBetween('created_at', [$from, $to])
                ->sum('total');

            $count = $sales->count();
            $total = $sales->sum('total');

            $results[] = [
                'terminal' => $terminal,
                'sales_count' => $count,
                'sales_total' => $total,
                'returns_total' => $returns,
                'net_sales' => $total - $returns,
                'average_sale' => $count > 0 ? round($total / $count, 2) : 0,
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'terminals' => $results,
            'grand_total_sales' => collect($results)->sum('sales_total'),
            'grand_total_returns' => collect($results)->sum('returns_total'),
            'grand_net_sales' => collect($results)->sum('net_sales'),
            'grand_count' => collect($results)->sum('sales_count'),
        ];
    }

    public function salesByCashier(int $companyId, ?string $from = null, ?string $to = null): array
    {
        $from = $from ? Carbon::parse($from)->startOfDay() : Carbon::now()->startOfMonth();
        $to = $to ? Carbon::parse($to)->endOfDay() : Carbon::now()->endOfDay();

        $sessions = PosCashierSession::where('company_id', $companyId)
            ->whereBetween('opened_at', [$from, $to])
            ->with('user')
            ->get();

        $results = [];
        foreach ($sessions->groupBy('user_id') as $userId => $userSessions) {
            $sessionIds = $userSessions->pluck('id');
            $user = $userSessions->first()->user;

            $sales = PosSale::whereIn('cashier_session_id', $sessionIds)
                ->where('status', 'posted')
                ->get();

            $returns = PosReturn::whereIn('cashier_session_id', $sessionIds)
                ->where('status', 'posted')
                ->sum('total');

            $count = $sales->count();
            $total = $sales->sum('total');

            $results[] = [
                'user' => $user,
                'sessions_count' => $userSessions->count(),
                'sales_count' => $count,
                'sales_total' => $total,
                'returns_total' => $returns,
                'net_sales' => $total - $returns,
                'average_sale' => $count > 0 ? round($total / $count, 2) : 0,
            ];
        }

        return [
            'from' => $from,
            'to' => $to,
            'cashiers' => $results,
            'grand_total_sales' => collect($results)->sum('sales_total'),
            'grand_total_returns' => collect($results)->sum('returns_total'),
            'grand_net_sales' => collect($results)->sum('net_sales'),
            'grand_count' => collect($results)->sum('sales_count'),
        ];
    }
}
