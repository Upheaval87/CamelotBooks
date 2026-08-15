<?php

namespace App\Http\Controllers;

use App\Services\PdfPresenter;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfController extends Controller
{
    // ADJUST these class names to your actual models
    const MODELS = [
        'quotation'   => \App\Models\Quotation::class,
        'bill'        => \App\Models\Bill::class,
        'invoice'     => \App\Models\Invoice::class,
        'receipt'     => \App\Models\SalesReceipt::class,
        'grn'         => \App\Models\GoodsReceivedNote::class,
        'credit'      => \App\Models\CreditNote::class,
        'po'          => \App\Models\PurchaseOrder::class,
        'requisition' => \App\Models\PurchaseRequisition::class,
    ];

    public function preview(string $type, int $id)
    {
        $payload = PdfPresenter::payload($type, self::resolve($type, $id));
        $payload['pdfMode'] = false;                      // flex footer lock (screen)
        return view('pdf.document', $payload);
    }

    public function download(string $type, int $id)
    {
        $doc = self::resolve($type, $id);
        $payload = PdfPresenter::payload($type, $doc);
        $payload['pdfMode'] = true;                       // fixed footer lock (dompdf)
        $number = str_replace(['№ ', ' '], ['', '-'], (string) ($payload['number'] ?? ''));
        $number = preg_replace('/[^A-Za-z0-9._-]/', '', $number);
        return Pdf::loadView('pdf.document', $payload)
            ->setPaper('a4')
            ->download(strtolower(str_replace(' ', '-', $payload['title'])) . '-' . $number . '.pdf');
    }

    private static function resolve(string $type, int $id)
    {
        $model = self::MODELS[$type] ?? abort(404);
        $doc = $model::findOrFail($id);
        // ADD YOUR AUTH CHECK HERE, e.g.:
        // $this->authorize('view', $doc);
        return $doc;
    }
}