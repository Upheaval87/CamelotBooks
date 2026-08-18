<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CompanyAllowance;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeePayment;
use App\Models\EmployeeSalaryItem;
use App\Models\EmployeeSalaryStructure;
use App\Models\PayeTable;
use App\Models\PayeTableBand;
use App\Models\PayrollRun;
use App\Models\PayrollRunItem;
use App\Models\PayslipDelivery;
use App\Models\PensionScheme;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PayrollController extends Controller
{
    // ─────────────────────────────────────────────
    //  DASHBOARD
    // ─────────────────────────────────────────────

    /**
     * Payroll Centre dashboard — KPIs: total employees, active, on leave,
     * last run, pending approvals.
     */
    public function dashboard()
    {
        $companyId = (int) session('current_company_id');

        $totalEmployees = Employee::forCompany($companyId)->count();
        $activeEmployees = Employee::forCompany($companyId)->where('employment_status', 'active')->count();
        $onLeaveEmployees = Employee::forCompany($companyId)->where('employment_status', 'on_leave')->count();

        $lastRun = PayrollRun::forCompany($companyId)->latest()->first();
        $pendingApprovals = PayrollRun::forCompany($companyId)->where('status', 'pending_approval')->count();

        $recentPayslips = PayrollRunItem::forCompany($companyId)
            ->with('employee', 'payrollRun')
            ->latest()
            ->limit(5)
            ->get();

        return view('accounting.payroll.dashboard', compact(
            'companyId',
            'totalEmployees',
            'activeEmployees',
            'onLeaveEmployees',
            'lastRun',
            'pendingApprovals',
            'recentPayslips',
        ));
    }

    // ─────────────────────────────────────────────
    //  EMPLOYEES
    // ─────────────────────────────────────────────

    /**
     * Employees list — searchable, filterable by status / department / branch.
     */
    public function index(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $query = Employee::forCompany($companyId)
            ->with('currentSalaryStructure');

        if ($request->filled('status')) {
            $query->where('employment_status', $request->status);
        }

        if ($request->filled('department')) {
            $query->where('department', $request->department);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('first_name', 'like', "%{$search}%")
                    ->orWhere('last_name', 'like', "%{$search}%")
                    ->orWhere('employee_number', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $employees = $query->orderBy('first_name')->paginate(20)->withQueryString();

        $branches = Branch::forCompany($companyId)->active()->orderBy('name')->get();

        return view('accounting.payroll.employees', compact(
            'companyId',
            'employees',
            'branches',
        ));
    }

    /**
     * Employees create — empty onboarding wizard form.
     */
    public function create()
    {
        $companyId = (int) session('current_company_id');

        $branches = Branch::forCompany($companyId)->active()->orderBy('name')->get();
        $allowances = CompanyAllowance::forCompany($companyId)->active()->orderBy('name')->get();
        $pensionSchemes = PensionScheme::forCompany($companyId)->current()->get();

        return view('accounting.payroll.create', compact(
            'companyId',
            'branches',
            'allowances',
            'pensionSchemes',
        ));
    }

    /**
     * Store a new employee + salary structure + allowances.
     */
    public function store(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:employees,email',
            'phone'                 => 'nullable|string|max:50',
            'date_of_birth'         => 'nullable|date',
            'gender'                => 'nullable|string|in:male,female,other',
            'hire_date'             => 'required|date',
            'department'            => 'nullable|string|max:255',
            'job_title'             => 'nullable|string|max:255',
            'branch_id'             => 'nullable|exists:branches,id',
            'employment_type'       => 'nullable|string|in:full_time,part_time,contract,intern',
            'bank_name'             => 'nullable|string|max:255',
            'bank_account_number'   => 'nullable|string|max:50',
            'bank_account_name'     => 'nullable|string|max:255',
            'nrc_number'            => 'nullable|string|max:100',
            'tax_number'            => 'nullable|string|max:100',
            'basic_salary'          => 'required|numeric|min:0',
            'payment_frequency'     => 'required|string|in:monthly,weekly',
            'pension_scheme_id'     => 'nullable|exists:pension_schemes,id',
            'allowances'            => 'nullable|array',
            'allowances.*.allowance_id' => 'required_with:allowances|exists:company_allowances,id',
            'allowances.*.amount'        => 'required_with:allowances|numeric|min:0',
        ]);

        $validated['company_id'] = $companyId;
        $validated['employment_status'] = 'active';
        $validated['is_active'] = true;
        $validated['employee_number'] = $this->generateEmployeeNumber($companyId);

        $employee = Employee::create($validated);

        // TODO: Call PayrollService::createSalaryStructure() to persist salary structure + allowance items
        if ($request->filled('basic_salary')) {
            $structure = EmployeeSalaryStructure::create([
                'company_id'   => $companyId,
                'employee_id'  => $employee->id,
                'basic_salary' => $validated['basic_salary'],
                'pension_scheme_id' => $validated['pension_scheme_id'] ?? null,
                'effective_date'    => $validated['hire_date'],
                'is_current'        => true,
            ]);

            if ($request->filled('allowances')) {
                foreach ($request->allowances as $item) {
                    EmployeeSalaryItem::create([
                        'company_id'        => $companyId,
                        'salary_structure_id' => $structure->id,
                        'allowance_id'      => $item['allowance_id'],
                        'amount'            => $item['amount'],
                    ]);
                }
            }
        }

        return redirect()->route('accounting.payroll.employees.show', $employee)
            ->with('success', 'Employee created.');
    }

    /**
     * Employee profile — 12-tab view with salary summary, YTD gross,
     * payments, loans, etc.
     */
    public function show(Employee $employee)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $employee->company_id === $companyId, 404);

        $employee->load([
            'currentSalaryStructure.items.allowance',
            'payments.payrollRun',
            'loans',
            'deliveries.payrollRun',
        ]);

        // YTD gross from payroll_run_items
        $ytdGross = PayrollRunItem::forCompany($companyId)
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->whereYear('pay_period_start', now()->year))
            ->sum('gross_pay');

        $ytdDeductions = PayrollRunItem::forCompany($companyId)
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->whereYear('pay_period_start', now()->year))
            ->sum('total_deductions');

        $ytdNetPay = PayrollRunItem::forCompany($companyId)
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->whereYear('pay_period_start', now()->year))
            ->sum('net_pay');

        $recentPayments = $employee->payments->sortByDesc('created_at')->take(10);
        $activeLoans = $employee->loans->where('status', 'active');

        return view('accounting.payroll.show', compact(
            'employee',
            'ytdGross',
            'ytdDeductions',
            'ytdNetPay',
            'recentPayments',
            'activeLoans',
        ));
    }

    /**
     * Employees edit — populate the onboarding wizard with existing data.
     */
    public function edit(Employee $employee)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $employee->company_id === $companyId, 404);

        $employee->load('currentSalaryStructure.items');

        $branches = Branch::forCompany($companyId)->active()->orderBy('name')->get();
        $allowances = CompanyAllowance::forCompany($companyId)->active()->orderBy('name')->get();
        $pensionSchemes = PensionScheme::forCompany($companyId)->current()->get();

        return view('accounting.payroll.edit', compact(
            'employee',
            'companyId',
            'branches',
            'allowances',
            'pensionSchemes',
        ));
    }

    /**
     * Employees update.
     */
    public function update(Request $request, Employee $employee)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $employee->company_id === $companyId, 404);

        $validated = $request->validate([
            'first_name'            => 'required|string|max:255',
            'last_name'             => 'required|string|max:255',
            'email'                 => 'required|email|max:255|unique:employees,email,' . $employee->id,
            'phone'                 => 'nullable|string|max:50',
            'date_of_birth'         => 'nullable|date',
            'gender'                => 'nullable|string|in:male,female,other',
            'hire_date'             => 'required|date',
            'department'            => 'nullable|string|max:255',
            'job_title'             => 'nullable|string|max:255',
            'branch_id'             => 'nullable|exists:branches,id',
            'employment_status'    => ['nullable', 'string', Rule::in(['active', 'inactive', 'on_leave', 'terminated'])],
            'employment_type'       => 'nullable|string|in:full_time,part_time,contract,intern',
            'bank_name'             => 'nullable|string|max:255',
            'bank_account_number'   => 'nullable|string|max:50',
            'bank_account_name'     => 'nullable|string|max:255',
            'nrc_number'            => 'nullable|string|max:100',
            'tax_number'            => 'nullable|string|max:100',
            'basic_salary'          => 'required|numeric|min:0',
            'payment_frequency'     => 'required|string|in:monthly,weekly',
            'pension_scheme_id'     => 'nullable|exists:pension_schemes,id',
            'allowances'            => 'nullable|array',
            'allowances.*.allowance_id' => 'required_with:allowances|exists:company_allowances,id',
            'allowances.*.amount'        => 'required_with:allowances|numeric|min:0',
        ]);

        $employee->update($validated);

        // TODO: Call PayrollService::updateSalaryStructure() to update salary structure + allowance items
        $structure = $employee->currentSalaryStructure;
        if ($structure) {
            $structure->update([
                'basic_salary'      => $validated['basic_salary'],
                'pension_scheme_id' => $validated['pension_scheme_id'] ?? null,
            ]);

            if ($request->filled('allowances')) {
                $structure->items()->delete();
                foreach ($request->allowances as $item) {
                    EmployeeSalaryItem::create([
                        'company_id'          => $companyId,
                        'salary_structure_id' => $structure->id,
                        'allowance_id'        => $item['allowance_id'],
                        'amount'              => $item['amount'],
                    ]);
                }
            }
        }

        return redirect()->route('accounting.payroll.employees.show', $employee)
            ->with('success', 'Employee updated.');
    }

    // ─────────────────────────────────────────────
    //  PAYROLL RUNS
    // ─────────────────────────────────────────────

    /**
     * Runs list — all payroll runs with status chips.
     */
    public function runs()
    {
        $companyId = (int) session('current_company_id');

        $runs = PayrollRun::forCompany($companyId)
            ->with('createdBy')
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return view('accounting.payroll.runs', compact('runs'));
    }

    /**
     * Runs create — form to create a new payroll run.
     */
    public function createRun()
    {
        $companyId = (int) session('current_company_id');

        $employees = Employee::forCompany($companyId)->active()->get();
        $payeTable = PayeTable::forCompany($companyId)->current()->first();
        $pensionScheme = PensionScheme::forCompany($companyId)->current()->first();
        $branches = Branch::forCompany($companyId)->active()->orderBy('name')->get();

        return view('accounting.payroll.runs-create', compact(
            'companyId',
            'employees',
            'payeTable',
            'pensionScheme',
            'branches',
        ));
    }

    /**
     * Runs store — create run + calculate items.
     */
    public function storeRun(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'pay_period_start'  => 'required|date',
            'pay_period_end'    => 'required|date|after_or_equal:pay_period_start',
            'payment_date'      => 'required|date',
            'branch_id'         => 'nullable|exists:branches,id',
            'employee_ids'      => 'required|array|min:1',
            'employee_ids.*'    => 'exists:employees,id',
        ]);

        $run = PayrollRun::create([
            'company_id'       => $companyId,
            'pay_period_start' => $validated['pay_period_start'],
            'pay_period_end'   => $validated['pay_period_end'],
            'payment_date'     => $validated['payment_date'],
            'branch_id'        => $validated['branch_id'] ?? null,
            'status'           => 'draft',
            'created_by'       => auth()->id(),
        ]);

        // TODO: Call PayrollService::calculate($run, $validated['employee_ids'])
        // to create PayrollRunItem records with gross/deductions/net calculations

        return redirect()->route('accounting.payroll.runs.show', $run)
            ->with('success', 'Run created.');
    }

    /**
     * Runs show — run detail with step workflow.
     */
    public function showRun(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);

        $run->load([
            'items.employee',
            'createdBy',
            'approvedByUser',
            'payeTable',
            'pensionScheme',
        ]);

        $totalGross = $run->items->sum('gross_pay');
        $totalDeductions = $run->items->sum('total_deductions');
        $totalNetPay = $run->items->sum('net_pay');
        $employeeCount = $run->items->count();

        return view('accounting.payroll.runs-show', compact(
            'run',
            'totalGross',
            'totalDeductions',
            'totalNetPay',
            'employeeCount',
        ));
    }

    /**
     * Runs calculate — apply the calculation engine to produce RunItem records.
     */
    public function calculateRun(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless($run->status === 'draft', 422, 'Run must be in draft status to calculate.');

        // TODO: Call PayrollService::calculate($run) to create PayrollRunItem records

        $run->update(['status' => 'calculated']);

        return back()->with('success', 'Run calculated.');
    }

    /**
     * Runs submit — submit for approval.
     */
    public function submitRun(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless($run->status === 'calculated', 422, 'Run must be calculated before submitting.');

        $run->update(['status' => 'pending_approval']);

        return back()->with('success', 'Run submitted for approval.');
    }

    /**
     * Runs approve.
     */
    public function approveRun(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless($run->status === 'pending_approval', 422, 'Run is not pending approval.');

        $run->update([
            'status'       => 'approved',
            'approved_at'  => now(),
            'approved_by'  => auth()->id(),
        ]);

        return back()->with('success', 'Run approved.');
    }

    /**
     * Runs post — post to GL (create journal entry).
     */
    public function postRun(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless($run->status === 'approved', 422, 'Run must be approved before posting.');

        // TODO: Call PayrollService::postToGeneralLedger($run)
        // to create the journal entry via JournalPostingEngine

        $run->update(['status' => 'posted', 'posted_at' => now()]);

        return back()->with('success', 'Run posted to GL.');
    }

    /**
     * Runs pay — mark as paid, create EmployeePayment records.
     */
    public function payRun(PayrollRun $run)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless($run->status === 'posted', 422, 'Run must be posted before recording payments.');

        // TODO: Call PayrollService::recordPayments($run)
        // to create EmployeePayment records for each item

        $run->update([
            'status'     => 'partially_paid',
            'paid_at'    => now(),
            'paid_by'    => auth()->id(),
        ]);

        return back()->with('success', 'Payments recorded.');
    }

    // ─────────────────────────────────────────────
    //  PAYSLIPS
    // ─────────────────────────────────────────────

    /**
     * Payslips — list all payslips across runs.
     */
    public function payslips()
    {
        $companyId = (int) session('current_company_id');

        $payslips = PayrollRunItem::forCompany($companyId)
            ->with(['employee', 'payrollRun'])
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('accounting.payroll.payslips', compact('payslips'));
    }

    /**
     * Payslip show — single payslip detail.
     */
    public function showPayslip(PayrollRunItem $payslip)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $payslip->company_id === $companyId, 404);

        $payslip->load([
            'employee.currentSalaryStructure.items.allowance',
            'payrollRun.payeTable',
            'payrollRun.pensionScheme',
        ]);

        return view('accounting.payroll.payslip-show', compact('payslip'));
    }

    /**
     * Send payslip — create a PayslipDelivery record for the given payslip.
     */
    public function sendPayslip(PayrollRunItem $payslip)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $payslip->company_id === $companyId, 404);

        $employee = $payslip->employee;

        $delivery = PayslipDelivery::create([
            'company_id' => $companyId,
            'payroll_run_id' => $payslip->payroll_run_id,
            'employee_id' => $payslip->employee_id,
            'status' => 'sent',
            'email_address' => $employee->email,
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Payslip sent to ' . $employee->full_name . '.');
    }

    // ─────────────────────────────────────────────
    //  STATUTORY
    // ─────────────────────────────────────────────

    /**
     * Statutory — PAYE tables + pension schemes management.
     */
    public function statutory()
    {
        $companyId = (int) session('current_company_id');

        $payeTables = PayeTable::forCompany($companyId)->latest()->get();
        $pensionSchemes = PensionScheme::forCompany($companyId)->latest()->get();

        return view('accounting.payroll.statutory', compact(
            'companyId',
            'payeTables',
            'pensionSchemes',
        ));
    }

    /**
     * Statutory store — create PAYE table or pension scheme.
     */
    public function storeStatutory(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'type' => 'required|string|in:paye_table,pension_scheme',
            'name' => 'required|string|max:255',
        ]);

        if ($validated['type'] === 'paye_table') {
            $request->validate([
                'year'   => 'required|integer|min:2000',
                'bands'  => 'required|array|min:1',
                'bands.*.lower'  => 'required|numeric|min:0',
                'bands.*.upper'  => 'required|numeric|gte:bands.*.lower',
                'bands.*.rate'   => 'required|numeric|min:0|max:100',
            ]);

            $table = PayeTable::create([
                'company_id' => $companyId,
                'name'       => $validated['name'],
                'year'       => $request->year,
                'is_current' => true,
            ]);

            foreach ($request->bands as $band) {
                PayeTableBand::create([
                    'company_id'  => $companyId,
                    'paye_table_id' => $table->id,
                    'lower_limit'  => $band['lower'],
                    'upper_limit'  => $band['upper'],
                    'rate'         => $band['rate'],
                ]);
            }
        } elseif ($validated['type'] === 'pension_scheme') {
            $request->validate([
                'employer_contribution_rate' => 'required|numeric|min:0|max:100',
                'employee_contribution_rate'  => 'required|numeric|min:0|max:100',
                'effective_date'              => 'required|date',
            ]);

            PensionScheme::create([
                'company_id'                => $companyId,
                'name'                      => $validated['name'],
                'employer_contribution_rate' => $request->employer_contribution_rate,
                'employee_contribution_rate'  => $request->employee_contribution_rate,
                'effective_date'              => $request->effective_date,
                'is_current'                 => true,
            ]);
        }

        return back()->with('success', 'Record saved.');
    }

    // ─────────────────────────────────────────────
    //  PEOPLE OPS (Loans & Advances)
    // ─────────────────────────────────────────────

    /**
     * People ops — employee loans management.
     */
    public function people()
    {
        $companyId = (int) session('current_company_id');

        $employees = Employee::forCompany($companyId)
            ->active()
            ->orderBy('first_name')
            ->get();

        $loans = EmployeeLoan::forCompany($companyId)
            ->with('employee')
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('accounting.payroll.people', compact(
            'companyId',
            'employees',
            'loans',
        ));
    }

    /**
     * People store — create employee loan.
     */
    public function storePeople(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'amount'      => 'required|numeric|min:0.01',
            'repayment_months' => 'required|integer|min:1',
            'monthly_repayment' => 'required|numeric|min:0',
            'reason'      => 'nullable|string|max:1000',
            'start_date'  => 'required|date',
        ]);

        $validated['company_id'] = $companyId;
        $validated['status'] = 'active';
        $validated['outstanding_balance'] = $validated['amount'];

        EmployeeLoan::create($validated);

        return back()->with('success', 'Loan created.');
    }

    // ─────────────────────────────────────────────
    //  REPORTS
    // ─────────────────────────────────────────────

    /**
     * Reports — payroll reports page.
     */
    public function reports()
    {
        $companyId = (int) session('current_company_id');

        $runs = PayrollRun::forCompany($companyId)
            ->latest()
            ->limit(12)
            ->get();

        return view('accounting.payroll.reports', compact(
            'companyId',
            'runs',
        ));
    }

    // ─────────────────────────────────────────────
    //  SETTINGS
    // ─────────────────────────────────────────────

    /**
     * Settings — payroll configuration page.
     */
    public function settings()
    {
        $companyId = (int) session('current_company_id');

        $payeTables = PayeTable::forCompany($companyId)->latest()->get();
        $pensionSchemes = PensionScheme::forCompany($companyId)->latest()->get();
        $allowances = CompanyAllowance::forCompany($companyId)->latest()->get();

        return view('accounting.payroll.settings', compact(
            'companyId',
            'payeTables',
            'pensionSchemes',
            'allowances',
        ));
    }

    /**
     * Settings store — update allowances, payroll settings.
     */
    public function storeSettings(Request $request)
    {
        $companyId = (int) session('current_company_id');

        $validated = $request->validate([
            'allowances'                => 'nullable|array',
            'allowances.*.id'           => 'nullable|exists:company_allowances,id',
            'allowances.*.name'         => 'required_with:allowances|string|max:255',
            'allowances.*.type'         => 'required_with:allowances|string|in:fixed,percentage',
            'allowances.*.default_amount' => 'required_with:allowances|numeric|min:0',
            'allowances.*.is_taxable'   => 'required_with:allowances|boolean',
            'allowances.*.is_active'    => 'nullable|boolean',
        ]);

        if ($request->filled('allowances')) {
            foreach ($request->allowances as $item) {
                $data = [
                    'company_id'      => $companyId,
                    'name'            => $item['name'],
                    'type'            => $item['type'],
                    'default_amount'  => $item['default_amount'],
                    'is_taxable'      => $item['is_taxable'] ?? false,
                    'is_active'       => $item['is_active'] ?? true,
                ];

                if (!empty($item['id'])) {
                    CompanyAllowance::where('id', $item['id'])
                        ->where('company_id', $companyId)
                        ->update($data);
                } else {
                    CompanyAllowance::create($data);
                }
            }
        }

        return back()->with('success', 'Settings saved.');
    }

    // ─────────────────────────────────────────────
    //  HELPERS
    // ─────────────────────────────────────────────

    /**
     * Generate a unique employee number for the company.
     */
    private function generateEmployeeNumber(int $companyId): string
    {
        $lastNumber = Employee::forCompany($companyId)
            ->whereNotNull('employee_number')
            ->orderByDesc('employee_number')
            ->value('employee_number');

        $next = 1;
        if ($lastNumber && preg_match('/\d+$/', $lastNumber, $m)) {
            $next = ((int) $m[0]) + 1;
        }

        return 'EMP-' . str_pad($next, 5, '0', STR_PAD_LEFT);
    }
}
