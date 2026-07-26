<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeePayment;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use App\Models\PayrollRun;
use App\Models\PensionScheme;
use App\Models\PayslipDelivery;
use App\Services\Accounting\PayrollService;
use App\Services\Payroll\EncryptedPayslipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class PayrollRunController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $runs = PayrollRun::where('company_id', $companyId)
            ->with('creator')
            ->orderByDesc('pay_date')
            ->paginate(20);

        return view('accounting.payroll-runs.index', compact('runs'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $payeTables = PayeTable::where('company_id', $companyId)->orderByDesc('effective_from')->get();
        $pensionSchemes = PensionScheme::where('company_id', $companyId)->orderByDesc('effective_from')->get();
        $employeeCount = Employee::where('company_id', $companyId)->active()->count();

        return view('accounting.payroll-runs.create', compact('payeTables', 'pensionSchemes', 'employeeCount'));
    }

    public function store(Request $request, PayrollService $payrollService)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        $validated = $request->validate([
            'period_label' => 'required|string|max:100',
            'pay_date' => 'required|date',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        try {
            $run = $payrollService->runPayroll(
                $companyId,
                $validated['period_label'],
                $validated['pay_date'],
                $validated['period_start'],
                $validated['period_end'],
                $userId
            );

            return redirect()->route('accounting.payroll-runs.show', $run)
                ->with('success', "Payroll run {$run->run_number} calculated successfully.");
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function show(PayrollRun $run)
    {
        $companyId = session('current_company_id');

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $run->load(['items.employee', 'journalEntry', 'payeTable', 'pensionScheme', 'payments', 'deliveries']);

        $bankAccounts = Account::where('company_id', $companyId)
            ->where('is_bank', true)
            ->where('is_active', true)
            ->get();

        return view('accounting.payroll-runs.show', compact('run', 'bankAccounts'));
    }

    public function approve(PayrollRun $run, PayrollService $payrollService)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        try {
            $payrollService->approvePayroll($run, $userId);
            return redirect()->route('accounting.payroll-runs.show', $run)
                ->with('success', 'Payroll run approved and ready for posting.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function post(PayrollRun $run, PayrollService $payrollService)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        try {
            $payrollService->postPayroll($run, $userId);
            return redirect()->route('accounting.payroll-runs.show', $run)
                ->with('success', 'Payroll run posted to the general ledger.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function payEmployee(Request $request, PayrollRun $run, int $employeeId, PayrollService $payrollService)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:accounts,id',
        ]);

        try {
            $payrollService->payEmployee(
                $run,
                $employeeId,
                $validated['amount'],
                $validated['payment_date'],
                $validated['bank_account_id'],
                $userId
            );

            return redirect()->route('accounting.payroll-runs.show', $run)
                ->with('success', 'Salary payment recorded.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function remitPaye(Request $request, PayrollRun $run, PayrollService $payrollService)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:accounts,id',
        ]);

        try {
            $payrollService->remitPAYE(
                $run,
                $validated['amount'],
                $validated['payment_date'],
                $validated['bank_account_id'],
                $userId
            );

            return redirect()->route('accounting.payroll-runs.show', $run)
                ->with('success', 'PAYE remittance recorded.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function remitPension(Request $request, PayrollRun $run, PayrollService $payrollService)
    {
        $companyId = session('current_company_id');
        $userId = auth()->id();

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_date' => 'required|date',
            'bank_account_id' => 'required|exists:accounts,id',
        ]);

        try {
            $payrollService->remitPension(
                $run,
                $validated['amount'],
                $validated['payment_date'],
                $validated['bank_account_id'],
                $userId
            );

            return redirect()->route('accounting.payroll-runs.show', $run)
                ->with('success', 'Pension remittance recorded.');
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['error' => $e->getMessage()]);
        }
    }

    public function sendPayslips(Request $request, PayrollRun $run)
    {
        $companyId = session('current_company_id');

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        if (!in_array($run->status, [PayrollRun::STATUS_POSTED, PayrollRun::STATUS_PARTIALLY_PAID, PayrollRun::STATUS_FULLY_PAID])) {
            return back()->withErrors(['error' => 'Payroll run must be posted before sending payslips.']);
        }

        Artisan::call('payroll:send-payslips', ['runId' => $run->id]);
        $output = Artisan::output();

        return redirect()->route('accounting.payroll-runs.show', $run)
            ->with('success', 'Payslip sending complete. Check delivery status below.');
    }

    public function payslip(PayrollRun $run, int $itemId)
    {
        $companyId = session('current_company_id');

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $item = $run->items()->with('employee')->findOrFail($itemId);

        $payslipService = app(EncryptedPayslipService::class);
        $pdfContent = $payslipService->generatePayslipPdf($run, $item);

        return response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"payslip_{$item->employee->employee_number}.pdf\"",
        ]);
    }

    public function payslips(PayrollRun $run)
    {
        $companyId = session('current_company_id');

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $items = $run->items()->with('employee')->get();

        return view('accounting.payroll-runs.print-payslips', [
            'run' => $run,
            'items' => $items,
        ]);
    }

    public function payeRemittanceSchedule(Request $request, PayrollRun $run)
    {
        $companyId = session('current_company_id');

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $items = $run->items()->with('employee')->get();

        return view('accounting.payroll-runs.paye-schedule', compact('run', 'items'));
    }

    public function pensionRemittanceSchedule(Request $request, PayrollRun $run)
    {
        $companyId = session('current_company_id');

        if ($run->company_id !== $companyId) {
            abort(404);
        }

        $items = $run->items()->with('employee')->get();

        return view('accounting.payroll-runs.pension-schedule', compact('run', 'items'));
    }

    public function storePayeTable(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'version_name' => 'required|string|max:100',
            'effective_from' => 'required|date',
            'bands' => 'required|array|min:1',
            'bands.*.threshold' => 'required|numeric|min:0',
            'bands.*.upper_limit' => 'nullable|numeric|min:0',
            'bands.*.rate' => 'required|numeric|min:0|max:100',
        ]);

        DB::beginTransaction();

        try {
            PayeTable::where('company_id', $companyId)
                ->where('is_current', true)
                ->update(['is_current' => false, 'effective_to' => now()->subDay()]);

            $table = PayeTable::create([
                'company_id' => $companyId,
                'version_name' => $validated['version_name'],
                'effective_from' => $validated['effective_from'],
                'is_current' => true,
            ]);

            foreach ($validated['bands'] as $index => $band) {
                PayeTableBand::create([
                    'paye_table_id' => $table->id,
                    'threshold' => $band['threshold'],
                    'upper_limit' => $band['upper_limit'] ?? null,
                    'rate' => $band['rate'],
                    'sort_order' => $index,
                ]);
            }

            DB::commit();

            return back()->with('success', 'PAYE table created successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }

    public function storePensionScheme(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'name' => 'required|string|max:200',
            'registration_number' => 'nullable|string|max:100',
            'employee_rate' => 'required|numeric|min:0|max:100',
            'employer_rate' => 'required|numeric|min:0|max:100',
            'max_contributory_salary' => 'nullable|numeric|min:0',
            'effective_from' => 'required|date',
        ]);

        PensionScheme::where('company_id', $companyId)
            ->where('is_current', true)
            ->update(['is_current' => false, 'effective_to' => now()->subDay()]);

        $scheme = PensionScheme::create([
            'company_id' => $companyId,
            'name' => $validated['name'],
            'registration_number' => $validated['registration_number'] ?? null,
            'employee_rate' => $validated['employee_rate'],
            'employer_rate' => $validated['employer_rate'],
            'max_contributory_salary' => $validated['max_contributory_salary'] ?? null,
            'effective_from' => $validated['effective_from'],
            'is_current' => true,
        ]);

        return back()->with('success', 'Pension scheme created successfully.');
    }
}
