<?php

namespace App\Modules\Vendors\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Vendors\Models\Vendor;
use App\Modules\Vendors\Models\VendorPayment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class VendorPaymentController extends Controller
{
    public function store(Request $request, Vendor $vendor)
    {
        $request->validate([
            'payment_date' => 'required|date',
            'payment_method' => 'required|string',
            'amount' => 'required|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($request, $vendor) {
            $payment = $vendor->payments()->create([
                'company_id' => $vendor->company_id,
                'payment_number' => 'PAY-' . date('Y') . '-' . str_pad((VendorPayment::whereYear('created_at', date('Y'))->count() + 1), 4, '0', STR_PAD_LEFT),
                'payment_date' => $request->payment_date,
                'payment_method' => $request->payment_method,
                'reference_number' => $request->reference_number,
                'provider_institution' => $request->provider_institution,
                'account_name' => $request->account_name,
                'account_holder_name' => $request->account_holder_name,
                'paying_bank_account_id' => $request->paying_bank_account_id,
                'amount' => $request->amount,
                'status' => 'cleared',
                'note' => $request->note,
                'created_by' => auth()->id(),
            ]);

            if ($request->payment_method === 'Split Payment' && $request->has('split_lines')) {
                foreach ($request->split_lines as $line) {
                    if (empty($line['method']) || empty($line['amount'])) continue;
                    $payment->splitLines()->create([
                        'method' => $line['method'],
                        'amount' => $line['amount'],
                        'reference_number' => $line['reference_number'] ?? null,
                        'provider_institution' => $line['provider_institution'] ?? null,
                        'account_name' => $line['account_name'] ?? null,
                        'account_holder_name' => $line['account_holder_name'] ?? null,
                        'cash_tendered' => $line['cash_tendered'] ?? null,
                        'change_given' => $line['change_given'] ?? null,
                    ]);
                }
            }

            if ($request->has('allocations')) {
                foreach ($request->allocations as $alloc) {
                    if (empty($alloc['bill_id']) || empty($alloc['allocated_amount'])) continue;
                    $payment->allocations()->create([
                        'bill_id' => $alloc['bill_id'],
                        'allocated_amount' => $alloc['allocated_amount'],
                    ]);
                    $bill = $vendor->bills()->find($alloc['bill_id']);
                    if ($bill) {
                        $bill->amount_paid += $alloc['allocated_amount'];
                        if ($bill->amount_paid >= $bill->total_amount) {
                            $bill->status = 'paid';
                        }
                        $bill->save();
                    }
                }
            }
        });

        return back()->with('success', 'Payment recorded successfully.');
    }
}
