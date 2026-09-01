<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\MethodConversion;
use App\Services\Accounting\MethodConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

/**
 * Switch to Accrual (spec §5) — a gated, journaled, one-way controlled
 * conversion for companies currently on the cash basis.
 */
class SwitchToAccrualController extends Controller
{
    private const ADMIN_ROLES = ['system_admin', 'company_admin'];

    private string $companyId;
    private ?Company $company;

    public function __construct(
        private MethodConversionService $service,
    ) {
        $this->companyId = (string) session('current_company_id');
    }

    public function show(): \Illuminate\View\View
    {
        return $this->gate(function () {
            $conversion = MethodConversion::query()
                ->forCompany((int) $this->companyId)
                ->latest()
                ->first();

            $conversionJournal = null;
            if ($conversion && $conversion->conversion_journal_id) {
                $conversionJournal = \App\Models\JournalEntry::with('lines.account')
                    ->find($conversion->conversion_journal_id);
            }

            return view('accounting.settings.switch-accrual', [
                'company' => $this->company,
                'companyId' => (int) $this->companyId,
                'cs' => $this->cs(),
                'conversion' => $conversion,
                'conversionJournal' => $conversionJournal,
                'treatmentOptions' => $this->treatmentOptions(),
                'defaultCutOff' => $this->defaultCutOff(),
                'lastPostedPeriodEnd' => $this->lastPostedPeriodEnd(),
            ]);
        });
    }

    public function store(Request $request)
    {
        return $this->gate(function () use ($request) {
            $data = $request->validate([
                'cut_off_date' => ['required', 'date'],
                'treatment' => ['required', 'string', Rule::in(array_keys($this->treatmentOptions()))],
                'ar' => ['nullable', 'numeric', 'min:0'],
                'inv' => ['nullable', 'numeric', 'min:0'],
                'pre' => ['nullable', 'numeric', 'min:0'],
                'ap' => ['nullable', 'numeric', 'min:0'],
                'acc' => ['nullable', 'numeric', 'min:0'],
                'une' => ['nullable', 'numeric', 'min:0'],
                'action' => ['required', 'string', Rule::in(['draft', 'activate'])],
            ]);

            $companyId = (int) $this->companyId;
            $balances = array_intersect_key($data, array_flip(['ar', 'inv', 'pre', 'ap', 'acc', 'une']));

            try {
                if ($data['action'] === 'draft') {
                    $conversion = $this->service->saveDraft(
                        $companyId,
                        $data['cut_off_date'],
                        $data['treatment'],
                        $balances,
                        $request->user()->id
                    );
                    $this->service->persistOpeningBalances($conversion->id, $balances);

                    return redirect()->route('settings.switch_accrual')
                        ->with('status', 'Conversion draft saved. Review the journal then activate when ready.');
                }

                $conversion = $this->service->activate(
                    $companyId,
                    $data['cut_off_date'],
                    $data['treatment'],
                    $balances,
                    $request->user()->id
                );

                return redirect()->route('settings.switch_accrual')
                    ->with('status', 'Company switched to the accrual basis. Conversion journal #' .
                        $conversion->conversion_journal_id . ' posted.');
            } catch (\InvalidArgumentException $e) {
                throw ValidationException::withMessages(['cut_off_date' => $e->getMessage()]);
            }
        });
    }

    /**
     * Resolve the company + apply the entry gate (admin AND cash method).
     */
    private function gate(\Closure $callback)
    {
        $companyId = (int) $this->companyId;
        $this->company = Company::find($companyId);

        if (!$this->company) {
            abort(404, 'Company not found.');
        }

        $role = auth()->user()?->getRoleInCurrentCompanyAttribute();

        if (!$this->company->isCashBasis()) {
            abort(403, 'This company is already on the accrual basis.');
        }

        if (!in_array($role, self::ADMIN_ROLES, true) && !auth()->user()?->isSuperAdmin()) {
            abort(403, 'Only an administrator can switch accounting methods.');
        }

        return $callback();
    }

    private function treatmentOptions(): array
    {
        return [
            MethodConversionService::TREATMENT_PROSPECTIVE => 'Prospective (recommended) — history stays cash basis',
            MethodConversionService::TREATMENT_RETROSPECTIVE => 'Retrospective — restate prior periods',
        ];
    }

    private function defaultCutOff(): ?string
    {
        $companyId = (int) $this->companyId;
        $period = \App\Models\AccountingPeriod::forCompany($companyId)
            ->orderByDesc('end_date')
            ->first();
        return $period?->end_date?->format('Y-m-d');
    }

    private function lastPostedPeriodEnd(): ?string
    {
        $companyId = (int) $this->companyId;
        $period = \App\Models\AccountingPeriod::forCompany($companyId)
            ->where('status', '<>', 'open')
            ->orderByDesc('end_date')
            ->first();
        return $period?->end_date?->format('Y-m-d');
    }

    private function cs(): string
    {
        return \App\Models\SystemSetting::getValue('currency', 'currency_symbol', $this->companyId, '$');
    }
}
