<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NumberingSequence extends Model
{
    protected $fillable = [
        'company_id',
        'document_type',
        'prefix',
        'padding_width',
        'next_number',
        'reset_policy',
        'is_active',
    ];

    protected $casts = [
        'padding_width' => 'integer',
        'next_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function documentTypeLabels(): array
    {
        return [
            'journal_entry' => 'Journal Entry',
            'invoice' => 'Invoice',
            'bill' => 'Bill',
            'credit_note' => 'Credit Note',
            'vendor_credit' => 'Vendor Credit',
            'customer_receipt' => 'Customer Receipt',
            'vendor_payment' => 'Vendor Payment',
            'purchase_requisition' => 'Purchase Requisition',
            'purchase_order' => 'Purchase Order',
            'goods_received_note' => 'Goods Received Note',
            'payroll_run' => 'Payroll Run',
            'employee_payment' => 'Employee Payment',
            'depreciation_run' => 'Depreciation Run',
            'fixed_asset' => 'Fixed Asset',
            'stock_adjustment' => 'Stock Adjustment',
            'stock_transfer' => 'Stock Transfer',
            'pos_sale' => 'POS Sale',
            'pos_settlement' => 'POS Settlement',
        ];
    }

    public static function defaultSequences(): array
    {
        return [
            ['document_type' => 'journal_entry', 'prefix' => 'JE-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'invoice', 'prefix' => 'INV-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'bill', 'prefix' => 'BILL-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'credit_note', 'prefix' => 'CN-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'vendor_credit', 'prefix' => 'VCN-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'customer_receipt', 'prefix' => 'REC-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'vendor_payment', 'prefix' => 'PAY-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'purchase_requisition', 'prefix' => 'REQ-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'purchase_order', 'prefix' => 'PO-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'goods_received_note', 'prefix' => 'GRN-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'payroll_run', 'prefix' => 'PR-', 'padding_width' => 3, 'reset_policy' => 'monthly'],
            ['document_type' => 'employee_payment', 'prefix' => 'EP-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'depreciation_run', 'prefix' => 'DEPR-', 'padding_width' => 4, 'reset_policy' => 'annually'],
            ['document_type' => 'fixed_asset', 'prefix' => 'ASSET-', 'padding_width' => 4, 'reset_policy' => 'never'],
            ['document_type' => 'stock_adjustment', 'prefix' => 'ADJ-', 'padding_width' => 4, 'reset_policy' => 'never'],
            ['document_type' => 'stock_transfer', 'prefix' => 'TRF-', 'padding_width' => 4, 'reset_policy' => 'never'],
            ['document_type' => 'pos_sale', 'prefix' => 'POS-', 'padding_width' => 5, 'reset_policy' => 'never'],
            ['document_type' => 'pos_settlement', 'prefix' => 'STL-', 'padding_width' => 5, 'reset_policy' => 'never'],
        ];
    }
}
