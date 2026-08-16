<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Bill;
use App\Models\SystemSetting;
use App\Models\Vendor;
use App\Models\VendorCredit;
use App\Models\VendorPayment;
use App\Services\Reporting\AgingReportService;
use Illuminate\Http\Request;

class VendorController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $openStatuses = [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE];

        $query = Vendor::where('company_id', $companyId);

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('display_name', 'like', "%{$search}%")
                    ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->status;
            if ($status === 'active') {
                $query->where('is_active', true);
            } elseif ($status === 'inactive') {
                $query->where('is_active', false);
            } elseif ($status === 'overdue') {
                $query->whereHas('bills', function ($q) use ($openStatuses) {
                    $q->whereIn('status', $openStatuses)
                        ->where('due_date', '<', now()->toDateString())
                        ->whereColumn('amount', '>', 'amount_paid');
                });
            } elseif ($status === 'zero') {
                $query->whereDoesntHave('bills', function ($q) use ($openStatuses) {
                    $q->whereIn('status', $openStatuses)
                        ->whereColumn('amount', '>', 'amount_paid');
                });
            }
        }

        $allIds = Vendor::where('company_id', $companyId)->pluck('id');
        $overdueIds = Vendor::where('company_id', $companyId)
            ->whereHas('bills', function ($q) use ($openStatuses) {
                $q->whereIn('status', $openStatuses)
                    ->where('due_date', '<', now()->toDateString())
                    ->whereColumn('amount', '>', 'amount_paid');
            })
            ->pluck('id');

        $stats = [
            'total' => (int) $allIds->count(),
            'active' => (int) Vendor::where('company_id', $companyId)->where('is_active', true)->count(),
            'inactive' => (int) Vendor::where('company_id', $companyId)->where('is_active', false)->count(),
            'overdue' => (int) $overdueIds->count(),
            'zero' => (int) ($allIds->count() - $overdueIds->count()),
            'balance_owed' => (float) \App\Models\Bill::where('company_id', $companyId)
                ->whereIn('status', $openStatuses)
                ->selectRaw('COALESCE(SUM(amount), 0) - COALESCE(SUM(amount_paid), 0) as due')
                ->value('due'),
        ];

        $vendors = $query->orderBy('name')->paginate(15)->withQueryString();

        return view('accounting.vendors.index', compact('vendors', 'stats'));
    }

    public function dashboard(Request $request)
    {
        $companyId = session('current_company_id');

        $aging = app(AgingReportService::class)->apAging($companyId, null, now()->format('Y-m-d'));

        $overdue = (float) $aging['totals']['days_1_30']
            + (float) $aging['totals']['days_31_60']
            + (float) $aging['totals']['days_61_90']
            + (float) $aging['totals']['days_90_plus'];

        $openStatuses = [Bill::STATUS_APPROVED, Bill::STATUS_PARTIALLY_PAID, Bill::STATUS_OVERDUE];

        $stats = [
            'vendors' => (int) Vendor::where('company_id', $companyId)->where('is_active', true)->count(),
            'total_vendors' => (int) Vendor::where('company_id', $companyId)->count(),
            'open_balance' => (float) $aging['totals']['total'],
            'current' => (float) $aging['totals']['current'],
            'overdue' => $overdue,
            'unpaid_bills' => (int) Bill::where('company_id', $companyId)->whereIn('status', $openStatuses)->count(),
            'bills_this_month' => (float) Bill::where('company_id', $companyId)
                ->whereBetween('bill_date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->selectRaw('COALESCE(SUM(amount), 0) as amt')
                ->value('amt'),
            'pending_approval' => (int) Bill::where('company_id', $companyId)->where('status', Bill::STATUS_PENDING_APPROVAL)->count(),
        ];

        $topVendors = collect($aging['vendors'])->sortByDesc('total')->take(5)->values()->all();

        $dueSoon = Bill::where('company_id', $companyId)
            ->whereIn('status', $openStatuses)
            ->where('due_date', '>=', now()->toDateString())
            ->where('due_date', '<=', now()->addDays(30)->toDateString())
            ->with('vendor')
            ->orderBy('due_date')
            ->limit(8)
            ->get();

        $recentBills = Bill::where('company_id', $companyId)
            ->with('vendor')
            ->orderBy('bill_date', 'desc')
            ->limit(6)
            ->get();

        $recentPayments = VendorPayment::where('company_id', $companyId)
            ->with('vendor')
            ->orderBy('payment_date', 'desc')
            ->limit(6)
            ->get();

        $recentCredits = VendorCredit::where('company_id', $companyId)
            ->with('vendor')
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        return view('accounting.vendors.dashboard', compact(
            'stats', 'topVendors', 'dueSoon', 'recentBills', 'recentPayments', 'recentCredits', 'aging'
        ));
    }

    public function reports()
    {
        $companyId = session('current_company_id');
        $cs = SystemSetting::getValue('currency', 'currency_symbol', $companyId, '$');

        $aging = app(\App\Services\Reporting\AgingReportService::class)->apAging($companyId, null, now()->format('Y-m-d'));

        return view('accounting.vendors.reports', [
            'agingVendors' => $aging['vendors'],
            'agingTotals' => $aging['totals'],
            'cs' => $cs,
        ]);
    }

    public function settings()
    {
        $companyId = session('current_company_id');

        $settings = [
            'default_payment_terms' => SystemSetting::getValue('vendor_centre', 'default_payment_terms', $companyId, 'net_30'),
            'default_currency' => SystemSetting::getValue('vendor_centre', 'default_currency', $companyId, ''),
            'due_soon_days' => (int) SystemSetting::getValue('vendor_centre', 'due_soon_days', $companyId, 30),
        ];

        return view('accounting.vendors.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        $this->requirePermission($request, 'vendors.edit');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'default_payment_terms' => ['required', 'string', 'in:net_15,net_30,net_60,net_90,custom,due_on_receipt'],
            'default_currency' => ['nullable', 'string', 'max:10'],
            'due_soon_days' => ['required', 'integer', 'min:1', 'max:120'],
        ]);

        foreach ($validated as $key => $value) {
            SystemSetting::setValue('vendor_centre', $key, $value, $companyId);
        }

        return redirect()->route('accounting.vendors.settings')
            ->with('success', 'Vendor Centre settings updated successfully.');
    }

    public function exportCsv(Request $request)
    {
        $this->requirePermission($request, 'vendors.view');
        $companyId = session('current_company_id');

        $query = Vendor::where('company_id', $companyId);

        if ($request->filled('ids')) {
            $ids = collect(explode(',', $request->ids))->filter()->map(fn ($id) => (int) $id)->unique()->values();
            $query->whereIn('id', $ids);
        } else {
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            }

            if ($request->filled('status')) {
                $query->where('is_active', $request->status === 'active');
            }
        }

        $vendors = $query->orderBy('name')->get();

        $headers = ['Name', 'Display Name', 'Email', 'Phone', 'Payment Terms', 'Balance', 'Status'];

        return response()->streamDownload(function () use ($vendors, $headers) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $headers);
            foreach ($vendors as $vendor) {
                fputcsv($handle, [
                    $vendor->name,
                    $vendor->display_name,
                    $vendor->email,
                    $vendor->phone,
                    $vendor->payment_terms,
                    number_format($vendor->balance_due, 2),
                    $vendor->is_active ? 'Active' : 'Inactive',
                ]);
            }
            fclose($handle);
        }, 'vendors-' . now()->format('Y-m-d-His') . '.csv', ['Content-Type' => 'text/csv']);
    }

    public function create()
    {
        return view('accounting.vendors.create');
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'vendors.create');
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'remit_to_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string', 'in:net_15,net_30,net_60,net_90,custom,due_on_receipt'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        $validated['company_id'] = $companyId;
        $validated['is_active'] = true;

        Vendor::create($validated);

        return redirect()->route('accounting.vendors.index')
            ->with('success', 'Vendor created successfully.');
    }

    public function show(Vendor $vendor)
    {
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        $vendor->load(['bills' => function ($q) {
            $q->orderBy('bill_date', 'desc')->limit(50);
        }, 'payments' => function ($q) {
            $q->orderBy('payment_date', 'desc')->limit(50);
        }]);

        $credits = \App\Models\VendorCredit::where('vendor_id', $vendor->id)
            ->orderBy('credit_note_date', 'desc')
            ->limit(50)
            ->get();

        $balanceDue = $vendor->balance_due;
        $totalBilled = (float) $vendor->bills->sum('amount');

        $transactions = collect();

        foreach ($vendor->bills as $bill) {
            $transactions->push([
                'type' => 'Bill',
                'date' => $bill->bill_date,
                'reference' => $bill->bill_number,
                'description' => $bill->memo ?? "Bill {$bill->bill_number}",
                'amount' => $bill->amount,
                'paid' => $bill->amount_paid,
                'balance' => $bill->balance_due,
                'status' => $bill->status,
            ]);
        }

        foreach ($vendor->payments as $payment) {
            $transactions->push([
                'type' => 'Payment',
                'date' => $payment->payment_date,
                'reference' => $payment->payment_number,
                'description' => $payment->memo ?? "Payment {$payment->payment_number}",
                'amount' => -$payment->amount,
                'paid' => 0,
                'balance' => -$payment->amount,
                'status' => 'paid',
            ]);
        }

        $transactions = $transactions->sortByDesc('date')->values();

        return view('accounting.vendors.show', compact('vendor', 'balanceDue', 'totalBilled', 'credits', 'transactions'));
    }

    public function edit(Vendor $vendor)
    {
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        return view('accounting.vendors.edit', compact('vendor'));
    }

    public function update(Request $request, Vendor $vendor)
    {
        $this->requirePermission($request, 'vendors.edit');
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'display_name' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'billing_address' => ['nullable', 'string', 'max:500'],
            'remit_to_address' => ['nullable', 'string', 'max:500'],
            'currency' => ['nullable', 'string', 'max:10'],
            'payment_terms' => ['nullable', 'string', 'in:net_15,net_30,net_60,net_90,custom,due_on_receipt'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0'],
            'opening_balance' => ['nullable', 'numeric'],
            'opening_balance_date' => ['nullable', 'date'],
        ]);

        if (array_key_exists('opening_balance', $validated) && $vendor->opening_balance_date) {
            $hasPostings = \App\Models\JournalEntryLine::where('vendor_id', $vendor->id)
                ->whereHas('journalEntry', fn ($q) => $q
                    ->where('status', 'posted')
                    ->where('date', '>=', $vendor->opening_balance_date)
                )
                ->exists();

            if (!$hasPostings) {
                $hasPostings = \App\Models\Bill::where('vendor_id', $vendor->id)
                    ->where('bill_date', '>=', $vendor->opening_balance_date)
                    ->exists();
            }

            if (!$hasPostings) {
                $hasPostings = \App\Models\VendorPayment::where('vendor_id', $vendor->id)
                    ->where('payment_date', '>=', $vendor->opening_balance_date)
                    ->exists();
            }

            if ($hasPostings) {
                unset($validated['opening_balance']);
                $validated['opening_balance_date'] = null;
            }
        }

        $vendor->update($validated);

        return redirect()->route('accounting.vendors.index')
            ->with('success', 'Vendor updated successfully.');
    }

    public function toggle(Vendor $vendor)
    {
        $this->requirePermission('vendors.void');
        $companyId = session('current_company_id');
        abort_unless($vendor->company_id == $companyId, 403);

        $vendor->update(['is_active' => !$vendor->is_active]);

        $status = $vendor->is_active ? 'activated' : 'deactivated';

        return redirect()->route('accounting.vendors.index')
            ->with('success', "Vendor {$status} successfully.");
    }
}
