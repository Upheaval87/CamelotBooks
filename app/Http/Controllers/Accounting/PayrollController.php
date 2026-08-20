<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Branch;
use App\Models\CompanyAllowance;
use App\Models\Employee;
use App\Models\EmployeeBeneficiary;
use App\Models\EmployeeDocument;
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
use App\Services\Payroll\PayrollService;
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

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

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

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
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
            'national_id'           => 'nullable|string|max:100',
            'tax_id'                => 'nullable|string|max:100',
            'nationality'           => 'nullable|string|max:100',
            'marital_status'        => 'nullable|string|in:single,married,divorced,widowed',
            'dependents'            => 'nullable|integer|min:0',
            'place_of_residence'    => 'nullable|string|max:255',
            'home_village'          => 'nullable|string|max:255',
            'home_district'         => 'nullable|string|max:100',
            'nok_name'              => 'nullable|string|max:255',
            'nok_relationship'      => 'nullable|string|in:spouse,parent,child,sibling,other',
            'nok_phone'             => 'nullable|string|max:50',
            'hire_date'             => 'required|date',
            'department'            => 'nullable|string|max:255',
            'position'              => 'nullable|string|max:255',
            'branch_id'             => 'nullable|exists:branches,id',
            'employment_type'       => 'nullable|string|in:full_time,part_time,contract,casual,temporary',
            'employment_end_date'   => 'nullable|date|required_if:employment_type,part_time,contract,casual,temporary',
            'employment_status'     => 'nullable|string|in:active,on_leave,terminated',
            'basic_salary'          => 'required|numeric|min:0',
            'payment_frequency'     => 'required|string|in:monthly,weekly',
            'housing_allowance'     => 'nullable|numeric|min:0',
            'transport_allowance'   => 'nullable|numeric|min:0',
            'other_allowances'      => 'nullable|numeric|min:0',
            'pension_scheme_id'     => 'nullable|exists:pension_schemes,id',
            'pension_member_number' => 'nullable|string|max:100',
            'pension_contribution'  => 'nullable|numeric|min:0|max:100',
            'other_deductions'      => 'nullable|numeric|min:0',
            'payment_method'        => 'nullable|string|in:bank_transfer,mobile_money,cash',
            'bank_name'             => 'nullable|string|max:255',
            'bank_account_number'   => 'nullable|string|max:50',
            'bank_account_name'     => 'nullable|string|max:255',
            'bank_branch_code'      => 'nullable|string|max:255',
            'mobile_money_provider' => 'nullable|string|max:100',
            'mobile_money_number'   => 'nullable|string|max:50',
            'payslip_password'      => 'nullable|string|max:255',
            'allowances'            => 'nullable|array',
            'allowances.*.allowance_id' => 'required_with:allowances|exists:company_allowances,id',
            'allowances.*.amount'        => 'required_with:allowances|numeric|min:0',
            'beneficiaries'            => 'nullable|array',
            'beneficiaries.*.full_name' => 'required_with:beneficiaries|string|max:255',
            'beneficiaries.*.relationship' => 'required_with:beneficiaries|string|in:spouse,child,parent,sibling,other',
            'beneficiaries.*.phone'    => 'nullable|string|max:50',
            'beneficiaries.*.pct'      => 'required_with:beneficiaries|numeric|min:0.01|max:100',
        ]);

        $validated['company_id'] = $companyId;
        $validated['employment_status'] = $validated['employment_status'] ?? 'active';
        $validated['is_active'] = true;
        $validated['employee_number'] = $this->generateEmployeeNumber($companyId);

        $employee = Employee::create(collect($validated)->except([
            'basic_salary', 'payment_frequency', 'housing_allowance',
            'transport_allowance', 'other_allowances',
            'pension_scheme_id', 'pension_member_number', 'pension_contribution', 'other_deductions',
            'allowances', 'beneficiaries',
        ])->toArray());

        if ($request->filled('basic_salary')) {
            $structure = EmployeeSalaryStructure::create([
                'company_id'     => $companyId,
                'employee_id'    => $employee->id,
                'basic_pay'      => $validated['basic_salary'],
                'effective_from' => $validated['hire_date'],
                'is_current'     => true,
            ]);

            if ($request->filled('allowances')) {
                foreach ($request->allowances as $item) {
                    EmployeeSalaryItem::create([
                        'company_id'           => $companyId,
                        'salary_structure_id'  => $structure->id,
                        'company_allowance_id' => $item['allowance_id'],
                        'amount'               => $item['amount'],
                    ]);
                }
            }
        }

        // Beneficiaries
        if ($request->filled('beneficiaries')) {
            foreach ($request->beneficiaries as $i => $ben) {
                if (!empty($ben['full_name'])) {
                    EmployeeBeneficiary::create([
                        'company_id'    => $companyId,
                        'employee_id'   => $employee->id,
                        'full_name'     => $ben['full_name'],
                        'relationship'  => $ben['relationship'] ?? 'other',
                        'phone'         => $ben['phone'] ?? null,
                        'pct'           => $ben['pct'] ?? 0,
                        'sort_order'    => $i,
                    ]);
                }
            }
        }

        // Documents
        $this->handleDocuments($request, $employee, $companyId);

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
            ->whereHas('payrollRun', fn ($q) => $q->whereYear('period_start', now()->year))
            ->sum('gross_pay');

        $ytdDeductions = PayrollRunItem::forCompany($companyId)
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->whereYear('period_start', now()->year))
            ->sum('total_deductions');

        $ytdNetPay = PayrollRunItem::forCompany($companyId)
            ->where('employee_id', $employee->id)
            ->whereHas('payrollRun', fn ($q) => $q->whereYear('period_start', now()->year))
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

        $employee->load([
            'currentSalaryStructure.items.allowance',
            'documents',
            'beneficiaries',
        ]);

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
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
            'national_id'           => 'nullable|string|max:100',
            'tax_id'                => 'nullable|string|max:100',
            'nationality'           => 'nullable|string|max:100',
            'marital_status'        => 'nullable|string|in:single,married,divorced,widowed',
            'dependents'            => 'nullable|integer|min:0',
            'place_of_residence'    => 'nullable|string|max:255',
            'home_village'          => 'nullable|string|max:255',
            'home_district'         => 'nullable|string|max:100',
            'nok_name'              => 'nullable|string|max:255',
            'nok_relationship'      => 'nullable|string|in:spouse,parent,child,sibling,other',
            'nok_phone'             => 'nullable|string|max:50',
            'hire_date'             => 'required|date',
            'department'            => 'nullable|string|max:255',
            'position'              => 'nullable|string|max:255',
            'branch_id'             => 'nullable|exists:branches,id',
            'employment_type'       => 'nullable|string|in:full_time,part_time,contract,casual,temporary',
            'employment_end_date'   => 'nullable|date|required_if:employment_type,part_time,contract,casual,temporary',
            'employment_status'     => ['nullable', 'string', Rule::in(['active', 'inactive', 'on_leave', 'terminated'])],
            'basic_salary'          => 'required|numeric|min:0',
            'payment_frequency'     => 'required|string|in:monthly,weekly',
            'pension_scheme_id'     => 'nullable|exists:pension_schemes,id',
            'pension_member_number' => 'nullable|string|max:100',
            'pension_contribution'  => 'nullable|numeric|min:0|max:100',
            'other_deductions'      => 'nullable|numeric|min:0',
            'payment_method'        => 'nullable|string|in:bank_transfer,mobile_money,cash',
            'bank_name'             => 'nullable|string|max:255',
            'bank_account_number'   => 'nullable|string|max:50',
            'bank_account_name'     => 'nullable|string|max:255',
            'bank_branch_code'      => 'nullable|string|max:255',
            'mobile_money_provider' => 'nullable|string|max:100',
            'mobile_money_number'   => 'nullable|string|max:50',
            'payslip_password'      => 'nullable|string|max:255',
            'allowances'            => 'nullable|array',
            'allowances.*.allowance_id' => 'required_with:allowances|exists:company_allowances,id',
            'allowances.*.amount'        => 'required_with:allowances|numeric|min:0',
            'beneficiaries'            => 'nullable|array',
            'beneficiaries.*.full_name' => 'required_with:beneficiaries|string|max:255',
            'beneficiaries.*.relationship' => 'required_with:beneficiaries|string|in:spouse,child,parent,sibling,other',
            'beneficiaries.*.phone'    => 'nullable|string|max:50',
            'beneficiaries.*.pct'      => 'required_with:beneficiaries|numeric|min:0.01|max:100',
            'delete_documents'         => 'nullable|array',
            'delete_documents.*'       => 'integer',
        ]);

        $employee->update(collect($validated)->except([
            'basic_salary', 'payment_frequency', 'pension_scheme_id',
            'pension_member_number', 'pension_contribution', 'other_deductions',
            'allowances', 'beneficiaries', 'delete_documents',
        ])->toArray());

        // Salary structure
        if ($request->filled('basic_salary')) {
            $structure = $employee->currentSalaryStructure;
            if ($structure) {
                $structure->update([
                    'basic_pay' => $validated['basic_salary'],
                ]);
                if ($request->filled('allowances')) {
                    $structure->items()->delete();
                    foreach ($request->allowances as $item) {
                        EmployeeSalaryItem::create([
                            'company_id'           => $companyId,
                            'salary_structure_id'  => $structure->id,
                            'company_allowance_id' => $item['allowance_id'],
                            'amount'               => $item['amount'],
                        ]);
                    }
                }
            } else {
                EmployeeSalaryStructure::where('employee_id', $employee->id)->update(['is_current' => false]);
                $structure = EmployeeSalaryStructure::create([
                    'company_id'     => $companyId,
                    'employee_id'    => $employee->id,
                    'basic_pay'      => $validated['basic_salary'],
                    'effective_from' => $validated['hire_date'],
                    'is_current'     => true,
                ]);
                if ($request->filled('allowances')) {
                    foreach ($request->allowances as $item) {
                        EmployeeSalaryItem::create([
                            'company_id'           => $companyId,
                            'salary_structure_id'  => $structure->id,
                            'company_allowance_id' => $item['allowance_id'],
                            'amount'               => $item['amount'],
                        ]);
                    }
                }
            }
        }

        // Beneficiaries — replace all
        EmployeeBeneficiary::where('employee_id', $employee->id)->delete();
        if ($request->filled('beneficiaries')) {
            foreach ($request->beneficiaries as $i => $ben) {
                if (!empty($ben['full_name'])) {
                    EmployeeBeneficiary::create([
                        'company_id'    => $companyId,
                        'employee_id'   => $employee->id,
                        'full_name'     => $ben['full_name'],
                        'relationship'  => $ben['relationship'] ?? 'other',
                        'phone'         => $ben['phone'] ?? null,
                        'pct'           => $ben['pct'] ?? 0,
                        'sort_order'    => $i,
                    ]);
                }
            }
        }

        // Delete flagged documents
        if ($request->filled('delete_documents')) {
            $deleteIds = $request->input('delete_documents');
            $docs = EmployeeDocument::where('employee_id', $employee->id)
                ->whereIn('id', $deleteIds)
                ->get();
            foreach ($docs as $doc) {
                \Storage::disk('private')->delete($doc->storage_ref);
                $doc->delete();
            }
        }

        // New documents
        $this->handleDocuments($request, $employee, $companyId);

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
        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

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
    public function storeRun(Request $request, PayrollService $payrollService)
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

        $runNumber = $payrollService->generateRunNumber($companyId);

        $periodStart = $validated['pay_period_start'];
        $periodEnd = $validated['pay_period_end'];

        $run = PayrollRun::create([
            'company_id'       => $companyId,
            'run_number'       => $runNumber,
            'period_label'     => $periodStart . ' to ' . $periodEnd,
            'period_start'     => $periodStart,
            'period_end'       => $periodEnd,
            'pay_date'         => $validated['payment_date'],
            'branch_id'        => $validated['branch_id'] ?? null,
            'paye_table_id'    => PayeTable::forCompany($companyId)->current()->value('id'),
            'pension_scheme_id' => PensionScheme::forCompany($companyId)->current()->value('id'),
            'status'           => 'draft',
            'created_by'       => auth()->id(),
        ]);

        foreach ($validated['employee_ids'] as $employeeId) {
            PayrollRunItem::create([
                'payroll_run_id' => $run->id,
                'employee_id'    => $employeeId,
            ]);
        }

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
    public function calculateRun(PayrollRun $run, PayrollService $payrollService)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless($run->status === 'draft', 422, 'Run must be in draft status to calculate.');

        $payrollService->calculate($run);

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
    public function postRun(PayrollRun $run, PayrollService $payrollService)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless(in_array($run->status, ['approved', 'calculated']), 422, 'Run must be approved before posting.');

        $payrollService->postToGeneralLedger($run);

        return back()->with('success', 'Run posted to GL.');
    }

    /**
     * Runs pay — mark as paid, create EmployeePayment records.
     */
    public function payRun(PayrollRun $run, PayrollService $payrollService)
    {
        $companyId = (int) session('current_company_id');

        abort_unless((int) $run->company_id === $companyId, 404);
        abort_unless($run->status === 'posted', 422, 'Run must be posted before recording payments.');

        $payrollService->recordPayments($run);

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

        abort_unless((int) $payslip->payrollRun->company_id === $companyId, 404);

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

        abort_unless((int) $payslip->payrollRun->company_id === $companyId, 404);

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
                'effective_from' => 'required|date',
                'bands'          => 'required|array|min:1',
                'bands.*.threshold'   => 'required|numeric|min:0',
                'bands.*.upper_limit' => 'nullable|numeric|gte:bands.*.threshold',
                'bands.*.rate'        => 'required|numeric|min:0|max:100',
            ]);

            $table = PayeTable::create([
                'company_id'     => $companyId,
                'version_name'   => $validated['name'],
                'effective_from' => $request->effective_from,
                'effective_to'   => $request->effective_to ?? null,
                'is_current'     => true,
            ]);

            foreach ($request->bands as $i => $band) {
                PayeTableBand::create([
                    'paye_table_id' => $table->id,
                    'threshold'     => $band['threshold'],
                    'upper_limit'   => $band['upper_limit'] ?? null,
                    'rate'          => $band['rate'],
                    'sort_order'    => $i,
                ]);
            }
        } elseif ($validated['type'] === 'pension_scheme') {
            $request->validate([
                'employer_rate'       => 'required|numeric|min:0|max:100',
                'employee_rate'       => 'required|numeric|min:0|max:100',
                'effective_from'      => 'required|date',
                'max_contributory_salary' => 'nullable|numeric|min:0',
            ]);

            PensionScheme::create([
                'company_id'               => $companyId,
                'name'                     => $validated['name'],
                'employer_rate'            => $request->employer_rate,
                'employee_rate'            => $request->employee_rate,
                'max_contributory_salary'  => $request->max_contributory_salary ?? null,
                'effective_from'           => $request->effective_from,
                'effective_to'             => $request->effective_to ?? null,
                'is_current'               => true,
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
            'employee_id'      => 'required|exists:employees,id',
            'amount'           => 'required|numeric|min:0.01',
            'monthly_repayment' => 'required|numeric|min:0',
            'interest_rate'    => 'nullable|numeric|min:0|max:100',
            'start_date'       => 'required|date',
            'end_date'         => 'nullable|date|after_or_equal:start_date',
            'notes'            => 'nullable|string|max:1000',
        ]);

        $runNumber = 'LOAN-' . now()->format('Y') . '-' . str_pad(
            EmployeeLoan::forCompany($companyId)->count() + 1, 4, '0', STR_PAD_LEFT
        );

        EmployeeLoan::create([
            'company_id'          => $companyId,
            'employee_id'         => $validated['employee_id'],
            'loan_number'         => $runNumber,
            'principal_amount'    => $validated['amount'],
            'outstanding_balance' => $validated['amount'],
            'monthly_deduction'   => $validated['monthly_repayment'],
            'interest_rate'       => $validated['interest_rate'] ?? 0,
            'start_date'          => $validated['start_date'],
            'end_date'            => $validated['end_date'] ?? null,
            'status'              => 'active',
            'notes'               => $validated['notes'] ?? null,
        ]);

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

    /**
     * Handle document uploads for employee onboarding/edit.
     */
    private function handleDocuments(Request $request, Employee $employee, int $companyId): void
    {
        $uploadMap = [
            'document_photo'       => ['kind' => 'photo',     'label' => 'Passport Photo', 'mimes' => 'jpg,jpeg,png', 'maxKb' => 2048],
            'document_national_id' => ['kind' => 'national_id', 'label' => 'National ID',   'mimes' => 'pdf,jpg,jpeg,png', 'maxKb' => 5120],
            'document_custom_1'    => ['kind' => 'custom',     'label' => null, 'mimes' => 'pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,csv', 'maxKb' => 10240],
            'document_custom_2'    => ['kind' => 'custom',     'label' => null, 'mimes' => 'pdf,jpg,jpeg,png,gif,webp,doc,docx,xls,xlsx,txt,csv', 'maxKb' => 10240],
        ];

        foreach ($uploadMap as $inputName => $cfg) {
            if ($request->hasFile($inputName)) {
                $file = $request->file($inputName);
                $ext = $file->getClientOriginalExtension();
                $dir = "employee-documents/{$companyId}/{$employee->id}";
                $filename = uniqid() . '.' . $ext;
                $path = $file->storeAs($dir, $filename, 'private');

                $kind = $cfg['kind'];
                $fieldName = null;
                if ($kind === 'custom') {
                    $fieldName = $request->input($inputName . '_name');
                    if (empty($fieldName)) {
                        $fieldName = $inputName === 'document_custom_1'
                            ? $request->input('document_custom_1_name', 'Attachment 1')
                            : $request->input('document_custom_2_name', 'Attachment 2');
                    }
                }

                EmployeeDocument::create([
                    'company_id'  => $companyId,
                    'employee_id' => $employee->id,
                    'kind'        => $kind,
                    'field_name'  => $fieldName,
                    'mime'        => $file->getMimeType(),
                    'size_bytes'  => $file->getSize(),
                    'storage_ref' => $path,
                    'created_by'  => auth()->id(),
                ]);
            }
        }
    }
}
