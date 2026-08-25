<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BRR-{{ $returnable->brr_number }} – Bottle Return Receipt</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', system-ui, sans-serif; font-size: 13px; color: #1a1a1a; background: #fff; }
        @page { size: A4 portrait; margin: 12mm; }
        .receipt { max-width: 80mm; margin: 0 auto; }
        .header { text-align: center; border-bottom: 2px solid #11454B; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { font-size: 18px; font-weight: 700; color: #11454B; letter-spacing: -0.02em; }
        .header .subtitle { font-size: 11px; color: #5F7476; margin-top: 4px; text-transform: uppercase; letter-spacing: 0.08em; }
        .brr-number { text-align: center; font-size: 22px; font-weight: 700; color: #128F8E; margin: 12px 0; letter-spacing: 0.05em; }
        .meta { display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-bottom: 16px; }
        .meta-item { }
        .meta-label { font-size: 10px; color: #5F7476; text-transform: uppercase; letter-spacing: 0.06em; font-weight: 600; }
        .meta-value { font-size: 13px; font-weight: 600; color: #1a1a1a; margin-top: 2px; }
        .summary { border: 1px solid #DCEAEA; border-radius: 8px; padding: 12px; margin-bottom: 16px; background: #F4F8F8; }
        .summary-row { display: flex; justify-content: space-between; padding: 4px 0; }
        .summary-row.total { border-top: 1px solid #DCEAEA; margin-top: 4px; padding-top: 8px; font-weight: 700; font-size: 15px; color: #11454B; }
        .footer { text-align: center; font-size: 10px; color: #5F7476; border-top: 1px solid #DCEAEA; padding-top: 12px; margin-top: 16px; }
        .status { text-align: center; padding: 6px 16px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; display: inline-block; margin-bottom: 12px; }
        .status-pending { background: #FEF3C7; color: #92400E; }
        .status-redeemed { background: #D1FAE5; color: #065F46; }
        .status-expired { background: #F3F4F6; color: #6B7280; }
        .status-voided { background: #FEE2E2; color: #991B1B; }
        .status-partial { background: #DBEAFE; color: #1E40AF; }
        .barcode { text-align: center; margin: 12px 0; font-family: monospace; font-size: 18px; letter-spacing: 0.2em; color: #1a1a1a; }
        .brand { font-size: 10px; color: #8AA5A7; margin-top: 4px; }
        @media print { body { background: #fff; } .receipt { margin: 0; } }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <h1>Bottle Return Receipt</h1>
            <div class="subtitle">Container Return & Credit Issuance</div>
        </div>

        <div class="brr-number">BRR-{{ $returnable->brr_number }}</div>

        <div style="text-align:center">
            @php
                $statusClass = match($returnable->status) {
                    'pending' => 'status-pending',
                    'partially_redeemed' => 'status-partial',
                    'redeemed' => 'status-redeemed',
                    'expired' => 'status-expired',
                    'voided' => 'status-voided',
                    default => 'status-pending',
                };
            @endphp
            <span class="status {{ $statusClass }}">{{ $returnable->status_label }}</span>
        </div>

        <div class="meta">
            <div class="meta-item">
                <div class="meta-label">Date</div>
                <div class="meta-value">{{ $returnable->created_at->format('d M Y H:i') }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Intake #</div>
                <div class="meta-value">{{ $returnable->intake_number }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Product</div>
                <div class="meta-value">{{ $returnable->product?->name ?? '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Customer</div>
                <div class="meta-value">{{ $returnable->customer?->name ?? 'Walk-in' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Branch</div>
                <div class="meta-value">{{ $returnable->branch?->name ?? '—' }}</div>
            </div>
            <div class="meta-item">
                <div class="meta-label">Expiry</div>
                <div class="meta-value">{{ $returnable->expiry_date?->format('d M Y') ?? 'No limit' }}</div>
            </div>
        </div>

        <div class="summary">
            <div class="summary-row">
                <span>Bottle Count</span>
                <span style="font-weight:600">{{ $returnable->bottle_count }}</span>
            </div>
            <div class="summary-row">
                <span>Value Each</span>
                <span style="font-weight:600">{{ format_money($returnable->value_each) }}</span>
            </div>
            <div class="summary-row total">
                <span>Total Credit Issued</span>
                <span>{{ format_money($returnable->credit_amount) }}</span>
            </div>
            @if($returnable->redeemed_qty > 0)
                <div class="summary-row">
                    <span>Redeemed</span>
                    <span>{{ $returnable->redeemed_qty }} / {{ $returnable->quantity }}</span>
                </div>
            @endif
        </div>

        <div class="barcode">||||| {{ $returnable->brr_number }} |||||</div>

        <div class="footer">
            <p>This receipt entitles the holder to bottle/container credit.</p>
            <p>Credits are redeemable at any CamelotBooks POS terminal.</p>
            @if($returnable->expiry_date)
                <p style="margin-top:4px;font-weight:600">Credit expires: {{ $returnable->expiry_date->format('d M Y') }}</p>
            @endif
            <p style="margin-top:8px">© {{ date('Y') }} {{ $returnable->company?->name ?? 'CamelotBooks' }}</p>
        </div>
    </div>

    <script>window.onload = () => window.print();</script>
</body>
</html>
