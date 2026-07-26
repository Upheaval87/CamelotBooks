<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\EmployeeSalaryStructure;
use App\Models\EmployeeSalaryItem;
use App\Models\CompanyAllowance;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\CostCenter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');

        $employees = Employee::where('company_id', $companyId)
            ->with(['branch', 'costCenter'])
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(20);

        return view('accounting.employees.index', compact('employees'));
    }

    public function create()
    {
        $companyId = session('current_company_id');

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();

        return view('accounting.employees.create', compact('branches', 'costCenters'));
    }

    public function store(Request $request)
    {
        $companyId = session('current_company_id');

        $validated = $request->validate([
            'employee_number' => 'required|string|max:50',
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'date_of_birth' => 'nullable|date',
            'gender' => 'nullable|string|in:male,female,other',
            'address' => 'nullable|string|max:500',
            'position' => 'nullable|string|max:200',
            'department' => 'nullable|string|max:200',
            'hire_date' => 'required|date',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'tax_id' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:100',
            'pension_member_number' => 'nullable|string|max:100',
            'pension_scheme_id' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:200',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:200',
            'bank_branch_code' => 'nullable|string|max:50',
            'basic_pay' => 'nullable|numeric|min:0',
            'payslip_password' => 'nullable|string|max:255',
        ]);

        $basicPay = $validated['basic_pay'] ?? 0;
        unset($validated['basic_pay']);

        $payslipPassword = $validated['payslip_password'] ?? null;
        unset($validated['payslip_password']);

        $validated['company_id'] = $companyId;

        DB::transaction(function () use ($validated, $basicPay, $companyId, $payslipPassword) {
            $employee = Employee::create($validated);

            if ($payslipPassword !== null) {
                $employee->setPayslipPasswordValueAttribute($payslipPassword);
                $employee->save();
            }

            if ($basicPay > 0) {
                EmployeeSalaryStructure::create([
                    'company_id' => $companyId,
                    'employee_id' => $employee->id,
                    'basic_pay' => $basicPay,
                    'effective_from' => $validated['hire_date'],
                    'is_current' => true,
                ]);
            }

            return $employee;
        });

        return redirect()->route('accounting.employees.index')
            ->with('success', 'Employee created successfully.');
    }

    public function show(Employee $employee)
    {
        $companyId = session('current_company_id');

        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $employee->load(['branch', 'costCenter', 'currentSalaryStructure.items', 'payments']);

        return view('accounting.employees.show', compact('employee'));
    }

    public function edit(Employee $employee)
    {
        $companyId = session('current_company_id');

        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $branches = Branch::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $costCenters = CostCenter::where('company_id', $companyId)->where('is_active', true)->orderBy('name')->get();
        $employee->load('currentSalaryStructure.items');

        return view('accounting.employees.edit', compact('employee', 'branches', 'costCenters'));
    }

    public function update(Request $request, Employee $employee)
    {
        $companyId = session('current_company_id');

        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'position' => 'nullable|string|max:200',
            'department' => 'nullable|string|max:200',
            'branch_id' => 'nullable|exists:branches,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'tax_id' => 'nullable|string|max:100',
            'national_id' => 'nullable|string|max:100',
            'pension_member_number' => 'nullable|string|max:100',
            'bank_name' => 'nullable|string|max:200',
            'bank_account_number' => 'nullable|string|max:100',
            'bank_account_name' => 'nullable|string|max:200',
            'employment_status' => 'nullable|string|in:active,terminated,suspended,on_leave',
            'termination_date' => 'nullable|date',
            'is_active' => 'boolean',
            'basic_pay' => 'nullable|numeric|min:0',
            'payslip_password' => 'nullable|string|max:255',
        ]);

        $basicPay = $validated['basic_pay'] ?? null;
        unset($validated['basic_pay']);

        $payslipPassword = $validated['payslip_password'] ?? null;
        unset($validated['payslip_password']);

        DB::transaction(function () use ($employee, $validated, $basicPay, $companyId, $payslipPassword) {
            if (isset($validated['employment_status']) && $validated['employment_status'] === 'terminated') {
                $validated['is_active'] = false;
                $validated['termination_date'] = $validated['termination_date'] ?? now()->format('Y-m-d');
            }

            $employee->update($validated);

            if ($payslipPassword !== null) {
                $employee->setPayslipPasswordValueAttribute($payslipPassword);
                $employee->save();

                AuditLog::log(
                    $companyId,
                    auth()->id(),
                    Employee::class,
                    $employee->id,
                    'payslip_password_changed',
                    null,
                    ['payslip_password' => '[REDACTED]'],
                    'Payslip password was updated'
                );
            }

            if ($basicPay !== null) {
                $currentStructure = EmployeeSalaryStructure::where('employee_id', $employee->id)->current()->first();

                if ($currentStructure) {
                    if ((float) $currentStructure->basic_pay !== (float) $basicPay) {
                        $currentStructure->update(['is_current' => false, 'effective_to' => now()->subDay()]);

                        EmployeeSalaryStructure::create([
                            'company_id' => $companyId,
                            'employee_id' => $employee->id,
                            'basic_pay' => $basicPay,
                            'effective_from' => now(),
                            'is_current' => true,
                        ]);
                    }
                } elseif ($basicPay > 0) {
                    EmployeeSalaryStructure::create([
                        'company_id' => $companyId,
                        'employee_id' => $employee->id,
                        'basic_pay' => $basicPay,
                        'effective_from' => now(),
                        'is_current' => true,
                    ]);
                }
            }
        });

        return redirect()->route('accounting.employees.show', $employee)
            ->with('success', 'Employee updated successfully.');
    }

    public function toggle(Employee $employee)
    {
        $companyId = session('current_company_id');

        if ($employee->company_id !== $companyId) {
            abort(404);
        }

        $employee->update(['is_active' => !$employee->is_active]);

        return redirect()->route('accounting.employees.index')
            ->with('success', 'Employee status updated.');
    }
}
