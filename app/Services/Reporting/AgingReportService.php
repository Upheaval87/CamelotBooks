<?php

namespace App\Services\Reporting;

use App\Models\Account;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Vendor;
use App\Models\Bill;
use Carbon\Carbon;

class AgingReportService
{
    public function arAging(int $companyId, ?int $branchId, string $asOfDate): array
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->where('invoice_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('customer')
            ->get();

        $asOf = Carbon::parse($asOfDate);
        $customerBuckets = [];

        foreach ($invoices as $invoice) {
            $customerId = $invoice->customer_id;
            $customerName = $invoice->customer->name ?? 'Unknown';
            $dueDate = Carbon::parse($invoice->due_date);
            $daysOverdue = max(0, $asOf->diffInDays($dueDate, false));
            $daysOverdue = $asOf->greaterThan($dueDate) ? abs($asOf->diffInDays($dueDate)) : 0;

            $amount = (float) $invoice->amount - (float) $invoice->amount_paid;

            $bucket = $this->getAgingBucket($daysOverdue);

            if (!isset($customerBuckets[$customerId])) {
                $customerBuckets[$customerId] = [
                    'customer_id' => $customerId,
                    'customer_name' => $customerName,
                    'current' => 0,
                    'days_1_30' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'days_90_plus' => 0,
                    'total' => 0,
                ];
            }

            $customerBuckets[$customerId][$bucket] += $amount;
            $customerBuckets[$customerId]['total'] += $amount;
        }

        usort($customerBuckets, fn ($a, $b) => $b['total'] <=> $a['total']);

        $totals = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_90_plus' => 0,
            'total' => 0,
        ];

        foreach ($customerBuckets as $row) {
            foreach (['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_90_plus', 'total'] as $key) {
                $totals[$key] += $row[$key];
            }
        }

        return [
            'customers' => $customerBuckets,
            'totals' => $totals,
            'as_of_date' => $asOfDate,
        ];
    }

    public function apAging(int $companyId, ?int $branchId, string $asOfDate): array
    {
        $bills = Bill::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'approved'])
            ->whereDate('bill_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('vendor')
            ->get();

        $asOf = Carbon::parse($asOfDate);
        $vendorBuckets = [];

        foreach ($bills as $bill) {
            $vendorId = $bill->vendor_id;
            $vendorName = $bill->vendor->name ?? 'Unknown';
            $dueDate = Carbon::parse($bill->due_date);
            $daysOverdue = $asOf->greaterThan($dueDate) ? abs($asOf->diffInDays($dueDate)) : 0;

            $amount = (float) $bill->amount - (float) $bill->amount_paid;

            $bucket = $this->getAgingBucket($daysOverdue);

            if (!isset($vendorBuckets[$vendorId])) {
                $vendorBuckets[$vendorId] = [
                    'vendor_id' => $vendorId,
                    'vendor_name' => $vendorName,
                    'current' => 0,
                    'days_1_30' => 0,
                    'days_31_60' => 0,
                    'days_61_90' => 0,
                    'days_90_plus' => 0,
                    'total' => 0,
                ];
            }

            $vendorBuckets[$vendorId][$bucket] += $amount;
            $vendorBuckets[$vendorId]['total'] += $amount;
        }

        usort($vendorBuckets, fn ($a, $b) => $b['total'] <=> $a['total']);

        $totals = [
            'current' => 0,
            'days_1_30' => 0,
            'days_31_60' => 0,
            'days_61_90' => 0,
            'days_90_plus' => 0,
            'total' => 0,
        ];

        foreach ($vendorBuckets as $row) {
            foreach (['current', 'days_1_30', 'days_31_60', 'days_61_90', 'days_90_plus', 'total'] as $key) {
                $totals[$key] += $row[$key];
            }
        }

        return [
            'vendors' => $vendorBuckets,
            'totals' => $totals,
            'as_of_date' => $asOfDate,
        ];
    }

    public function arAgingDetail(int $companyId, ?int $branchId, string $asOfDate): array
    {
        $invoices = Invoice::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid'])
            ->where('invoice_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('customer')
            ->get();

        $asOf = Carbon::parse($asOfDate);
        $lines = [];

        foreach ($invoices as $invoice) {
            $dueDate = Carbon::parse($invoice->due_date);
            $daysOverdue = $asOf->greaterThan($dueDate) ? abs($asOf->diffInDays($dueDate)) : 0;
            $amount = (float) $invoice->amount - (float) $invoice->amount_paid;

            $lines[] = [
                'customer_name' => $invoice->customer->name ?? 'Unknown',
                'invoice_number' => $invoice->invoice_number,
                'due_date' => $invoice->due_date,
                'days_overdue' => $daysOverdue,
                'total' => $amount,
            ];
        }

        usort($lines, fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        $totals = ['total' => 0];
        foreach ($lines as $line) {
            $totals['total'] += $line['total'];
        }

        return [
            'customers' => $lines,
            'totals' => $totals,
            'as_of_date' => $asOfDate,
        ];
    }

    public function apAgingDetail(int $companyId, ?int $branchId, string $asOfDate): array
    {
        $bills = Bill::where('company_id', $companyId)
            ->whereIn('status', ['posted', 'partially_paid', 'approved'])
            ->whereDate('bill_date', '<=', $asOfDate)
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->with('vendor')
            ->get();

        $asOf = Carbon::parse($asOfDate);
        $lines = [];

        foreach ($bills as $bill) {
            $dueDate = Carbon::parse($bill->due_date);
            $daysOverdue = $asOf->greaterThan($dueDate) ? abs($asOf->diffInDays($dueDate)) : 0;
            $amount = (float) $bill->amount - (float) $bill->amount_paid;

            $lines[] = [
                'vendor_name' => $bill->vendor->name ?? 'Unknown',
                'bill_number' => $bill->bill_number,
                'due_date' => $bill->due_date,
                'days_overdue' => $daysOverdue,
                'total' => $amount,
            ];
        }

        usort($lines, fn ($a, $b) => $b['days_overdue'] <=> $a['days_overdue']);

        $totals = ['total' => 0];
        foreach ($lines as $line) {
            $totals['total'] += $line['total'];
        }

        return [
            'vendors' => $lines,
            'totals' => $totals,
            'as_of_date' => $asOfDate,
        ];
    }

    private function getAgingBucket(int $daysOverdue): string
    {
        if ($daysOverdue <= 0) return 'current';
        if ($daysOverdue <= 30) return 'days_1_30';
        if ($daysOverdue <= 60) return 'days_31_60';
        if ($daysOverdue <= 90) return 'days_61_90';
        return 'days_90_plus';
    }
}
