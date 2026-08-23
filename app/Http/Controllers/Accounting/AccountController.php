<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Http\Requests\Accounting\StoreAccountRequest;
use App\Http\Requests\Accounting\UpdateAccountRequest;
use App\Models\Account;
use App\Services\Accounting\CoaService;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $companyId = session('current_company_id');
        $service = new CoaService($companyId);

        $treeData = $service->buildTree();
        $equation = $service->computeEquation();
        $currentView = $service->getViewPreference();
        $systemCurrency = $treeData['system_currency'];
        $stats = $treeData['stats'];

        return view('accounting.accounts.index', array_merge($treeData, compact(
            'equation', 'currentView', 'systemCurrency', 'stats'
        )));
    }

    public function tree(Request $request)
    {
        $companyId = session('current_company_id');
        $service = new CoaService($companyId);
        return response()->json($service->buildTree());
    }

    public function preference(Request $request)
    {
        $request->validate(['view' => 'required|in:tree,list']);
        $companyId = session('current_company_id');
        $service = new CoaService($companyId);
        $service->setViewPreference($request->view);
        return response()->json(['ok' => true, 'view' => $request->view]);
    }

    public function deactivate(Request $request, Account $account)
    {
        abort_unless($account->company_id == session('current_company_id'), 404);

        $request->validate(['reason' => 'required|string|min:3|max:500']);

        $companyId = session('current_company_id');
        $service = new CoaService($companyId);
        $service->deactivateAccount($account, $request->reason);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => 'inactive']);
        }

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account deactivated successfully.');
    }

    public function reactivate(Request $request, Account $account)
    {
        abort_unless($account->company_id == session('current_company_id'), 404);

        $companyId = session('current_company_id');
        $service = new CoaService($companyId);
        $service->reactivateAccount($account);

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'status' => 'active']);
        }

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account reactivated successfully.');
    }

    public function create()
    {
        $companyId = session('current_company_id');
        $parentAccounts = Account::where('company_id', $companyId)
            ->active()
            ->whereNull('parent_id')
            ->orderBy('code')
            ->get();
        return view('accounting.accounts.create', compact('parentAccounts'));
    }

    public function store(StoreAccountRequest $request)
    {
        $companyId = session('current_company_id');
        $validated = $request->validated();
        $service = new CoaService($companyId);
        $service->createAccount($validated);
        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account created successfully.');
    }

    public function show(Account $account)
    {
        $account->load('parent', 'children');
        return view('accounting.accounts.show', compact('account'));
    }

    public function edit(Account $account)
    {
        $companyId = session('current_company_id');
        $parentAccounts = Account::where('company_id', $companyId)
            ->active()
            ->whereNull('parent_id')
            ->where('id', '!=', $account->id)
            ->orderBy('code')
            ->get();
        return view('accounting.accounts.edit', compact('account', 'parentAccounts'));
    }

    public function update(UpdateAccountRequest $request, Account $account)
    {
        abort_unless($account->company_id == session('current_company_id'), 404);

        $companyId = session('current_company_id');
        $validated = $request->validated();
        $service = new CoaService($companyId);
        $service->updateAccount($account, $validated);

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account updated successfully.');
    }

    public function toggle(Account $account)
    {
        abort_unless($account->company_id == session('current_company_id'), 404);

        if ($account->is_active) {
            $companyId = session('current_company_id');
            $service = new CoaService($companyId);
            $service->deactivateAccount($account, 'Toggled via quick action');
        } else {
            $companyId = session('current_company_id');
            $service = new CoaService($companyId);
            $service->reactivateAccount($account);
        }

        return redirect()->route('accounting.accounts.index')
            ->with('success', 'Account status updated.');
    }
}