<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\DefaultAccountMapping;
use App\Models\JournalEntry;
use App\Models\JournalEntryLine;
use App\Models\TaxAdjustment;
use App\Models\TaxAuditTrail;
use App\Models\TaxCode;
use App\Models\TaxCodeRate;
use App\Models\TaxExemption;
use App\Models\TaxJurisdiction;
use App\Models\TaxObligation;
use App\Models\TaxPayment;
use App\Models\TaxPeriod;
use App\Models\TaxRecognitionRule;
use App\Models\TaxReturn;
use App\Models\TaxTransaction;
use App\Models\TaxType;
use App\Models\WhtCertificate;
use App\Services\FeatureManagement;
use App\Services\Tax\TaxObligationService;
use App\Services\Tax\TaxPaymentService;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class TaxController extends Controller
{
    private const OPEN_ENDED_DATE = '9999-12-31';

    public function __construct(private TaxPaymentService $paymentService)
    {
    }

    public function dashboard(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $sides = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->posted()
            ->selectRaw('side, SUM(tax_amount) AS tax_total')
            ->groupBy('side')
            ->pluck('tax_total', 'side');

        $outputTax = round((float) ($sides['OUTPUT'] ?? 0), 2);
        $inputTax = round((float) ($sides['INPUT'] ?? 0), 2);
        $adjustmentsNet = $this->approvedAdjustmentsNet($companyId);

        $paid = round(
            (float) TaxPayment::query()
                ->where('company_id', $companyId)
                ->whereIn(DB::raw('LOWER(status)'), ['confirmed', 'paid'])
                ->sum('amount'),
            2
        );

        $netPayable = round($outputTax - $inputTax + $adjustmentsNet, 2);

        $openPeriods = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->where('status', 'OPEN')
            ->count();

        $unfiledQuery = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->where('end_date', '<', today())
            ->whereIn('status', ['OPEN', 'IN_PREPARATION']);
        $unfiledCount = (clone $unfiledQuery)->count();

        $upcomingDeadlines = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->with('taxType:id,code,name')
            ->whereNotNull('filing_due_date')
            ->whereIn('status', ['OPEN', 'IN_PREPARATION'])
            ->where('filing_due_date', '>=', today())
            ->orderBy('filing_due_date')
            ->limit(5)
            ->get()
            ->map(fn (TaxPeriod $period) => [
                'period_id' => $period->id,
                'label' => $period->label,
                'tax_type_code' => $period->taxType?->code,
                'tax_type_name' => $period->taxType?->name,
                'filing_due_date' => optional($period->filing_due_date)->toDateString(),
                'days_left' => (int) today()->diffInDays($period->filing_due_date),
            ])
            ->all();

        $kpi = [
            'output_tax' => $outputTax,
            'input_tax' => $inputTax,
            'net_payable' => $netPayable,
            'adjustments' => $adjustmentsNet,
            'paid' => $paid,
            'outstanding' => round($netPayable - $paid, 2),
            'open_periods' => $openPeriods,
            'unfiled_periods' => $unfiledCount,
            'upcoming_deadlines' => $upcomingDeadlines,
        ];

        $periods = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->with('taxType:id,code,name')
            ->orderByDesc('start_date')
            ->limit(12)
            ->get();
        $this->attachPeriodStats($companyId, $periods);

        $exceptions = [];

        $rateRows = TaxCodeRate::query()
            ->whereHas('taxCode', fn ($query) => $query->where('company_id', $companyId))
            ->with('taxCode:id,code,name')
            ->get(['id', 'tax_code_id', 'rate_pct', 'effective_from', 'effective_to']);

        foreach ($rateRows->groupBy('tax_code_id') as $taxCodeId => $groupedRates) {
            $sortedRates = $groupedRates->sortBy('effective_from')->values();

            foreach ($sortedRates as $index => $rate) {
                foreach ($sortedRates->slice($index + 1) as $otherRate) {
                    if (! $this->windowsOverlap($rate, $otherRate)) {
                        continue;
                    }

                    $exceptions[] = [
                        'kind' => 'rate_overlap',
                        'severity' => 'error',
                        'message' => sprintf(
                            'Tax code %s has overlapping rate windows starting %s and %s.',
                            $rate->taxCode?->code ?? ('#' . $taxCodeId),
                            optional($rate->effective_from)->toDateString(),
                            optional($otherRate->effective_from)->toDateString(),
                        ),
                        'link' => route('accounting.taxation.rates'),
                    ];
                }
            }
        }

        foreach ($unfiledQuery->with('taxType:id,code,name')->orderBy('end_date')->limit(10)->get() as $unfiled) {
            $exceptions[] = [
                'kind' => 'return_unfiled',
                'severity' => 'warning',
                'message' => sprintf(
                    '%s period %s ended %s and has not been filed.',
                    $unfiled->taxType?->name ?? 'Tax',
                    $unfiled->label,
                    optional($unfiled->end_date)->toDateString(),
                ),
                'link' => route('accounting.taxation.returns.working-paper', ['periodId' => $unfiled->id]),
            ];
        }

        $unpostedCount = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->where('status', 'UNPOSTED')
            ->count();

        if ($unpostedCount > 0) {
            $exceptions[] = [
                'kind' => 'unposted_transactions',
                'severity' => 'info',
                'message' => sprintf('%d tax transaction(s) are still unposted.', $unpostedCount),
                'link' => route('accounting.taxation.reconciliation'),
            ];
        }

        return view('accounting.taxation.dashboard', compact('kpi', 'periods', 'exceptions') + ['cs' => $this->cs()]);
    }

    public function codes(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $codes = TaxCode::query()
            ->where('company_id', $companyId)
            ->with([
                'taxType:id,code,name',
                'jurisdiction:id,code,name',
                'rates' => fn ($query) => $query->orderByDesc('effective_from'),
            ])
            ->orderBy('code')
            ->get();

        $taxTypes = TaxType::query()->where('company_id', $companyId)->orderBy('code')->get(['id', 'code', 'name']);
        $jurisdictions = TaxJurisdiction::query()->where('active', true)->orderBy('name')->get();
        $glAccounts = Account::query()->where('company_id', $companyId)->where('is_active', true)->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.taxation.codes', compact('codes', 'taxTypes', 'jurisdictions', 'glAccounts') + ['cs' => $this->cs()]);
    }

    public function types(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');

        $types = TaxType::query()
            ->where('company_id', $this->companyId())
            ->withCount('taxCodes')
            ->orderBy('code')
            ->get();

        $taxTypes = TaxType::query()->where('company_id', $this->companyId())->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.taxation.types', compact('types', 'taxTypes') + ['cs' => $this->cs()]);
    }

    public function rates(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');

        $rates = TaxCodeRate::query()
            ->whereHas('taxCode', fn ($query) => $query->where('company_id', $this->companyId()))
            ->with('taxCode:id,code,name,tax_type_id,treatment')
            ->orderByDesc('effective_from')
            ->orderBy('tax_code_id')
            ->get();

        $taxCodes = TaxCode::query()->where('company_id', $this->companyId())->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.taxation.rates', compact('rates', 'taxCodes') + ['cs' => $this->cs()]);
    }

    public function exemptions(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');

        $exemptions = TaxExemption::query()
            ->where('company_id', $this->companyId())
            ->with('taxType:id,code,name')
            ->orderBy('code')
            ->get();

        $taxTypes = TaxType::query()->where('company_id', $this->companyId())->orderBy('code')->get(['id', 'code', 'name']);

        return view('accounting.taxation.exemptions', compact('exemptions', 'taxTypes') + ['cs' => $this->cs()]);
    }

    public function jurisdictions(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');

        $jurisdictions = TaxJurisdiction::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        return view('accounting.taxation.jurisdictions', compact('jurisdictions') + ['cs' => $this->cs()]);
    }

    public function storeType(Request $request)
    {
        $this->requirePermission($request, 'taxation.create');
        $companyId = $this->companyId();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'category' => ['required', 'in:VAT,WHT,PAYE,FBT,CORPORATE,PRESUMPTIVE,OTHER'],
            'active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($companyId, $data, $request) {
            $type = TaxType::create([
                'company_id' => $companyId,
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'category' => $data['category'],
                'active' => (bool) ($data['active'] ?? true),
            ]);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'tax_type',
                $type->id,
                'status',
                null,
                $type->active ? 'ACTIVE' : 'INACTIVE',
                'Tax type created.'
            );
        });

        return redirect(route('accounting.taxation.config', ['tab' => 'types']))->with('success', __('Tax type created.'));
    }

    public function storeCode(Request $request)
    {
        $this->requirePermission($request, 'taxation.create');
        $companyId = $this->companyId();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'tax_type_id' => ['required', 'integer'],
            'jurisdiction_id' => ['nullable', 'integer'],
            'treatment' => ['required', 'in:STANDARD,ZERO_RATED,EXEMPT,DEDUCTED,CHARGED,REVERSE_CHARGE'],
            'price_basis' => ['required', 'in:EXCLUSIVE,INCLUSIVE'],
            'rounding_mode' => ['required', 'in:HALF_UP,HALF_DOWN,HALF_EVEN'],
            'rounding_level' => ['required', 'in:LINE,DOCUMENT'],
            'gl_output_acct' => ['nullable', 'integer'],
            'gl_input_acct' => ['nullable', 'integer'],
            'gl_payable_acct' => ['nullable', 'integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'active' => ['nullable', 'boolean'],
        ]);

        $taxType = TaxType::find($data['tax_type_id']);
        abort_unless($taxType !== null && (int) $taxType->company_id === $companyId, 404);

        if (! empty($data['jurisdiction_id'])) {
            $jurisdiction = TaxJurisdiction::find($data['jurisdiction_id']);
            abort_unless($jurisdiction !== null && (int) $jurisdiction->company_id === $companyId, 404);
        }

        foreach (['gl_output_acct', 'gl_input_acct', 'gl_payable_acct'] as $glKey) {
            if (! empty($data[$glKey])) {
                $account = Account::find($data[$glKey]);
                abort_unless($account !== null && (int) $account->company_id === $companyId, 404);
            }
        }

        DB::transaction(function () use ($companyId, $data, $request) {
            $code = TaxCode::create([
                'company_id' => $companyId,
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'tax_type_id' => $data['tax_type_id'],
                'jurisdiction_id' => $data['jurisdiction_id'] ?? null,
                'treatment' => $data['treatment'],
                'price_basis' => $data['price_basis'],
                'rounding_mode' => $data['rounding_mode'],
                'rounding_level' => $data['rounding_level'],
                'gl_output_acct' => $data['gl_output_acct'] ?? null,
                'gl_input_acct' => $data['gl_input_acct'] ?? null,
                'gl_payable_acct' => $data['gl_payable_acct'] ?? null,
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'active' => (bool) ($data['active'] ?? true),
            ]);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'tax_code',
                $code->id,
                'status',
                null,
                $code->active ? 'ACTIVE' : 'INACTIVE',
                'Tax code created.'
            );
        });

        return redirect(route('accounting.taxation.config', ['tab' => 'codes']))->with('success', __('Tax code created.'));
    }

    public function storeRate(Request $request)
    {
        $this->requirePermission($request, 'taxation.create');
        $companyId = $this->companyId();

        $data = $request->validate([
            'tax_code_id' => ['required', 'integer'],
            'rate_pct' => ['required', 'numeric', 'min:0', 'max:100'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
        ]);

        $taxCode = TaxCode::find($data['tax_code_id']);
        abort_unless($taxCode !== null && (int) $taxCode->company_id === $companyId, 404);

        DB::transaction(function () use ($companyId, $data, $request) {
            $rate = TaxCodeRate::create([
                'tax_code_id' => $data['tax_code_id'],
                'rate_pct' => $data['rate_pct'],
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
            ]);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'tax_rate',
                $rate->id,
                'rate_pct',
                null,
                $rate->rate_pct,
                'Tax rate created.'
            );
        });

        return redirect(route('accounting.taxation.config', ['tab' => 'rates']))->with('success', __('Tax rate created.'));
    }

    public function storeExemption(Request $request)
    {
        $this->requirePermission($request, 'taxation.create');
        $companyId = $this->companyId();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'scope' => ['required', 'in:SALES,PURCHASES,BOTH'],
            'tax_type_id' => ['required', 'integer'],
            'effective_from' => ['required', 'date'],
            'effective_to' => ['nullable', 'date', 'after_or_equal:effective_from'],
            'active' => ['nullable', 'boolean'],
        ]);

        $taxType = TaxType::find($data['tax_type_id']);
        abort_unless($taxType !== null && (int) $taxType->company_id === $companyId, 404);

        DB::transaction(function () use ($companyId, $data, $request) {
            $exemption = TaxExemption::create([
                'company_id' => $companyId,
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'reason' => $data['reason'] ?? '',
                'scope' => $data['scope'],
                'tax_type_id' => $data['tax_type_id'],
                'effective_from' => $data['effective_from'],
                'effective_to' => $data['effective_to'] ?? null,
                'active' => (bool) ($data['active'] ?? true),
            ]);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'tax_exemption',
                $exemption->id,
                'status',
                null,
                $exemption->active ? 'ACTIVE' : 'INACTIVE',
                'Tax exemption created.'
            );
        });

        return redirect(route('accounting.taxation.config', ['tab' => 'exemptions']))->with('success', __('Tax exemption created.'));
    }

    public function storeJurisdiction(Request $request)
    {
        $this->requirePermission($request, 'taxation.create');
        $companyId = $this->companyId();

        $data = $request->validate([
            'code' => ['required', 'string', 'max:30'],
            'name' => ['required', 'string', 'max:255'],
            'country' => ['required', 'string', 'max:100'],
            'authority' => ['required', 'string', 'max:200'],
            'active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($companyId, $data, $request) {
            $jurisdiction = TaxJurisdiction::create([
                'company_id' => $companyId,
                'code' => strtoupper($data['code']),
                'name' => $data['name'],
                'country' => $data['country'],
                'authority' => $data['authority'],
                'active' => (bool) ($data['active'] ?? true),
            ]);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'tax_jurisdiction',
                $jurisdiction->id,
                'status',
                null,
                $jurisdiction->active ? 'ACTIVE' : 'INACTIVE',
                'Tax jurisdiction created.'
            );
        });

        return redirect(route('accounting.taxation.config', ['tab' => 'jurisdictions']))->with('success', __('Tax jurisdiction created.'));
    }

    public function accounts(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $labels = DefaultAccountMapping::availableKeys();
        $mappings = collect(DefaultAccountMapping::getAll($companyId))
            ->except(['tax_payable', 'tax_receivable']);

        $accountIds = $mappings->filter()->unique()->values()->all();
        $accountsById = $accountIds === []
            ? collect()
            : Account::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $accountIds)
                ->get()
                ->keyBy('id');

        $otherMappings = $mappings
            ->map(fn ($accountId, $key) => [
                'key' => $key,
                'label' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                'account' => $accountsById->get($accountId),
            ])
            ->sortBy('label')
            ->values();

        return view('accounting.taxation.accounts', [
            'taxPayableAccount' => DefaultAccountMapping::getAccount($companyId, 'tax_payable'),
            'taxReceivableAccount' => DefaultAccountMapping::getAccount($companyId, 'tax_receivable'),
            'otherMappings' => $otherMappings,
            'cs' => $this->cs(),
        ]);
    }

    public function config(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();
        $cs = $this->cs();

        $activeTab = $request->query('tab', 'types');
        $validTabs = ['types', 'rates', 'codes', 'exemptions', 'jurisdictions', 'accounts'];
        if (! in_array($activeTab, $validTabs)) {
            $activeTab = 'types';
        }

        // Types
        $types = TaxType::query()
            ->where('company_id', $companyId)
            ->withCount('taxCodes')
            ->orderBy('code')
            ->get();

        // Rates
        $rates = TaxCodeRate::query()
            ->whereHas('taxCode', fn ($query) => $query->where('company_id', $companyId))
            ->with('taxCode:id,code,name,tax_type_id,treatment')
            ->orderByDesc('effective_from')
            ->orderBy('tax_code_id')
            ->get();

        // Codes
        $codes = TaxCode::query()
            ->where('company_id', $companyId)
            ->with([
                'taxType:id,code,name',
                'jurisdiction:id,code,name',
                'rates' => fn ($query) => $query->orderByDesc('effective_from'),
            ])
            ->orderBy('code')
            ->get();

        // Exemptions
        $exemptions = TaxExemption::query()
            ->where('company_id', $companyId)
            ->with('taxType:id,code,name')
            ->orderBy('code')
            ->get();

        // Jurisdictions
        $jurisdictions = TaxJurisdiction::query()
            ->where('active', true)
            ->orderBy('name')
            ->get();

        // Accounts
        $labels = DefaultAccountMapping::availableKeys();
        $mappings = collect(DefaultAccountMapping::getAll($companyId))
            ->except(['tax_payable', 'tax_receivable']);

        $accountIds = $mappings->filter()->unique()->values()->all();
        $accountsById = $accountIds === []
            ? collect()
            : Account::query()
                ->where('company_id', $companyId)
                ->whereIn('id', $accountIds)
                ->get()
                ->keyBy('id');

        $otherMappings = $mappings
            ->map(fn ($accountId, $key) => [
                'key' => $key,
                'label' => $labels[$key] ?? ucwords(str_replace('_', ' ', $key)),
                'account' => $accountsById->get($accountId),
            ])
            ->sortBy('label')
            ->values();

        $taxPayableAccount = DefaultAccountMapping::getAccount($companyId, 'tax_payable');
        $taxReceivableAccount = DefaultAccountMapping::getAccount($companyId, 'tax_receivable');

        // Lightweight option lists for the inline create forms.
        $taxTypes = TaxType::query()
            ->where('company_id', $companyId)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $taxCodes = TaxCode::query()
            ->where('company_id', $companyId)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
        $glAccounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('accounting.taxation.config', compact(
            'activeTab', 'types', 'rates', 'codes', 'exemptions',
            'jurisdictions', 'otherMappings', 'taxPayableAccount', 'taxReceivableAccount',
            'taxTypes', 'taxCodes', 'glAccounts', 'cs'
        ));
    }

    public function periods(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');

        $periods = TaxPeriod::query()
            ->where('company_id', $this->companyId())
            ->with('taxType:id,code,name')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        $this->attachPeriodStats($this->companyId(), $periods->getCollection());

        return view('accounting.taxation.periods', compact('periods') + ['cs' => $this->cs()]);
    }

    public function returnWorkingPaper(Request $request, int $periodId)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $period = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->with('taxType:id,code,name')
            ->find($periodId);
        abort_unless($period !== null, 404);

        $transactions = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->forPeriod($period->id)
            ->posted()
            ->with([
                'taxCode:id,code,name,treatment,tax_type_id',
                'exemption:id,code,name',
                'jurisdiction:id,code,name',
            ])
            ->orderBy('tax_code_id')
            ->orderBy('side')
            ->orderBy('id')
            ->get();

        $rows = $transactions
            ->groupBy(fn (TaxTransaction $txn) => ($txn->tax_code_id ?? 0) . '|' . $txn->side)
            ->map(function ($group) {
                $first = $group->first();
                $ratePct = (float) ($first->rate_pct ?? 0);

                $expected = round(
                    $group->sum(fn (TaxTransaction $txn) => round(((float) $txn->base_amount) * $ratePct / 100, 2)),
                    2
                );
                $calculated = round((float) $group->sum('tax_amount'), 2);

                return [
                    'tax_code_id' => $first->tax_code_id,
                    'code' => $first->taxCode?->code,
                    'code_name' => $first->taxCode?->name,
                    'treatment' => $first->taxCode?->treatment,
                    'side' => $first->side,
                    'rate_pct' => $ratePct,
                    'base_amount' => round((float) $group->sum('base_amount'), 2),
                    'expected_tax' => $expected,
                    'calculated_tax' => $calculated,
                    'variance' => round($calculated - $expected, 2),
                    'transaction_count' => $group->count(),
                ];
            })
            ->values();

        $summary = $this->workingPaperSummary($companyId, $period, $transactions, $rows);

        return view('accounting.taxation.working-paper', [
            'period' => $period,
            'transactions' => $rows,
            'summary' => $summary,
            'cs' => $this->cs(),
        ]);
    }

    public function reconciliation(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $periods = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->with('taxType:id,code,name')
            ->orderByDesc('start_date')
            ->limit(12)
            ->get();

        if ($periods->isEmpty()) {
            return view('accounting.taxation.reconciliation', ['periods' => $periods, 'rows' => collect(), 'cs' => $this->cs()]);
        }

        $periodIds = $periods->pluck('id')->all();

        $aggregates = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->posted()
            ->whereIn('period_id', $periodIds)
            ->selectRaw('period_id, side, '
                . 'SUM(ROUND(base_amount * rate_pct / 100, 2)) AS expected_tax, '
                . 'SUM(tax_amount) AS calculated_tax')
            ->groupBy('period_id', 'side')
            ->get()
            ->groupBy('period_id');

        $returnsByPeriod = TaxReturn::query()
            ->where('company_id', $companyId)
            ->whereIn('period_id', $periodIds)
            ->orderByDesc('id')
            ->get()
            ->groupBy('period_id')
            ->map(fn ($groupedReturns) => $groupedReturns->first());

        $payableId = DefaultAccountMapping::getAccountId($companyId, 'tax_payable');
        $receivableId = DefaultAccountMapping::getAccountId($companyId, 'tax_receivable');

        $movementsByDate = collect();
        if ($payableId !== null || $receivableId !== null) {
            if ($payableId !== null && $receivableId !== null) {
                $movementSql = 'CASE WHEN journal_entry_lines.account_id = ' . ((int) $payableId)
                    . ' THEN journal_entry_lines.credit - journal_entry_lines.debit'
                    . ' ELSE journal_entry_lines.debit - journal_entry_lines.credit END';
            } elseif ($payableId !== null) {
                $movementSql = 'journal_entry_lines.credit - journal_entry_lines.debit';
            } else {
                $movementSql = 'journal_entry_lines.debit - journal_entry_lines.credit';
            }

            $lineQuery = JournalEntryLine::query()
                ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
                ->where('journal_entries.company_id', $companyId)
                ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
                ->whereBetween('journal_entries.date', [$periods->min('start_date'), $periods->max('end_date')]);

            $lineQuery = $payableId !== null && $receivableId !== null
                ? $lineQuery->whereIn('journal_entry_lines.account_id', [$payableId, $receivableId])
                : $lineQuery->where('journal_entry_lines.account_id', $payableId ?? $receivableId);

            $movementsByDate = $lineQuery
                ->selectRaw('journal_entries.date AS entry_date, '
                    . 'COALESCE(SUM(' . $movementSql . '), 0) AS net_movement')
                ->groupBy('journal_entries.date')
                ->get()
                ->pluck('net_movement', 'entry_date');
        }

        $normalizeDate = fn ($value) => $value instanceof Carbon ? $value->toDateString() : (string) $value;

        $movementWithin = function ($from, $to) use ($movementsByDate, $normalizeDate) {
            if ($movementsByDate->isEmpty()) {
                return 0.0;
            }

            $from = $normalizeDate($from);
            $to = $normalizeDate($to);

            $total = 0.0;
            foreach ($movementsByDate as $entryDate => $netMovement) {
                if ($entryDate >= $from && $entryDate <= $to) {
                    $total += (float) $netMovement;
                }
            }

            return $total;
        };

        $rows = [];

        foreach ($periods as $period) {
            $periodAggregates = $aggregates->get($period->id, collect());
            $filedReturn = $returnsByPeriod->get($period->id);
            $payableMovement = $movementWithin($period->start_date, $period->end_date);

            $sideLabels = [
                'OUTPUT' => 'Output VAT',
                'INPUT' => 'Input VAT',
                'WHT' => $period->taxType?->name ?: 'Withholding',
            ];

            foreach (['OUTPUT', 'INPUT', 'WHT'] as $side) {
                $aggregate = $periodAggregates->firstWhere('side', $side);
                $expected = round((float) ($aggregate->expected_tax ?? 0), 2);
                $calculated = round((float) ($aggregate->calculated_tax ?? 0), 2);

                $posted = match ($side) {
                    'OUTPUT' => round($payableMovement, 2),
                    'INPUT' => round(-$payableMovement, 2),
                    default => $calculated,
                };

                $reported = null;
                if ($filedReturn !== null) {
                    $reported = match ($side) {
                        'OUTPUT' => (float) $filedReturn->output_tax,
                        'INPUT' => (float) $filedReturn->input_tax,
                        default => null,
                    };
                }

                if ($aggregate === null && $reported === null && $posted == 0.0) {
                    continue;
                }

                $rows[] = [
                    'period_id' => $period->id,
                    'period_label' => $period->label,
                    'tax_type' => $period->taxType?->name,
                    'side' => $side,
                    'display_label' => $sideLabels[$side] . ' · ' . $period->label,
                    'expected' => $expected,
                    'calculated' => $calculated,
                    'posted' => $posted,
                    'variance' => round($calculated - $posted, 2),
                    'reported' => $reported !== null ? round($reported, 2) : null,
                    'report_variance' => $reported !== null ? round($reported - $calculated, 2) : null,
                    'working_paper_url' => route('accounting.taxation.returns.working-paper', ['periodId' => $period->id]),
                ];
            }
        }

        return view('accounting.taxation.reconciliation', ['periods' => $periods, 'rows' => collect($rows), 'cs' => $this->cs()]);
    }

    public function certificates(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');

        $certificates = WhtCertificate::query()
            ->where('company_id', $this->companyId())
            ->with(['supplier:id,code,name', 'taxCode:id,code,name', 'period:id,label,start_date,end_date'])
            ->orderByDesc('issued_date')
            ->orderByDesc('id')
            ->get();

        return view('accounting.taxation.certificates', compact('certificates') + ['cs' => $this->cs()]);
    }

    public function reports(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        // #18: the report list is driven from ReportRegistry (permissions + feature
        // flags + route availability all resolved centrally).
        $grouped = \App\Services\Reporting\ReportRegistry::getAccessibleGrouped($request->user(), $companyId);

        $reports = [];

        foreach ($grouped as $cat) {
            foreach ($cat['reports'] as $report) {
                if (! Route::has($report['route'])) {
                    continue;
                }

                $reports[] = [
                    'key'         => $report['key'],
                    'name'        => $report['name'],
                    'description' => $report['description'],
                    'url'         => route($report['route']),
                ];
            }
        }

        return view('accounting.taxation.reports', ['reports' => collect($reports), 'cs' => $this->cs()]);
    }

    public function auditTrail(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');

        $filters = [
            'entity_kind' => trim((string) $request->query('entity_kind', '')),
            'from' => $request->query('from'),
            'to' => $request->query('to'),
        ];

        $query = TaxAuditTrail::query()
            ->where('company_id', $this->companyId())
            ->with('user:id,name');

        if ($filters['entity_kind'] !== '') {
            $query->where('entity_kind', $filters['entity_kind']);
        }

        if (! empty($filters['from'])) {
            $query->whereDate('acted_at', '>=', $filters['from']);
        }

        if (! empty($filters['to'])) {
            $query->whereDate('acted_at', '<=', $filters['to']);
        }

        $logs = $query
            ->orderByDesc('acted_at')
            ->orderByDesc('id')
            ->paginate(25)
            ->appends($request->query());

        return view('accounting.taxation.audit-trail', compact('logs', 'filters') + ['cs' => $this->cs()]);
    }

    public function currentPosition(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $typeRows = TaxTransaction::query()
            ->join('tax_codes', 'tax_codes.id', '=', 'tax_transactions.tax_code_id')
            ->join('tax_types', 'tax_types.id', '=', 'tax_codes.tax_type_id')
            ->where('tax_transactions.company_id', $companyId)
            ->where('tax_transactions.status', 'POSTED')
            ->selectRaw('tax_types.id AS tax_type_id, tax_types.code AS type_code, tax_types.name AS type_name, '
                . "COALESCE(SUM(CASE WHEN tax_transactions.side = 'OUTPUT' THEN tax_transactions.tax_amount ELSE 0 END), 0) AS collected, "
                . "COALESCE(SUM(CASE WHEN tax_transactions.side <> 'OUTPUT' THEN COALESCE(tax_transactions.recoverable_tax_amount, tax_transactions.tax_amount) ELSE 0 END), 0) AS recoverable")
            ->groupBy('tax_types.id', 'tax_types.code', 'tax_types.name')
            ->orderBy('tax_types.code')
            ->get();

        $adjustmentsByType = TaxAdjustment::query()
            ->approved()
            ->where('company_id', $companyId)
            ->selectRaw("tax_type_id, COALESCE(SUM(CASE WHEN direction = 'ADD' THEN amount ELSE -amount END), 0) AS net_adjustments")
            ->groupBy('tax_type_id')
            ->pluck('net_adjustments', 'tax_type_id');

        $paidByType = TaxPayment::query()
            ->where('company_id', $companyId)
            ->whereIn(DB::raw('LOWER(status)'), ['confirmed', 'paid'])
            ->selectRaw('tax_type_id, SUM(amount) AS paid_total')
            ->groupBy('tax_type_id')
            ->pluck('paid_total', 'tax_type_id');

        $positions = [];
        $totals = [
            'collected' => 0.0,
            'recoverable' => 0.0,
            'adjustments' => 0.0,
            'paid' => 0.0,
            'outstanding' => 0.0,
        ];

        foreach ($typeRows as $row) {
            $collected = round((float) $row->collected, 2);
            $recoverable = round((float) $row->recoverable, 2);
            $adjustments = round((float) ($adjustmentsByType[$row->tax_type_id] ?? 0), 2);
            $paid = round((float) ($paidByType[$row->tax_type_id] ?? 0), 2);
            $outstanding = round($collected - $recoverable + $adjustments - $paid, 2);

            $positions[] = [
                'tax_type_id' => $row->tax_type_id,
                'type_code' => $row->type_code,
                'type_name' => $row->type_name,
                'collected' => $collected,
                'recoverable' => $recoverable,
                'adjustments' => $adjustments,
                'paid' => $paid,
                'outstanding' => $outstanding,
            ];

            $totals['collected'] += $collected;
            $totals['recoverable'] += $recoverable;
            $totals['adjustments'] += $adjustments;
            $totals['paid'] += $paid;
            $totals['outstanding'] += $outstanding;
        }

        $totals = array_map(fn ($value) => round($value, 2), $totals);

        return view('accounting.taxation.position', compact('positions', 'totals') + ['cs' => $this->cs()]);
    }

    public function controlAccount(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $account = DefaultAccountMapping::getAccount($companyId, 'tax_payable');

        if ($account === null) {
            return view('accounting.taxation.control-account', [
                'account' => null,
                'lines' => collect(),
                'openingBalance' => 0.0,
                'runningBalance' => 0.0,
                'totalDebit' => 0.0,
                'totalCredit' => 0.0,
                'cs' => $this->cs(),
            ]);
        }

        $lines = JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $account->id)
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->orderBy('journal_entries.date')
            ->orderBy('journal_entries.id')
            ->orderBy('journal_entry_lines.id')
            ->select('journal_entry_lines.*')
            ->get();

        $lines->load('journalEntry:id,journal_number,date,status,source_module');

        $isDebitNormal = $account->isDebitNormal();
        $running = (float) $account->opening_balance;
        $totalDebit = 0.0;
        $totalCredit = 0.0;

        foreach ($lines as $line) {
            $debit = (float) $line->debit;
            $credit = (float) $line->credit;
            $totalDebit += $debit;
            $totalCredit += $credit;
            $running += $isDebitNormal ? $debit - $credit : $credit - $debit;
            $line->balance_after = round($running, 2);
        }

        return view('accounting.taxation.control-account', [
            'account' => $account,
            'lines' => $lines,
            'openingBalance' => round((float) $account->opening_balance, 2),
            'runningBalance' => round($running, 2),
            'totalDebit' => round($totalDebit, 2),
            'totalCredit' => round($totalCredit, 2),
            'cs' => $this->cs(),
        ]);
    }

    public function payments(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $payments = TaxPayment::query()
            ->where('company_id', $companyId)
            ->with(['taxType:id,code,name', 'period:id,label,start_date,end_date', 'bankAccount:id,code,name', 'recorder:id,name'])
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->get();

        $types = TaxType::query()
            ->where('company_id', $companyId)
            ->where('active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        $periods = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->orderByDesc('start_date')
            ->get(['id', 'label', 'tax_type_id', 'start_date', 'end_date', 'status']);

        $bankAccounts = Account::query()
            ->where('company_id', $companyId)
            ->where('is_active', true)
            ->where('is_bank_account', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('accounting.taxation.payments', compact('payments', 'types', 'periods', 'bankAccounts') + ['cs' => $this->cs()]);
    }

    public function storePayment(Request $request)
    {
        $this->requirePermission($request, 'taxation.edit');

        $data = $request->validate([
            'tax_type_id' => ['required', 'integer'],
            'period_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'payment_date' => ['required', 'date'],
            'bank_account_id' => ['nullable', 'integer'],
            'payment_ref' => ['nullable', 'string', 'max:100'],
            'receipt_number' => ['nullable', 'string', 'max:100'],
            'authority' => ['nullable', 'string', 'max:191'],
        ]);

        try {
            $this->paymentService->recordPayment($this->companyId(), $data, (int) $request->user()->id);
        } catch (HttpExceptionInterface | ModelNotFoundException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()])->withInput();
        }

        return redirect()
            ->route('accounting.taxation.payments')
            ->with('success', __('Tax payment recorded.'));
    }

    public function payeWht(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $payeTransactions = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->posted()
            ->whereHas('taxCode', fn ($q) => $q->whereHas('taxType', fn ($q2) => $q2->where('category', 'PAYE')))
            ->with('taxCode:id,code,name')
            ->orderByDesc('created_at')
            ->get();

        $whtTransactions = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->posted()
            ->whereHas('taxCode', fn ($q) => $q->whereHas('taxType', fn ($q2) => $q2->where('category', 'WHT')))
            ->with('taxCode:id,code,name')
            ->orderByDesc('created_at')
            ->get();

        $certificates = WhtCertificate::query()
            ->where('company_id', $companyId)
            ->with('vendor:id,name')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $payeTotal = (float) $payeTransactions->sum('tax_amount');
        $whtTotal = (float) $whtTransactions->sum('tax_amount');

        return view('accounting.taxation.paye-wht', compact(
            'payeTransactions', 'whtTransactions', 'certificates', 'payeTotal', 'whtTotal'
        ) + ['cs' => $this->cs()]);
    }

    public function calendar(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $periods = TaxPeriod::query()
            ->where('company_id', $companyId)
            ->with('taxType:id,code,name')
            ->whereNotNull('filing_due_date')
            ->orderBy('filing_due_date')
            ->get();

        $obligations = $periods->map(function (TaxPeriod $period) {
            $daysLeft = (int) today()->diffInDays($period->filing_due_date);
            $status = match (true) {
                $period->status === 'CLOSED' => 'completed',
                $period->filing_due_date->isPast() => 'overdue',
                $daysLeft <= 7 => 'urgent',
                $daysLeft <= 30 => 'upcoming',
                default => 'future',
            };

            return [
                'period_id' => $period->id,
                'label' => $period->label,
                'tax_type_code' => $period->taxType?->code,
                'tax_type_name' => $period->taxType?->name,
                'filing_due_date' => optional($period->filing_due_date)->toDateString(),
                'payment_due_date' => optional($period->payment_due_date)->toDateString(),
                'period_status' => $period->status,
                'days_left' => $daysLeft,
                'status' => $status,
                'working_paper_url' => route('accounting.taxation.returns.working-paper', ['periodId' => $period->id]),
            ];
        });

        return view('accounting.taxation.calendar', compact('obligations') + ['cs' => $this->cs()]);
    }

    public function storeAdjustment(Request $request)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        $data = $request->validate([
            'period_id' => ['required', 'integer'],
            'tax_type_id' => ['required', 'integer'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'direction' => ['required', 'in:ADD,REDUCE'],
            'reason' => ['required', 'string', 'max:1000'],
        ]);

        $period = TaxPeriod::find($data['period_id']);
        abort_unless($period !== null && (int) $period->company_id === $companyId, 404);

        $taxType = TaxType::find($data['tax_type_id']);
        abort_unless($taxType !== null && (int) $taxType->company_id === $companyId, 404);

        DB::transaction(function () use ($companyId, $data, $request) {
            $adjustment = TaxAdjustment::create([
                'company_id' => $companyId,
                'period_id' => $data['period_id'],
                'tax_type_id' => $data['tax_type_id'],
                'amount' => $data['amount'],
                'direction' => $data['direction'],
                'reason' => $data['reason'],
                'status' => 'PENDING',
                'created_by' => $request->user()->id,
            ]);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'TAX_ADJUSTMENT',
                $adjustment->id,
                'status',
                null,
                'PENDING',
                $data['reason']
            );
        });

        return back()->with('success', __('Adjustment submitted for approval.'));
    }

    public function approveAdjustment(Request $request, int $adjustment)
    {
        $this->requirePermission($request, 'taxation.approve');
        $companyId = $this->companyId();

        $adj = TaxAdjustment::where('company_id', $companyId)->findOrFail($adjustment);
        abort_unless($adj->status === 'PENDING', 422, 'Adjustment is not pending.');

        DB::transaction(function () use ($adj, $companyId, $request) {
            $oldStatus = $adj->status;
            $adj->update(['status' => 'APPROVED', 'approved_by' => $request->user()->id, 'approved_at' => now()]);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'TAX_ADJUSTMENT',
                $adj->id,
                'status',
                $oldStatus,
                'APPROVED',
                'Adjustment approved.'
            );
        });

        return back()->with('success', __('Adjustment approved.'));
    }

    public function rejectAdjustment(Request $request, int $adjustment)
    {
        $this->requirePermission($request, 'taxation.approve');
        $companyId = $this->companyId();

        $adj = TaxAdjustment::where('company_id', $companyId)->findOrFail($adjustment);
        abort_unless($adj->status === 'PENDING', 422, 'Adjustment is not pending.');

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($adj, $companyId, $request, $data) {
            $oldStatus = $adj->status;
            $adj->update(['status' => 'REJECTED']);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'TAX_ADJUSTMENT',
                $adj->id,
                'status',
                $oldStatus,
                'REJECTED',
                $data['reason'] ?? 'Adjustment rejected.'
            );
        });

        return back()->with('success', __('Adjustment rejected.'));
    }

    public function generateReturn(Request $request)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        $data = $request->validate([
            'period_id' => ['required', 'integer'],
        ]);

        $period = TaxPeriod::where('company_id', $companyId)->find($data['period_id']);
        abort_unless($period !== null, 404);

        try {
            // Gate: RECONCILED → RETURN_DRAFTED (obligation lifecycle).
            app(\App\Services\Tax\TaxObligationService::class)
                ->draftReturn($companyId, $period->id, (int) $request->user()->id);
            return back()->with('success', __('Tax return generated.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }
    }

    public function approveReturn(Request $request, int $returnId)
    {
        $this->requirePermission($request, 'taxation.approve');
        $companyId = $this->companyId();

        $return = TaxReturn::where('company_id', $companyId)->findOrFail($returnId);

        try {
            // Gate: RETURN_DRAFTED → RETURN_APPROVED (obligation lifecycle).
            app(\App\Services\Tax\TaxObligationService::class)
                ->approveReturn($companyId, $return->period_id, (int) $request->user()->id);
            return back()->with('success', __('Tax return approved.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }
    }

    public function rejectReturn(Request $request, int $returnId)
    {
        $this->requirePermission($request, 'taxation.approve');
        $companyId = $this->companyId();

        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $return = TaxReturn::where('company_id', $companyId)->findOrFail($returnId);

        try {
            // Gate: RETURN_DRAFTED / RETURN_APPROVED → REJECTED (obligation lifecycle).
            app(\App\Services\Tax\TaxObligationService::class)
                ->rejectReturn($companyId, $return->period_id, (int) $request->user()->id, $data['reason'] ?? null);
            return back()->with('success', __('Tax return rejected.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }
    }

    public function fileReturn(Request $request, int $returnId)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        $data = $request->validate([
            'reference' => ['nullable', 'string', 'max:100'],
        ]);

        $return = TaxReturn::where('company_id', $companyId)->findOrFail($returnId);

        try {
            // Gate: RETURN_APPROVED → FILED (obligation lifecycle).
            app(\App\Services\Tax\TaxObligationService::class)
                ->fileReturn($companyId, $return->period_id, $data['reference'] ?? null);
            return back()->with('success', __('Tax return filed.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['return' => $e->getMessage()]);
        }
    }

    public function generateCertificate(Request $request)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        $data = $request->validate([
            'supplier_id' => ['required', 'integer'],
            'tax_code_id' => ['required', 'integer'],
            'period_id' => ['required', 'integer'],
            'gross_amount' => ['required', 'numeric', 'min:0.01'],
            'tax_amount' => ['required', 'numeric', 'min:0'],
        ]);

        try {
            $service = new \App\Services\Tax\WhtCertificateService();
            $service->createFromForm($companyId, $data, (int) $request->user()->id);
            return back()->with('success', __('WHT certificate generated.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['certificate' => $e->getMessage()]);
        }
    }

    public function revokeCertificate(Request $request, int $certificate)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        try {
            $service = new \App\Services\Tax\WhtCertificateService();
            $service->revoke($companyId, $certificate, (int) $request->user()->id);
            return back()->with('success', __('WHT certificate revoked.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['certificate' => $e->getMessage()]);
        }
    }

    public function voidPayment(Request $request, int $payment)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        try {
            $this->paymentService->voidPayment($companyId, $payment);
            return back()->with('success', __('Tax payment voided.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['payment' => $e->getMessage()]);
        }
    }

    public function closePeriod(Request $request, int $period)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        $periodModel = TaxPeriod::where('company_id', $companyId)->findOrFail($period);
        abort_unless($periodModel->status === 'OPEN', 422, 'Period is not open.');

        DB::transaction(function () use ($periodModel, $companyId, $request) {
            $oldStatus = $periodModel->status;
            $periodModel->update(['status' => 'CLOSED']);

            TaxAuditTrail::log(
                $companyId,
                (int) $request->user()->id,
                'TAX_PERIOD',
                $periodModel->id,
                'status',
                $oldStatus,
                'CLOSED',
                'Tax period closed.'
            );
        });

        return back()->with('success', __('Tax period closed.'));
    }

    /**
     * Tax Obligations dashboard — one row per open obligation across all tax types.
     */
    public function obligations(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $obligations = TaxObligation::query()
            ->forCompany($companyId)
            ->with(['period:id,label,start_date,end_date,filing_due_date,status', 'taxType:id,code,name,category'])
            ->active()
            ->orderBy('status')
            ->orderBy('created_at')
            ->get();

        $rows = [];

        foreach ($obligations as $obligation) {
            $period = $obligation->period;
            if (! $period) {
                continue;
            }

            $rows[] = [
                'id'                    => $obligation->id,
                'period_id'             => $period->id,
                'period_label'          => $period->label,
                'period_start'          => $period->start_date?->toDateString(),
                'period_end'            => $period->end_date?->toDateString(),
                'filing_due_date'       => $period->filing_due_date?->toDateString(),
                'tax_type'              => $obligation->taxType?->name,
                'tax_type_code'         => $obligation->taxType?->code,
                'category'              => $obligation->taxType?->category,
                'status'                => $obligation->status,
                'is_overdue'            => $obligation->isOverdue(),
                'blocked_reason'        => $obligation->blocked_reason,
                'variance_waived'       => $obligation->variance_waived,
                'nil_or_refund_flag'    => $obligation->nil_or_refund_flag,
                'working_paper_url'     => route('accounting.taxation.returns.working-paper', ['periodId' => $period->id]),
            ];
        }

        $openCount = $obligations->whereIn('status', [
            TaxObligation::STATUS_OPEN,
            TaxObligation::STATUS_CALCULATING,
            TaxObligation::STATUS_READY_TO_RECONCILE,
        ])->count();
        $readyCount = $obligations->where('status', TaxObligation::STATUS_READY_TO_RECONCILE)->count();
        $overdueCount = $obligations->filter(fn ($o) => $o->isOverdue())->count();

        return view('accounting.taxation.obligations', [
            'rows'         => collect($rows),
            'openCount'    => $openCount,
            'readyCount'   => $readyCount,
            'overdueCount' => $overdueCount,
            'totalCount'   => $obligations->count(),
            'cs'           => $this->cs(),
        ]);
    }

    public function reconcileObligation(Request $request, int $period)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        $periodModel = TaxPeriod::where('company_id', $companyId)->find($period);
        abort_unless($periodModel !== null, 404);

        $data = $request->validate([
            'waive'       => ['sometimes', 'boolean'],
            'waive_reason' => ['nullable', 'string', 'max:1000'],
        ]);

        // Variance for the period: calculated (computed tax) minus expected (GL posted).
        $aggregates = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->where('period_id', $periodModel->id)
            ->posted()
            ->selectRaw('side, SUM(ROUND(base_amount * rate_pct / 100, 2)) AS expected_tax, SUM(tax_amount) AS calculated_tax')
            ->groupBy('side')
            ->get()
            ->keyBy('side');

        $outputCalculated = (float) ($aggregates['OUTPUT']->calculated_tax ?? 0);
        $inputCalculated  = (float) ($aggregates['INPUT']->calculated_tax ?? 0);
        $adjustments      = $this->approvedAdjustmentsNet($companyId, $periodModel->id);
        $netCalculated    = round($outputCalculated - $inputCalculated + $adjustments, 2);

        $payableId = DefaultAccountMapping::getAccountId($companyId, 'tax_payable');
        $receivableId = DefaultAccountMapping::getAccountId($companyId, 'tax_receivable');

        $glMovement = 0.0;
        if ($payableId !== null || $receivableId !== null) {
            $glMovement = round(
                $this->glMovementForWindow(
                    $companyId,
                    $payableId ?? $receivableId,
                    $periodModel->start_date,
                    $periodModel->end_date
                ),
                2
            );
        }

        $variance = round($netCalculated - $glMovement, 2);

        try {
            app(TaxObligationService::class)->reconcile(
                $companyId,
                $periodModel->id,
                $variance,
                (int) $request->user()->id,
                (bool) ($data['waive'] ?? false),
                $data['waive_reason'] ?? null
            );
            return back()->with('success', __('Tax obligation reconciled.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['obligation' => $e->getMessage()]);
        }
    }

    public function reopenObligation(Request $request, int $period)
    {
        $this->requirePermission($request, 'taxation.edit');
        $companyId = $this->companyId();

        $periodModel = TaxPeriod::where('company_id', $companyId)->find($period);
        abort_unless($periodModel !== null, 404);

        try {
            app(TaxObligationService::class)->reopenRejected(
                $companyId,
                $periodModel->id,
                (int) $request->user()->id
            );
            return back()->with('success', __('Rejected tax return reopened for correction.'));
        } catch (\Throwable $e) {
            return back()->withErrors(['obligation' => $e->getMessage()]);
        }
    }

    public function recognitionRules(Request $request)
    {
        $this->requirePermission($request, 'taxation.view');
        $companyId = $this->companyId();

        $rules = TaxRecognitionRule::query()
            ->where('company_id', $companyId)
            ->with('taxType:id,code,name')
            ->orderBy('tax_type_id')
            ->get();

        $typesWithoutRule = TaxType::query()
            ->where('company_id', $companyId)
            ->where('active', true)
            ->whereNotIn('id', $rules->pluck('tax_type_id'))
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('accounting.taxation.recognition-rules', compact('rules', 'typesWithoutRule') + ['cs' => $this->cs()]);
    }

    private function companyId(): int
    {
        return (int) session('current_company_id');
    }

    private function cs(): string
    {
        return (string) \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $this->companyId(), '$');
    }

    private function approvedAdjustmentsNet(int $companyId, ?int $periodId = null): float
    {
        $add = (float) TaxAdjustment::query()
            ->approved()
            ->where('company_id', $companyId)
            ->when($periodId !== null, fn ($query) => $query->where('period_id', $periodId))
            ->where('direction', 'ADD')
            ->sum('amount');

        $reduce = (float) TaxAdjustment::query()
            ->approved()
            ->where('company_id', $companyId)
            ->when($periodId !== null, fn ($query) => $query->where('period_id', $periodId))
            ->where('direction', 'REDUCE')
            ->sum('amount');

        return round($add - $reduce, 2);
    }

    private function windowsOverlap(TaxCodeRate $a, TaxCodeRate $b): bool
    {
        $openEnded = Carbon::parse(self::OPEN_ENDED_DATE);
        $aFrom = Carbon::parse($a->effective_from);
        $aTo = $a->effective_to !== null ? Carbon::parse($a->effective_to) : $openEnded;
        $bFrom = Carbon::parse($b->effective_from);
        $bTo = $b->effective_to !== null ? Carbon::parse($b->effective_to) : $openEnded;

        return $aFrom->lessThanOrEqualTo($bTo) && $bFrom->lessThanOrEqualTo($aTo);
    }

    private function attachPeriodStats(int $companyId, iterable $periods): void
    {
        $periodIds = collect($periods)->pluck('id')->filter()->all();

        if ($periodIds === []) {
            return;
        }

        $stats = TaxTransaction::query()
            ->where('company_id', $companyId)
            ->posted()
            ->whereIn('period_id', $periodIds)
            ->selectRaw('period_id, side, SUM(base_amount) AS base_total, SUM(tax_amount) AS tax_total')
            ->groupBy('period_id', 'side')
            ->get();

        $byPeriod = [];

        foreach ($stats as $stat) {
            $byPeriod[$stat->period_id][$stat->side] = [
                'base' => (float) $stat->base_total,
                'tax' => (float) $stat->tax_total,
            ];
        }

        foreach ($periods as $period) {
            $output = $byPeriod[$period->id]['OUTPUT'] ?? ['base' => 0.0, 'tax' => 0.0];
            $input = $byPeriod[$period->id]['INPUT'] ?? ['base' => 0.0, 'tax' => 0.0];

            $period->taxable_sales = round($output['base'], 2);
            $period->output_tax = round($output['tax'], 2);
            $period->taxable_purchases = round($input['base'], 2);
            $period->input_tax = round($input['tax'], 2);
            $period->net_payable = round($output['tax'] - $input['tax'], 2);
        }
    }

    private function workingPaperSummary(int $companyId, TaxPeriod $period, $transactions, $rows): array
    {
        $outputRows = collect($rows)->where('side', 'OUTPUT');
        $inputRows = collect($rows)->where('side', 'INPUT');

        $outputBase = round((float) $outputRows->sum('base_amount'), 2);
        $inputBase = round((float) $inputRows->sum('base_amount'), 2);
        $outputExpected = round((float) $outputRows->sum('expected_tax'), 2);
        $inputExpected = round((float) $inputRows->sum('expected_tax'), 2);
        $outputCalculated = round((float) $outputRows->sum('calculated_tax'), 2);
        $inputCalculated = round((float) $inputRows->sum('calculated_tax'), 2);

        $recoverableInput = round(
            $transactions
                ->filter(fn (TaxTransaction $txn) => $txn->side === 'INPUT')
                ->sum(fn (TaxTransaction $txn) => (float) ($txn->recoverable_tax_amount ?? $txn->tax_amount)),
            2
        );

        $adjustments = $this->approvedAdjustmentsNet($companyId, $period->id);
        $netCalculated = round($outputCalculated - $inputCalculated + $adjustments, 2);
        $netExpected = round($outputExpected - $inputExpected, 2);

        return [
            'output_base' => $outputBase,
            'input_base' => $inputBase,
            'output_expected' => $outputExpected,
            'input_expected' => $inputExpected,
            'output_tax' => $outputCalculated,
            'input_tax' => $inputCalculated,
            'recoverable_input' => $recoverableInput,
            'adjustments' => $adjustments,
            'net_calculated' => $netCalculated,
            'net_expected' => $netExpected,
            'total_variance' => round($netCalculated - $netExpected, 2),
            'posted_gl' => round($this->glMovementForWindow(
                $companyId,
                DefaultAccountMapping::getAccountId($companyId, 'tax_payable'),
                $period->start_date,
                $period->end_date
            ), 2),
            'transaction_count' => $transactions->count(),
        ];
    }

    private function glMovementForWindow(int $companyId, ?int $accountId, $from, $to): float
    {
        if ($accountId === null) {
            return 0.0;
        }

        return (float) JournalEntryLine::query()
            ->join('journal_entries', 'journal_entries.id', '=', 'journal_entry_lines.journal_entry_id')
            ->where('journal_entry_lines.account_id', $accountId)
            ->where('journal_entries.company_id', $companyId)
            ->whereIn('journal_entries.status', [JournalEntry::STATUS_POSTED, JournalEntry::STATUS_REVERSED])
            ->whereBetween('journal_entries.date', [$from, $to])
            ->selectRaw('COALESCE(SUM(journal_entry_lines.credit - journal_entry_lines.debit), 0) AS movement')
            ->value('movement');
    }
}
