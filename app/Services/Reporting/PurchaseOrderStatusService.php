<?php
namespace App\Services\Reporting;
use App\Models\PurchaseOrder;

class PurchaseOrderStatusService
{
    public function generate(int $companyId): array
    {
        $pos = PurchaseOrder::forCompany($companyId)
            ->whereIn('status', ['draft', 'sent', 'partially_received'])
            ->with(['vendor', 'lines.product'])
            ->orderBy('date', 'desc')->get();

        $results = [];
        foreach ($pos as $po) {
            $lines = $po->lines->map(fn($l) => [
                'product_name' => $l->product->name ?? 'N/A',
                'sku' => $l->product->sku ?? '',
                'ordered' => (float) $l->quantity_ordered,
                'received' => (float) $l->quantity_received,
                'remaining' => (float) $l->quantity_ordered - (float) $l->quantity_received,
            ])->toArray();

            $results[] = [
                'po_number' => $po->po_number,
                'date' => $po->date,
                'vendor_name' => $po->vendor->name ?? 'N/A',
                'status' => $po->status,
                'lines' => $lines,
            ];
        }

        return ['purchase_orders' => $results];
    }
}
