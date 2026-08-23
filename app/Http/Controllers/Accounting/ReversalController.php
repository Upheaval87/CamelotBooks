<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\ReversalApprovalHistory;
use App\Models\ReversalAuthorizationRequest;
use App\Models\ReversalAuthorizationRule;
use App\Models\TransactionReversal;
use App\Models\TransactionReversalRequest;
use App\Services\Accounting\TransactionReversalService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReversalController extends Controller
{
    public function __construct(
        private TransactionReversalService $service,
    ) {}

    public function create(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.request');

        $transactions = $this->service->searchTransactions(
            session('current_company_id'),
            $request->only(['date_from', 'date_to', 'type', 'branch_id', 'account_id', 'min_amount', 'max_amount', 'q'])
        );

        $transactionTypes = [
            'journal_entry' => 'Journal Entry',
            'invoice' => 'Invoice',
            'bill' => 'Bill',
            'customer_receipt' => 'Customer Receipt',
            'vendor_payment' => 'Vendor Payment',
            'sales_receipt' => 'Sales Receipt',
            'pos_sale' => 'POS Sale',
            'payroll_run' => 'Payroll Run',
        ];

        $selected = $request->query('select');
        $selectedJE = null;
        if ($selected) {
            $selectedJE = \App\Models\JournalEntry::forCompany(session('current_company_id'))
                ->with(['lines.account'])
                ->find($selected);
        }

        return view('accounting.reversals.create', compact(
            'transactions', 'transactionTypes', 'selectedJE'
        ));
    }

    public function store(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.request');

        $validated = $request->validate([
            'journal_entry_id' => 'required|integer|exists:journal_entries,id',
            'reversal_date' => 'required|date',
            'reversal_method' => 'required|in:full,partial',
            'partial_amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|min:10',
        ]);

        $requestModel = $this->service->requestReversal(
            session('current_company_id'),
            $validated['journal_entry_id'],
            Auth::id(),
            $validated
        );

        return redirect()->route('accounting.reversals.show', $requestModel->id)
            ->with('success', 'Reversal request submitted. Authorization chain initiated.');
    }

    public function index(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.view');

        $companyId = session('current_company_id');
        $stats = $this->service->getDashboardStats($companyId);

        $query = TransactionReversalRequest::forCompany($companyId)
            ->with(['journalEntry', 'requester', 'approver']);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('date_from')) {
            $query->where('request_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->where('request_date', '<=', $request->date_to);
        }

        $requests = $query->orderBy('created_at', 'desc')->paginate(15);

        return view('accounting.reversals.index', compact('requests', 'stats'));
    }

    public function show(Request $request, int $id)
    {
        $companyId = session('current_company_id');

        $requestModel = TransactionReversalRequest::forCompany($companyId)
            ->with([
                'journalEntry.lines.account',
                'requester',
                'approver',
                'authorizationRequests.assignee',
                'authorizationRequests.approver',
                'approvalHistory.performer',
                'reversal',
            ])
            ->findOrFail($id);

        return view('accounting.reversals.show', ['requestModel' => $requestModel]);
    }

    public function auth(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.view');

        $companyId = session('current_company_id');
        $userId = Auth::id();
        $stats = $this->service->getAuthDashboardStats($companyId, $userId);

        return view('accounting.reversals.auth', compact('stats'));
    }

    public function authQueue(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.view');

        $companyId = session('current_company_id');
        $userId = Auth::id();

        $queue = ReversalAuthorizationRequest::forCompany($companyId)
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->with(['request.journalEntry', 'request.requester'])
            ->orderBy('created_at', 'asc')
            ->paginate(15);

        return view('accounting.reversals.auth-queue', compact('queue'));
    }

    public function authShow(Request $request, int $id)
    {
        $companyId = session('current_company_id');

        $authorization = ReversalAuthorizationRequest::forCompany($companyId)
            ->with([
                'request.journalEntry.lines.account',
                'request.requester',
                'request.approvalHistory.performer',
            ])
            ->findOrFail($id);

        return view('accounting.reversals.auth-show', ['authorization' => $authorization]);
    }

    public function authApprove(Request $request, int $id)
    {
        $this->requirePermission($request, 'transaction-reversals.approve');

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        $companyId = session('current_company_id');
        $authorization = ReversalAuthorizationRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $this->service->approve(
            $authorization->reversal_request_id,
            Auth::id(),
            $request->comments
        );

        return redirect()->route('accounting.reversals.auth.queue')
            ->with('success', 'Reversal approved and posted to the general ledger.');
    }

    public function authReject(Request $request, int $id)
    {
        $this->requirePermission($request, 'transaction-reversals.reject');

        $request->validate([
            'reason' => 'required|string|min:10',
        ]);

        $companyId = session('current_company_id');
        $authorization = ReversalAuthorizationRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $this->service->reject(
            $authorization->reversal_request_id,
            Auth::id(),
            $request->reason
        );

        return redirect()->route('accounting.reversals.auth.queue')
            ->with('success', 'Reversal request rejected.');
    }

    public function authClarify(Request $request, int $id)
    {
        $this->requirePermission($request, 'transaction-reversals.clarify');

        $request->validate([
            'message' => 'required|string|min:10',
        ]);

        $companyId = session('current_company_id');
        $authorization = ReversalAuthorizationRequest::where('id', $id)
            ->where('company_id', $companyId)
            ->firstOrFail();

        $this->service->requestClarification(
            $authorization->reversal_request_id,
            Auth::id(),
            $request->message
        );

        return redirect()->route('accounting.reversals.show', $authorization->reversal_request_id)
            ->with('success', 'Clarification requested from the submitter.');
    }

    public function rules(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.configure_rules');

        $companyId = session('current_company_id');
        $stats = $this->service->getRulesStats($companyId);
        $rules = ReversalAuthorizationRule::forCompany($companyId)
            ->with('branch')
            ->orderBy('minimum_amount')
            ->get();

        $roles = [
            'accountant' => 'Accountant',
            'accounts_clerk' => 'Accounts Clerk',
            'finance_manager' => 'Finance Manager',
            'company_admin' => 'Company Admin',
            'administrator' => 'Administrator',
            'senior_approver' => 'Senior Approver',
        ];

        return view('accounting.reversals.rules', compact('rules', 'stats', 'roles'));
    }

    public function rulesStore(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.configure_rules');

        $validated = $request->validate([
            'transaction_type' => 'nullable|string',
            'minimum_amount' => 'required|numeric|min:0',
            'maximum_amount' => 'nullable|numeric|gte:minimum_amount',
            'required_approvals' => 'required|integer|min:1|max:10',
            'approver_role' => 'required|string',
            'branch_id' => 'nullable|integer|exists:branches,id',
        ]);

        $this->service->storeRule(session('current_company_id'), $validated);

        return redirect()->route('accounting.reversals.rules')
            ->with('success', 'Authorization rule created.');
    }

    public function rulesToggle(Request $request, int $ruleId)
    {
        $this->requirePermission($request, 'transaction-reversals.configure_rules');

        $this->service->toggleRule($ruleId, session('current_company_id'));

        return redirect()->route('accounting.reversals.rules')
            ->with('success', 'Rule toggled.');
    }

    public function rulesDelete(Request $request, int $ruleId)
    {
        $this->requirePermission($request, 'transaction-reversals.configure_rules');

        $this->service->deleteRule($ruleId, session('current_company_id'));

        return redirect()->route('accounting.reversals.rules')
            ->with('success', 'Rule deleted.');
    }

    public function audit(Request $request)
    {
        $this->requirePermission($request, 'transaction-reversals.view_audit');

        $companyId = session('current_company_id');

        $audit = ReversalApprovalHistory::forCompany($companyId)
            ->with(['request', 'performer'])
            ->orderBy('date_time', 'desc')
            ->paginate(20);

        return view('accounting.reversals.audit', compact('audit'));
    }
}
