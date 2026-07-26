<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EmailTemplate extends Model
{
    protected $fillable = [
        'company_id',
        'event_type',
        'subject',
        'body',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public static function eventLabels(): array
    {
        return [
            'invoice_sent' => 'Invoice Sent',
            'payment_received' => 'Payment Received',
            'approval_requested' => 'Approval Requested',
            'low_stock_alert' => 'Low Stock Alert',
            'overdue_reminder' => 'Overdue Invoice/Bill Reminder',
            'recurring_generated' => 'Recurring Invoice/Bill Generated',
            'payslip_sent' => 'Payslip Sent',
        ];
    }

    public static function defaultTemplates(): array
    {
        return [
            'invoice_sent' => [
                'subject' => 'Invoice {{invoice_number}} from {{company_name}}',
                'body' => "Dear {{customer_name}},\n\nPlease find attached invoice {{invoice_number}} for {{total_amount}}.\n\nDue date: {{due_date}}\n\nThank you for your business.\n\n{{company_name}}",
            ],
            'payment_received' => [
                'subject' => 'Payment Received - {{receipt_number}}',
                'body' => "Dear {{customer_name}},\n\nWe have received your payment of {{amount}}.\nReceipt number: {{receipt_number}}\n\nThank you.\n\n{{company_name}}",
            ],
            'approval_requested' => [
                'subject' => 'Approval Required: {{document_type}} {{document_number}}',
                'body' => "A {{document_type}} ({{document_number}}) requires your approval.\n\nAmount: {{amount}}\nSubmitted by: {{submitted_by}}\n\nPlease review and approve/reject.\n\n{{company_name}}",
            ],
            'low_stock_alert' => [
                'subject' => 'Low Stock Alert: {{product_name}}',
                'body' => "The following item is running low on stock:\n\nProduct: {{product_name}}\nCurrent stock: {{current_stock}}\nMinimum level: {{minimum_level}}\n\nPlease reorder.\n\n{{company_name}}",
            ],
            'overdue_reminder' => [
                'subject' => 'Payment Overdue: {{document_number}}',
                'body' => "Dear {{contact_name}},\n\nThe following {{document_type}} is now overdue:\n\nNumber: {{document_number}}\nAmount: {{amount}}\nDue date: {{due_date}}\nDays overdue: {{days_overdue}}\n\nPlease arrange payment at your earliest convenience.\n\n{{company_name}}",
            ],
            'recurring_generated' => [
                'subject' => 'Recurring {{document_type}} Generated: {{document_number}}',
                'body' => "A recurring {{document_type}} has been automatically generated:\n\nNumber: {{document_number}}\nAmount: {{amount}}\n\nThis was generated from template: {{template_name}}\n\n{{company_name}}",
            ],
            'payslip_sent' => [
                'subject' => 'Your Payslip - {{period}}',
                'body' => "Dear {{employee_name}},\n\nPlease find attached your payslip for {{period}}.\n\nThe PDF is password-protected. Your password was provided to you separately.\n\nIf you have any questions, please contact the payroll department.\n\nRegards,\n{{company_name}}",
            ],
        ];
    }
}
