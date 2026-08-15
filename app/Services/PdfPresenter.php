<?php

namespace App\Services;

class PdfPresenter
{
    /** ENTRY: build the payload for one document. */
    public static function payload(string $type, $doc): array
    {
        $doc = self::normalize($type, $doc);
        $p = match ($type) {
            'quotation'   => self::quotation($doc),
            'bill'        => self::bill($doc),
            'invoice'     => self::invoice($doc),
            'delivery'    => self::delivery($doc),
            'receipt'     => self::receipt($doc),
            'grn'         => self::grn($doc),
            'credit'      => self::credit($doc),
            'po'          => self::po($doc),
            'requisition' => self::requisition($doc),
            default       => abort(404, 'Unknown document type'),
        };
        return $p + ['type' => $type, 'pdfMode' => false, 'fontDir' => 'file://' . str_replace('\\', '/', storage_path('fonts'))];
    }

    /* ================= normalizer: real model → generic shape ================= */

    private static function normalize(string $type, $doc): array
    {
        if (! is_object($doc)) {
            return is_array($doc) ? $doc : [];
        }
        return match ($type) {
            'quotation'   => self::normQuotation($doc),
            'bill'        => self::normBill($doc),
            'invoice'     => self::normInvoice($doc),
            'delivery'    => self::normDelivery($doc),
            'receipt'     => self::normReceipt($doc),
            'grn'         => self::normGrn($doc),
            'credit'      => self::normCredit($doc),
            'po'          => self::normPo($doc),
            'requisition' => self::normRequisition($doc),
            default       => (array) $doc,
        };
    }

    private static function normParty($model): array
    {
        return [
            'name'           => $model?->display_name ?: $model?->name,
            'address'        => $model?->billing_address ?: $model?->remit_to_address,
            'email'          => $model?->email,
            'phone'          => $model?->phone,
            'contact_person' => $model?->contact_person,
        ];
    }

    private static function normPartyRel($doc, string $rel): array
    {
        return self::normParty(data_get($doc, $rel));
    }

    private static function normLine($line): array
    {
        $product = $line instanceof \Illuminate\Database\Eloquent\Model ? $line->product : data_get($line, 'product');
        return [
            'item'         => [
                'code' => $product?->sku,
                'name' => $product?->name,
            ],
            'description'  => $line->description ?? data_get($line, 'description', ''),
            'qty'          => (float) ($line->quantity ?? data_get($line, 'quantity', 0)),
            'unit_price'   => (float) ($line->unit_price ?? data_get($line, 'unit_price', 0)),
            'disc_percent' => (float) ($line->discount ?? data_get($line, 'discount', 0)),
            'tax_percent'  => (float) ($line->tax_rate ?? data_get($line, 'tax_rate', 0)),
            'amount'       => (float) ($line->line_total ?? data_get($line, 'line_total', $line->amount ?? 0)),
        ];
    }

    private static function normLines($lines): array
    {
        $out = [];
        foreach ($lines ?: [] as $line) {
            if (is_object($line) || is_array($line)) {
                $out[] = self::normLine($line);
            }
        }
        return $out;
    }

    private static function normQuotation($d): array
    {
        $c = data_get($d, 'customer');
        return [
            'number'         => $d->quotation_number,
            'date'           => $d->quotation_date,
            'valid_until'    => $d->valid_until,
            'payment_terms'  => $c?->payment_terms,
            'currency'       => $d->currency,
            'preparer.name'  => $d->createdByUser?->name,
            'branch.name'    => $d->branch?->name,
            'cost_centre'    => $d->costCenter?->name,
            'customer'       => self::normParty($c),
            'lines'          => self::normLines($d->lines),
            'customer_notes' => $d->memo,
        ];
    }

    private static function normBill($d): array
    {
        $v = data_get($d, 'vendor');
        return [
            'number'              => $d->bill_number,
            'date'                => $d->bill_date,
            'due_date'            => $d->due_date,
            'payment_terms'       => $v?->payment_terms,
            'supplier_invoice_no' => $d->reference,
            'purchase_order_no'   => $d->po_number,
            'grn_no'              => $d->grn_reference,
            'preparer.name'       => $d->createdBy?->name,
            'branch.name'         => $d->branch?->name,
            'supplier'            => self::normParty($v),
            'lines'               => self::normLines($d->lines),
            'notes'               => $d->supplier_notes,
            'payment_instructions' => $d->payment_instructions,
            'freight'             => $d->freight_charges,
            'insurance'           => $d->insurance_charges,
            'customs'             => $d->customs_charges,
            'other_charges'       => $d->other_charges,
        ];
    }

    private static function normInvoice($d): array
    {
        $c = data_get($d, 'customer');
        return [
            'number'         => $d->invoice_number,
            'date'           => $d->invoice_date,
            'due_date'       => $d->due_date,
            'payment_terms'  => $c?->payment_terms,
            'currency'       => $d->currency,
            'preparer.name'  => $d->createdBy?->name,
            'branch.name'    => $d->branch?->name,
            'customer'       => self::normParty($c),
            'lines'          => self::normLines($d->lines),
            'payment_instructions' => $d->memo,
            'company'        => $d->company?->name,
        ];
    }

    private static function normDelivery($d): array
    {
        return [
            'number'        => $d->delivery_note_number ?? $d->delivery_number,
            'date'          => $d->date,
            'invoice_no'    => $d->invoice_no,
            'vehicle'       => $d->vehicle,
            'driver'        => $d->driver,
            'dispatched_by' => $d->dispatched_by,
            'branch.name'   => $d->branch?->name,
            'customer'      => self::normPartyRel($d, 'customer'),
            'lines'         => self::normLines($d->lines),
        ];
    }

    private static function normReceipt($d): array    {
        $c = data_get($d, 'customer');
        $payment = data_get($d, 'payments', []);
        $first = $payment instanceof \Illuminate\Support\Collection ? $payment->first() : (is_array($payment) ? reset($payment) : $payment);
        $method = $first?->paymentMethod?->name ?: $first?->payment_method;
        return [
            'number'        => $d->receipt_number,
            'date'          => $d->receipt_date,
            'method'        => $method,
            'reference'     => $d->reference,
            'received_by'   => $d->createdByUser?->name ?: $d->createdBy?->name,
            'currency'      => $d->currency,
            'branch.name'   => $d->branch?->name,
            'cost_centre'   => $d->costCenter?->name,
            'customer'      => self::normParty($c),
            'applied_to'    => $c?->display_name ?: $c?->name,
            'amount'        => $d->total ?: $d->amount,
        ];
    }

    private static function normGrn($d): array
    {
        $v = data_get($d, 'vendor');
        $lines = [];
        foreach ($d->lines ?: [] as $line) {
            $product = $line->product ?? data_get($line, 'product');
            $lines[] = [
                'item'          => ['code' => $product?->sku, 'name' => $product?->name],
                'description'   => $line->description ?? data_get($line, 'description', ''),
                'qty_ordered'   => (float) ($line->quantity_ordered ?? data_get($line, 'quantity_ordered', 0)),
                'qty_received'  => (float) ($line->quantity_received ?? data_get($line, 'quantity_received', 0)),
                'unit'          => $product?->unit_of_measure ?: ($line->transaction_uom ?? data_get($line, 'transaction_uom')),
                'condition'     => '',
            ];
        }
        return [
            'number'              => $d->grn_number,
            'date'                => $d->date,
            'purchase_order_no'   => $d->purchaseOrder?->po_number,
            'supplier_invoice_no' => '',
            'warehouse'           => $d->branch?->name,
            'received_by'         => $d->createdBy?->name,
            'inspected_by'        => '',
            'branch.name'         => $d->branch?->name,
            'supplier'            => self::normParty($v),
            'lines'               => $lines,
            'inspection_notes'    => $d->memo,
        ];
    }

    private static function normCredit($d): array
    {
        $c = data_get($d, 'customer');
        $lines = [];
        foreach ($d->lines ?: [] as $line) {
            $product = $line->product ?? data_get($line, 'product');
            $lines[] = [
                'item'        => ['code' => $product?->sku, 'name' => $product?->name],
                'description' => $line->description ?? data_get($line, 'description', ''),
                'qty'         => (float) ($line->quantity ?? data_get($line, 'quantity', 0)),
                'amount'      => (float) ($line->line_total ?? data_get($line, 'line_total', $line->amount ?? 0)),
            ];
        }
        return [
            'number'       => $d->credit_note_number,
            'date'         => $d->credit_note_date,
            'invoice_no'   => $d->invoice?->invoice_number,
            'reason'       => $d->memo,
            'reason_detail'=> $d->memo,
            'currency'     => $d->currency,
            'approved_by'  => $d->createdBy?->name,
            'branch.name'  => $d->branch?->name,
            'customer'     => self::normParty($c),
            'lines'        => $lines,
        ];
    }

    private static function normPo($d): array
    {
        $v = data_get($d, 'vendor');
        $lines = [];
        foreach ($d->lines ?: [] as $line) {
            $product = $line->product ?? data_get($line, 'product');
            $lines[] = [
                'item'        => ['code' => $product?->sku, 'name' => $product?->name],
                'description' => $line->description ?? data_get($line, 'description', ''),
                'qty'         => (float) ($line->quantity ?? data_get($line, 'quantity', 0)),
                'unit_price'  => (float) ($line->unit_price ?? data_get($line, 'unit_price', 0)),
                'disc_percent'=> 0,
                'tax_percent' => 0,
                'amount'      => (float) ($line->amount ?? data_get($line, 'amount', 0)),
            ];
        }
        return [
            'number'        => $d->po_number,
            'date'          => $d->date,
            'required_by'   => $d->expected_delivery_date,
            'payment_terms' => $v?->payment_terms,
            'cost_centre'   => $d->costCenter?->name,
            'requisition_no'=> $d->requisition?->requisition_number,
            'preparer.name' => $d->createdBy?->name,
            'branch.name'   => $d->branch?->name,
            'supplier'      => self::normParty($v),
            'lines'         => $lines,
            'notes'         => $d->memo,
        ];
    }

    private static function normRequisition($d): array
    {
        $lines = [];
        foreach ($d->lines ?: [] as $line) {
            $product = $line->product ?? data_get($line, 'product');
            $lines[] = [
                'item'           => ['code' => $product?->sku, 'name' => $product?->name],
                'description'    => $line->description ?? data_get($line, 'description', ''),
                'qty'            => (float) ($line->quantity ?? data_get($line, 'quantity', 0)),
                'estimated_cost' => (float) ($line->estimated_total ?? data_get($line, 'estimated_total', 0)),
                'remarks'        => '',
            ];
        }
        return [
            'number'            => $d->requisition_number,
            'date'              => $d->date,
            'needed_by'         => $d->required_by,
            'priority'          => $d->priority,
            'budget_line'       => '',
            'suggested_supplier'=> $d->supplier,
            'status'            => $d->status,
            'requester.name'    => $d->requestedBy?->name,
            'department'        => $d->department,
            'cost_centre'       => $d->costCenter?->name,
            'lines'             => $lines,
            'justification'     => $d->memo,
        ];
    }

    /* ================= helpers ================= */

    private static function g($o, string $key, $default = '—')
    {
        $v = data_get($o, $key);
        return $v === null || $v === '' ? $default : $v;
    }

    private static function money($n): string { return number_format((float) $n, 2); }

    private static function qty($n): string { return rtrim(rtrim(number_format((float)$n, 2), '0'), '.'); }

    private static function date($o, string $key): string
    {
        $d = data_get($o, $key);
        if (! $d) return '—';
        return ($d instanceof \DateTimeInterface ? $d : \Carbon\Carbon::parse($d))->format('M j, Y');
    }

    private static function party($doc, string $rel): array
    {
        $p = data_get($doc, $rel);
        $lines = array_filter([
            data_get($p, 'address'),
            trim(data_get($p, 'email', '') . ' · ' . data_get($p, 'phone', ''), ' ·'),
            data_get($p, 'contact_person') ? 'Attn: ' . data_get($p, 'contact_person') : null,
        ]);
        return ['name' => self::g($p, 'name'), 'lines' => array_values($lines)];
    }

    /** Money line items → cells + computed totals. REPLACE with your existing calc helpers if you have them. */
    private static function linesAndTotals($doc): array
    {
        $lines = []; $subtotal = 0; $discount = 0; $tax = 0;
        foreach (data_get($doc, 'lines', []) as $l) {
            $qty  = (float) data_get($l, 'qty', 0);
            $unit = (float) data_get($l, 'unit_price', data_get($l, 'unit_cost', 0));
            $disc = (float) data_get($l, 'disc_percent', data_get($l, 'discount_percent', 0));
            $rate = (float) data_get($l, 'tax_percent', data_get($l, 'item.tax_rate', 0)); // item tax details
            $gross = $qty * $unit; $dd = $gross * $disc / 100; $amt = $gross - $dd;
            $subtotal += $gross; $discount += $dd; $tax += $amt * $rate / 100;
            $lines[] = [self::g($l,'item.code'), self::g($l,'item.name'), data_get($l,'description',''),
                        self::qty($qty), self::money($unit), self::qty($disc).'%', self::money($amt)];
        }
        return [$lines, compact('subtotal','discount','tax')];
    }

    private static function charges($doc): array
    {
        return [
            ['label'=>'Freight',   'value'=>(float)data_get($doc,'freight',0),       'hideZero'=>true],
            ['label'=>'Insurance', 'value'=>(float)data_get($doc,'insurance',0),     'hideZero'=>true],
            ['label'=>'Customs',   'value'=>(float)data_get($doc,'customs',0),       'hideZero'=>true],
            ['label'=>'Other',     'value'=>(float)data_get($doc,'other_charges',0), 'hideZero'=>true],
        ];
    }

    private static function words(float $a): string
    {
        $w = (int) floor($a); $c = (int) round(($a - $w) * 100);
        $s = self::n2w($w) . ' Kwacha';
        if ($c) $s .= ' and ' . self::n2w($c) . ' tambala';
        return $s . ' only.';
    }

    private static function n2w(int $n): string
    {
        $o = ['zero','one','two','three','four','five','six','seven','eight','nine','ten','eleven','twelve','thirteen','fourteen','fifteen','sixteen','seventeen','eighteen','nineteen'];
        $t = ['','','twenty','thirty','forty','fifty','sixty','seventy','eighty','ninety'];
        if ($n < 20) return $o[$n];
        if ($n < 100) return $t[intdiv($n,10)] . ($n%10 ? ' '.$o[$n%10] : '');
        if ($n < 1000) return $o[intdiv($n,100)] . ' hundred' . ($n%100 ? ' '.self::n2w($n%100) : '');
        if ($n < 1000000) return self::n2w(intdiv($n,1000)) . ' thousand' . ($n%1000 ? ' '.self::n2w($n%1000) : '');
        return self::n2w(intdiv($n,1000000)) . ' million' . ($n%1000000 ? ' '.self::n2w($n%1000000) : '');
    }

    private static function colsMoney(bool $taxCol = false): array
    {
        $c = [
            ['label'=>'Code','width'=>'11%'], ['label'=>'Item','width'=>'26%'], ['label'=>'Description','width'=>'27%'],
            ['label'=>'Qty','width'=>'7%','num'=>true], ['label'=>'Unit Price','width'=>'11%','num'=>true],
        ];
        $c[] = $taxCol ? ['label'=>'Tax %','width'=>'6%','num'=>true] : ['label'=>'Disc','width'=>'6%','num'=>true];
        $c[] = ['label'=>'Amount (K)','width'=>'12%','num'=>true,'amt'=>true];
        return $c;
    }

    /* ================= the 9 documents ================= */

    private static function quotation($d): array
    {
        [$lines,$t] = self::linesAndTotals($d);
        $grand = $t['subtotal'] - $t['discount'] + $t['tax'];
        $p = self::party($d,'customer');
        return [
            'title'=>'Quotation','titleSmall'=>false,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Prepared For','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Quotation Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'Valid Until','v'=>self::date($d,'valid_until')],
                ['l'=>'Prepared By','v'=>self::g($d,'preparer.name')], ['l'=>'Payment Terms','v'=>self::g($d,'payment_terms')],
                ['l'=>'Currency','v'=>self::g($d,'currency')], ['l'=>'Branch','v'=>self::g($d,'branch.name')],
            ],
            'cols'=>self::colsMoney(), 'lines'=>$lines,
            'totals'=>[ ['label'=>'Subtotal','value'=>$t['subtotal']],
                        ['label'=>'Discount','value'=>$t['discount'],'hideZero'=>true],
                        ['label'=>'Tax','value'=>$t['tax'],'hideZero'=>true] ],
            'grandLabel'=>'Grand Total','grand'=>$grand,'words'=>self::words($grand),
            'notes'=>[ ['label'=>'Notes','body'=>self::g($d,'customer_notes','')],
                       ['label'=>'Terms','body'=>['Payment due per stated terms.','Quotation № must be referenced on acceptance.'],'list'=>true] ],
            'sigs'=>[ ['name'=>self::g($d,'preparer.name'),'role'=>'Prepared by'], ['name'=>'','role'=>'Authorised signature'] ],
        ];
    }

    private static function bill($d): array
    {
        [$lines,$t] = self::linesAndTotals($d);
        $ch = self::charges($d);
        $grand = $t['subtotal'] - $t['discount'] + $t['tax'] + array_sum(array_column($ch,'value'));
        $p = self::party($d,'supplier');
        return [
            'title'=>'Bill','titleSmall'=>false,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Supplier','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Bill Details',
            'details'=>[
                ['l'=>'Supplier Invoice','v'=>self::g($d,'supplier_invoice_no')], ['l'=>'Bill Date','v'=>self::date($d,'date')],
                ['l'=>'Due Date','v'=>self::date($d,'due_date')], ['l'=>'Purchase Order','v'=>self::g($d,'purchase_order_no')],
                ['l'=>'GRN Ref','v'=>self::g($d,'grn_no')], ['l'=>'Terms','v'=>self::g($d,'payment_terms')],
            ],
            'cols'=>self::colsMoney(), 'lines'=>$lines,
            'totals'=>array_merge([['label'=>'Subtotal','value'=>$t['subtotal']]], $ch),
            'grandLabel'=>'Grand Total','grand'=>$grand,'words'=>self::words($grand),
            'notes'=>[ ['label'=>'Notes','body'=>self::g($d,'notes','')],
                       ['label'=>'Payment Instructions','body'=>self::g($d,'payment_instructions','')] ],
            'sigs'=>[ ['name'=>self::g($d,'preparer.name'),'role'=>'Prepared by'], ['name'=>'','role'=>'Approved by · Finance'] ],
        ];
    }

    private static function invoice($d): array
    {
        [$lines,$t] = self::linesAndTotals($d);
        $grand = $t['subtotal'] - $t['discount'] + $t['tax'];
        $p = self::party($d,'customer');
        return [
            'title'=>'Invoice','titleSmall'=>false,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Bill To','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Invoice Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'Due Date','v'=>self::date($d,'due_date')],
                ['l'=>'Terms','v'=>self::g($d,'payment_terms')], ['l'=>'Currency','v'=>self::g($d,'currency')],
                ['l'=>'Branch','v'=>self::g($d,'branch.name')], ['l'=>'Salesperson','v'=>self::g($d,'preparer.name')],
            ],
            'cols'=>self::colsMoney(true), 'lines'=>$lines,
            'totals'=>[ ['label'=>'Subtotal','value'=>$t['subtotal']],
                        ['label'=>'Discount','value'=>$t['discount'],'hideZero'=>true],
                        ['label'=>'Tax','value'=>$t['tax'],'hideZero'=>true] ],
            'grandLabel'=>'Grand Total','grand'=>$grand,'words'=>self::words($grand),
            'notes'=>[ ['label'=>'Payment Instructions','body'=>self::g($d,'payment_instructions','')],
                       ['label'=>'Terms','body'=>['Payment due within stated terms.','Interest applies on overdue balances.'],'list'=>true] ],
            'sigs'=>[ ['name'=>'','role'=>'Authorised signature'], ['name'=>self::g($d,'company','CamelotBooks Ltd'),'role'=>'Issuer'] ],
        ];
    }

    private static function delivery($d): array
    {
        $lines = [];
        foreach (data_get($d,'lines',[]) as $l)
            $lines[] = [self::g($l,'item.code'), self::g($l,'item.name'), data_get($l,'description',''),
                        self::qty(data_get($l, 'qty_ordered', 0)), self::qty(data_get($l, 'qty_delivered', 0)),
                        self::g($l,'unit'), self::g($l,'remarks','')];
        $p = self::party($d,'customer');
        return [
            'title'=>'Delivery Note','titleSmall'=>true,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Deliver To','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Delivery Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'Invoice Ref','v'=>self::g($d,'invoice_no')],
                ['l'=>'Vehicle / Reg','v'=>self::g($d,'vehicle')], ['l'=>'Driver','v'=>self::g($d,'driver')],
                ['l'=>'Dispatched By','v'=>self::g($d,'dispatched_by')], ['l'=>'Branch','v'=>self::g($d,'branch.name')],
            ],
            'cols'=>[ ['label'=>'Code','width'=>'12%'], ['label'=>'Item','width'=>'30%'], ['label'=>'Description','width'=>'26%'],
                      ['label'=>'Ordered','width'=>'8%','num'=>true], ['label'=>'Delivered','width'=>'8%','num'=>true],
                      ['label'=>'Unit','width'=>'7%'], ['label'=>'Remarks','width'=>'9%'] ],
            'lines'=>$lines, 'totals'=>[], 'grand'=>null, 'words'=>'',
            'notes'=>[ ['label'=>'Declaration','body'=>'Goods delivered in good order and condition unless noted in Remarks. This note does not constitute a charge.'] ],
            'sigs'=>[ ['name'=>self::g($d,'driver'),'role'=>'Delivered by · Date'], ['name'=>'','role'=>'Received by · Date & stamp'] ],
        ];
    }

    private static function receipt($d): array
    {
        $apps = data_get($d,'applications', []);
        $lines = []; $paid = 0;
        foreach ($apps as $a) {
            $amt = (float) data_get($a,'amount_paid',0); $paid += $amt;
            $lines[] = [self::g($a,'invoice_no'), self::date($a,'invoice_date'), data_get($a,'description',''),
                        self::money(data_get($a,'amount_due',0)), self::money($amt)];
        }
        if (! $apps) { $paid = (float) data_get($d,'amount',0);
            $lines[] = [self::g($d,'applied_to'), self::date($d,'date'), 'Settlement', self::money($paid), self::money($paid)]; }
        $p = self::party($d,'customer');
        return [
            'title'=>'Receipt','titleSmall'=>false,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Received From','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Payment Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'Method','v'=>self::g($d,'method')],
                ['l'=>'Reference','v'=>self::g($d,'reference')], ['l'=>'Received By','v'=>self::g($d,'received_by')],
                ['l'=>'Currency','v'=>self::g($d,'currency')], ['l'=>'Branch','v'=>self::g($d,'branch.name')],
            ],
            'cols'=>[ ['label'=>'Applied To','width'=>'18%'], ['label'=>'Document Date','width'=>'22%'],
                      ['label'=>'Description','width'=>'30%'], ['label'=>'Amount Due','width'=>'15%','num'=>true],
                      ['label'=>'Amount Paid (K)','width'=>'15%','num'=>true,'amt'=>true] ],
            'lines'=>$lines,
            'totals'=>[ ['label'=>'Total Received','value'=>$paid] ],
            'grandLabel'=>'Receipt Total','grand'=>$paid,'words'=>self::words($paid),
            'notes'=>[ ['label'=>'Note','body'=>'This receipt acknowledges payment received and is valid only when issued by the system.'] ],
            'sigs'=>[ ['name'=>self::g($d,'received_by'),'role'=>'Received by'], ['name'=>'','role'=>'Authorised signature & stamp'] ],
        ];
    }

    private static function grn($d): array
    {
        $lines = [];
        foreach (data_get($d,'lines',[]) as $l)
            $lines[] = [self::g($l,'item.code'), self::g($l,'item.name'), data_get($l,'description',''),
                        self::qty(data_get($l, 'qty_ordered', 0)), self::qty(data_get($l, 'qty_received', 0)),
                        self::g($l,'unit'), self::g($l,'condition','')];
        $p = self::party($d,'supplier');
        return [
            'title'=>'Goods Received Note','titleSmall'=>true,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Supplier','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Receipt Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'PO Ref','v'=>self::g($d,'purchase_order_no')],
                ['l'=>'Supplier Invoice','v'=>self::g($d,'supplier_invoice_no')], ['l'=>'Warehouse','v'=>self::g($d,'warehouse')],
                ['l'=>'Received By','v'=>self::g($d,'received_by')], ['l'=>'Inspected By','v'=>self::g($d,'inspected_by')],
            ],
            'cols'=>[ ['label'=>'Code','width'=>'12%'], ['label'=>'Item','width'=>'28%'], ['label'=>'Description','width'=>'24%'],
                      ['label'=>'Ordered','width'=>'9%','num'=>true], ['label'=>'Received','width'=>'9%','num'=>true],
                      ['label'=>'Unit','width'=>'7%'], ['label'=>'Condition','width'=>'11%'] ],
            'lines'=>$lines, 'totals'=>[], 'grand'=>null, 'words'=>'',
            'notes'=>[ ['label'=>'Inspection Notes','body'=>self::g($d,'inspection_notes','')] ],
            'sigs'=>[ ['name'=>self::g($d,'received_by'),'role'=>'Received by'],
                      ['name'=>self::g($d,'inspected_by'),'role'=>'Checked by'],
                      ['name'=>'','role'=>'Store keeper'] ],
        ];
    }

    private static function credit($d): array
    {
        $lines = []; $sub = 0;
        foreach (data_get($d,'lines',[]) as $l) {
            $amt = (float) data_get($l,'amount',0); $sub += $amt;
            $lines[] = [self::g($l,'item.code'), self::g($l,'item.name'), data_get($l,'description',''),
                        self::qty(data_get($l, 'qty', 0)), self::money($amt)];
        }
        $p = self::party($d,'customer');
        return [
            'title'=>'Credit Note','titleSmall'=>true,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Credit To','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Credit Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'Ref Invoice','v'=>self::g($d,'invoice_no')],
                ['l'=>'Reason','v'=>self::g($d,'reason')], ['l'=>'Currency','v'=>self::g($d,'currency')],
                ['l'=>'Branch','v'=>self::g($d,'branch.name')], ['l'=>'Approved By','v'=>self::g($d,'approved_by')],
            ],
            'cols'=>[ ['label'=>'Code','width'=>'12%'], ['label'=>'Item','width'=>'30%'], ['label'=>'Description','width'=>'30%'],
                      ['label'=>'Qty','width'=>'8%','num'=>true], ['label'=>'Credit Amount (K)','width'=>'20%','num'=>true,'amt'=>true] ],
            'lines'=>$lines,
            'totals'=>[ ['label'=>'Subtotal','value'=>$sub] ],
            'grandLabel'=>'Total Credit','grand'=>$sub,'words'=>self::words($sub),
            'notes'=>[ ['label'=>'Reason','body'=>self::g($d,'reason_detail', self::g($d,'reason'))] ],
            'sigs'=>[ ['name'=>self::g($d,'approved_by'),'role'=>'Approved by'], ['name'=>'','role'=>'Authorised signature'] ],
        ];
    }

    private static function po($d): array
    {
        [$lines,$t] = self::linesAndTotals($d);
        $freight = (float) data_get($d,'freight',0);
        $grand = $t['subtotal'] + $freight;
        $p = self::party($d,'supplier');
        return [
            'title'=>'Purchase Order','titleSmall'=>true,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Supplier / Ship To','partyName'=>$p['name'],'partyLines'=>$p['lines'],
            'detailsLabel'=>'Order Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'Required By','v'=>self::date($d,'required_by')],
                ['l'=>'Terms','v'=>self::g($d,'payment_terms')], ['l'=>'Currency','v'=>self::g($d,'currency')],
                ['l'=>'Cost Centre','v'=>self::g($d,'cost_centre')], ['l'=>'Requisition Ref','v'=>self::g($d,'requisition_no')],
            ],
            'cols'=>[ ['label'=>'Code','width'=>'12%'], ['label'=>'Item','width'=>'28%'], ['label'=>'Description','width'=>'28%'],
                      ['label'=>'Qty','width'=>'8%','num'=>true], ['label'=>'Unit Cost','width'=>'11%','num'=>true],
                      ['label'=>'Amount (K)','width'=>'13%','num'=>true,'amt'=>true] ],
            'lines'=>$lines,
            'totals'=>[ ['label'=>'Subtotal','value'=>$t['subtotal']], ['label'=>'Freight','value'=>$freight,'hideZero'=>true] ],
            'grandLabel'=>'Order Total','grand'=>$grand,'words'=>self::words($grand),
            'notes'=>[ ['label'=>'Terms & Conditions','body'=>['Deliver to stated destination; quote PO number on all documents.','Short or damaged deliveries will be rejected.','Payment within stated terms of accepted delivery.'],'list'=>true],
                       ['label'=>'Buyer Notes','body'=>self::g($d,'notes','')] ],
            'sigs'=>[ ['name'=>self::g($d,'preparer.name'),'role'=>'Raised by'], ['name'=>'','role'=>'Authorised signatory & stamp'] ],
        ];
    }

    private static function requisition($d): array
    {
        $lines = []; $est = 0;
        foreach (data_get($d,'lines',[]) as $l) {
            $c = (float) data_get($l,'estimated_cost',0); $est += $c;
            $lines[] = [self::g($l,'item.code'), self::g($l,'item.name'), data_get($l,'description',''),
                        self::qty(data_get($l, 'qty', 0)), self::money($c), self::g($l,'remarks','')];
        }
        return [
            'title'=>'Purchase Requisition','titleSmall'=>true,'number'=>'№ '.self::g($d,'number'),
            'partyLabel'=>'Requested By','partyName'=>self::g($d,'requester.name'),
            'partyLines'=>['Department: '.self::g($d,'department'), 'Cost Centre: '.self::g($d,'cost_centre')],
            'detailsLabel'=>'Requisition Details',
            'details'=>[
                ['l'=>'Date','v'=>self::date($d,'date')], ['l'=>'Needed By','v'=>self::date($d,'needed_by')],
                ['l'=>'Priority','v'=>self::g($d,'priority'),'chip'=>true], ['l'=>'Budget Line','v'=>self::g($d,'budget_line')],
                ['l'=>'Suggested Supplier','v'=>self::g($d,'suggested_supplier')], ['l'=>'Status','v'=>self::g($d,'status')],
            ],
            'cols'=>[ ['label'=>'Code','width'=>'12%'], ['label'=>'Item','width'=>'28%'], ['label'=>'Description','width'=>'28%'],
                      ['label'=>'Qty','width'=>'8%','num'=>true], ['label'=>'Est. Cost','width'=>'12%','num'=>true],
                      ['label'=>'Remarks','width'=>'12%'] ],
            'lines'=>$lines,
            'totals'=>[ ['label'=>'Estimated Total','value'=>$est] ],
            'grandLabel'=>'Request Total','grand'=>$est,'wordsLabel'=>'Justification','words'=>self::g($d,'justification',''),
            'sigs'=>[ ['name'=>self::g($d,'requester.name'),'role'=>'Requested by'], ['name'=>'','role'=>'Department head'],
                      ['name'=>'','role'=>'Finance'], ['name'=>'','role'=>'Management'] ],
        ];
    }
}