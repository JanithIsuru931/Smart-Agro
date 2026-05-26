<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Supplier Receipt</title>
    <style>
        @page {
            margin: 28px 30px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            color: #1f2937;
            line-height: 1.5;
        }

        .header {
            border-bottom: 2px solid #0f766e;
            padding-bottom: 16px;
            margin-bottom: 24px;
        }

        .brand {
            font-size: 22px;
            font-weight: 700;
            color: #0f766e;
            margin-bottom: 4px;
        }

        .muted {
            color: #6b7280;
        }

        .receipt-meta {
            width: 100%;
            margin-bottom: 18px;
        }

        .receipt-meta td {
            vertical-align: top;
        }

        .section-title {
            font-size: 13px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            color: #0f766e;
            margin-bottom: 8px;
        }

        .box {
            border: 1px solid #d1d5db;
            border-radius: 10px;
            padding: 14px 16px;
            margin-bottom: 18px;
        }

        .details {
            width: 100%;
            border-collapse: collapse;
        }

        .details td {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }

        .details td:first-child {
            color: #6b7280;
            width: 46%;
        }

        .details td:last-child {
            text-align: right;
            font-weight: 600;
        }

        .total {
            font-size: 16px;
            font-weight: 700;
            color: #111827;
        }

        .footer {
            margin-top: 24px;
            font-size: 11px;
            color: #6b7280;
            text-align: center;
        }
    </style>
</head>
<body>
    <table class="receipt-meta">
        <tr>
            <td>
                <div class="brand">{{ config('app.name') }}</div>
                <div class="muted">Supplier Purchase Receipt</div>
            </td>
            <td style="text-align:right;">
                <div><strong>Receipt No:</strong> SPR-{{ str_pad((string) $purchase->id, 5, '0', STR_PAD_LEFT) }}</div>
                <div><strong>Purchase Date:</strong> {{ $purchase->purchase_date->format('Y-m-d') }}</div>
                <div><strong>Generated:</strong> {{ now()->format('Y-m-d H:i') }}</div>
            </td>
        </tr>
    </table>

    <div class="box">
        <div class="section-title">Supplier Details</div>
        <table class="details">
            <tr>
                <td>Supplier Name</td>
                <td>{{ $purchase->supplier->name }}</td>
            </tr>
            <tr>
                <td>Phone</td>
                <td>{{ $purchase->supplier->phone }}</td>
            </tr>
            <tr>
                <td>Location</td>
                <td>{{ $purchase->supplier->location }}</td>
            </tr>
        </table>
    </div>

    <div class="box">
        <div class="section-title">Purchase Summary</div>
        <table class="details">
            <tr>
                <td>Quantity Purchased</td>
                <td>{{ number_format($purchase->quantity) }} units</td>
            </tr>
            <tr>
                <td>Unit Price</td>
                <td>LKR {{ number_format($purchase->unit_price, 2) }}</td>
            </tr>
            <tr>
                <td>Total Paid</td>
                <td class="total">LKR {{ number_format($purchase->total_paid, 2) }}</td>
            </tr>
        </table>
    </div>

    @if ($purchase->notes)
        <div class="box">
            <div class="section-title">Notes</div>
            <div>{{ $purchase->notes }}</div>
        </div>
    @endif

    <div class="footer">
        This receipt confirms that payment was recorded for the supplier purchase above.
    </div>
</body>
</html>