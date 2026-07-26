<?php

namespace App\Services\EIS;

use App\Models\Customer;
use App\Models\EisSubmission;
use App\Models\EisTerminal;
use App\Models\JournalEntry;
use App\Models\PosSale;
use App\Models\PosSaleLine;
use App\Models\Product;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class EisSubmissionService
{
    protected string $baseUrl;
    protected int $maxRetries = 5;

    public function __construct()
    {
        $this->baseUrl = config('eis.api_url', 'https://dev-eis-api.mra.mw');
    }

    public function activateTerminal(EisTerminal $terminal, string $tac): array
    {
        $this->validateTerminalCompany($terminal);

        $tin = $this->getCompanyTin($terminal->company_id);
        $siteId = $terminal->site_id;
        $serialNumber = $terminal->device_serial;

        $response = Http::post("{$this->baseUrl}/api/v1/terminal/activate", [
            'tin' => $tin,
            'siteId' => $siteId,
            'serialNumber' => $serialNumber,
            'tac' => $tac,
        ]);

        if (!$response->successful()) {
            throw new InvalidArgumentException(
                "Terminal activation failed: HTTP {$response->status()}"
            );
        }

        $data = $response->json();

        if (($data['statusCode'] ?? 0) !== 200) {
            throw new InvalidArgumentException(
                "Terminal activation failed: " . ($data['message'] ?? 'Unknown error')
            );
        }

        $terminal->update([
            'jwt_token' => $data['jwt_token'] ?? $data['token'] ?? null,
            'secret_key' => $data['secret_key'] ?? null,
            'validation_key' => $data['validation_key'] ?? null,
            'status' => EisTerminal::STATUS_ACTIVE,
            'activated_at' => now(),
        ]);

        return $data;
    }

    public function submitInvoice(EisTerminal $terminal, PosSale $sale): EisSubmission
    {
        $this->validateTerminalCompany($terminal);

        if (!$terminal->isActive()) {
            throw new InvalidArgumentException("Terminal {$terminal->site_id} is not active.");
        }

        $sale->load(['lines.product', 'customer']);

        $payload = $this->buildPayload($terminal, $sale);

        $signature = $this->signPayload($payload, $terminal->secret_key);

        $submission = EisSubmission::create([
            'company_id' => $terminal->company_id,
            'eis_terminal_id' => $terminal->id,
            'receipt_number' => $sale->sale_number,
            'invoice_type' => $sale->customer?->tin ? 'B2B' : 'B2C',
            'status' => EisSubmission::STATUS_PENDING,
            'request_payload' => $payload,
            'retry_count' => 0,
        ]);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$terminal->jwt_token}",
                'X-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/api/v1/sales/submit-sales-transaction", $payload);

            $submission->update([
                'submitted_at' => now(),
                'status' => EisSubmission::STATUS_SUBMITTED,
                'response_payload' => $response->json(),
            ]);

            $responseData = $response->json();
            $statusCode = $responseData['statusCode'] ?? 0;

            if ($statusCode === 200 || $statusCode === 201) {
                $submission->update([
                    'status' => EisSubmission::STATUS_ACCEPTED,
                    'validation_url' => $responseData['validationURL'] ?? null,
                    'accepted_at' => now(),
                ]);

                $terminal->update(['last_submission_at' => now()]);
            } else {
                $errors = $responseData['validationErrors'] ?? [];
                $errorMessage = is_array($errors) ? implode('; ', $errors) : ($responseData['message'] ?? 'Validation failed');

                $submission->update([
                    'status' => EisSubmission::STATUS_REJECTED,
                    'error_message' => $errorMessage,
                ]);

                if ($responseData['shouldBlockTerminal'] ?? false) {
                    $terminal->update(['should_block_terminal' => true]);
                }
            }
        } catch (\Exception $e) {
            $submission->update([
                'status' => EisSubmission::STATUS_ERROR,
                'error_message' => $e->getMessage(),
            ]);

            Log::error('EIS submission failed', [
                'terminal_id' => $terminal->id,
                'sale_id' => $sale->id,
                'error' => $e->getMessage(),
            ]);
        }

        return $submission;
    }

    public function retrySubmission(EisSubmission $submission): EisSubmission
    {
        if ($submission->status !== EisSubmission::STATUS_ERROR) {
            throw new InvalidArgumentException('Only errored submissions can be retried.');
        }

        if ($submission->retry_count >= $this->maxRetries) {
            throw new InvalidArgumentException("Maximum retry count ({$this->maxRetries}) reached.");
        }

        $terminal = $submission->terminal()->with('company')->first();

        if (!$terminal || !$terminal->isActive()) {
            throw new InvalidArgumentException("Terminal is not active for retry.");
        }

        $submission->increment('retry_count');

        $payload = $submission->request_payload;
        $signature = $this->signPayload($payload, $terminal->secret_key);

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$terminal->jwt_token}",
                'X-Signature' => $signature,
                'Content-Type' => 'application/json',
            ])->post("{$this->baseUrl}/api/v1/sales/submit-sales-transaction", $payload);

            $submission->update([
                'submitted_at' => now(),
                'status' => EisSubmission::STATUS_SUBMITTED,
                'response_payload' => $response->json(),
            ]);

            $responseData = $response->json();
            $statusCode = $responseData['statusCode'] ?? 0;

            if ($statusCode === 200 || $statusCode === 201) {
                $submission->update([
                    'status' => EisSubmission::STATUS_ACCEPTED,
                    'validation_url' => $responseData['validationURL'] ?? null,
                    'accepted_at' => now(),
                ]);

                $terminal->update(['last_submission_at' => now()]);
            } else {
                $errors = $responseData['validationErrors'] ?? [];
                $errorMessage = is_array($errors) ? implode('; ', $errors) : ($responseData['message'] ?? 'Validation failed');

                $submission->update([
                    'status' => EisSubmission::STATUS_REJECTED,
                    'error_message' => $errorMessage,
                ]);
            }
        } catch (\Exception $e) {
            $submission->update([
                'error_message' => $e->getMessage(),
            ]);

            Log::error('EIS retry failed', [
                'submission_id' => $submission->id,
                'retry_count' => $submission->retry_count,
                'error' => $e->getMessage(),
            ]);
        }

        return $submission->fresh();
    }

    public function buildPayload(EisTerminal $terminal, PosSale $sale): array
    {
        $tin = $this->getCompanyTin($terminal->company_id);
        $buyerTin = $sale->customer?->tin;
        $saleDate = $sale->created_at instanceof \Carbon\Carbon
            ? $sale->created_at->format('Y-m-d')
            : date('Y-m-d');

        $lineItems = $sale->lines->map(function (PosSaleLine $line) {
            $product = $line->product;
            $subtotal = (float) $line->quantity * (float) $line->unit_price;
            $discountAmount = $line->discount_amount ?? 0;

            return [
                'productCode' => $product?->sku ?? 'MISC',
                'description' => $line->description ?? $product?->name ?? 'Item',
                'unitPrice' => round((float) $line->unit_price, 2),
                'quantity' => round((float) $line->quantity, 2),
                'discount' => round((float) $discountAmount, 2),
                'netTotal' => round($subtotal - $discountAmount, 2),
                'taxRateId' => $line->is_taxable ? 1 : 0,
            ];
        })->toArray();

        $subtotal = collect($lineItems)->sum('netTotal');
        $taxRate = 0.175;
        $totalTax = round($subtotal * $taxRate, 2);
        $invoiceTotal = round($subtotal + $totalTax, 2);

        return [
            'invoiceHeader' => [
                'tin' => $tin,
                'buyerTin' => $buyerTin,
                'transactionDate' => $saleDate,
                'paymentMethod' => 'cash',
                'siteId' => $terminal->site_id,
                'invoiceType' => $buyerTin ? 'B2B' : 'B2C',
            ],
            'invoiceLineItems' => $lineItems,
            'invoiceSummary' => [
                'totalNet' => round($subtotal, 2),
                'totalDiscount' => round(collect($lineItems)->sum('discount'), 2),
                'taxBreakDown' => [
                    [
                        'taxRate' => $taxRate,
                        'taxAmount' => $totalTax,
                    ],
                ],
                'totalVAT' => $totalTax,
                'invoiceTotal' => $invoiceTotal,
            ],
        ];
    }

    public function signPayload(array $payload, string $secretKey): string
    {
        $plainText = json_encode($payload, JSON_UNESCAPED_UNICODE);
        return base64_encode(hash_hmac('sha512', $plainText, $secretKey, true));
    }

    protected function validateTerminalCompany(EisTerminal $terminal): void
    {
        $authCompanyId = auth()->user()?->currentCompany?->id;
        if ($authCompanyId && $terminal->company_id !== $authCompanyId) {
            throw new InvalidArgumentException('Terminal does not belong to the current company.');
        }
    }

    protected function getCompanyTin(int $companyId): string
    {
        $company = \App\Models\Company::find($companyId);
        return $company->tax_id ?? '';
    }
}
