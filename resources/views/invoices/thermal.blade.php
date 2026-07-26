<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        @page { size: 80mm auto; margin: 0; }
        * { box-sizing: border-box; }
        body {
            width: 80mm;
            margin: 0 auto;
            padding: 8px;
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            color: #000;
        }
        .center { text-align: center; }
        .divider { border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { padding: 2px 0; vertical-align: top; }
        .right { text-align: right; }
        .totals-row td { font-weight: bold; font-size: 13px; }
        .print-btn { display: block; width: 100%; margin-top: 10px; padding: 8px; font-size: 12px; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="center">
        @if ($setting->logo_url)
            <img src="{{ $setting->logo_url }}" alt="{{ $setting->company_name }}" style="max-width: 48px; max-height: 48px;"><br>
        @endif
        <strong>{{ $setting->company_name }}</strong><br>
        {{ $sale->branch->name }}<br>
        {{ $sale->branch->address }}<br>
        {{ $sale->branch->phone }}
    </div>
    <div class="divider"></div>
    <div>
        Invoice: {{ $sale->invoice_number }}<br>
        Date: {{ $sale->sale_date->format('Y-m-d H:i') }}<br>
        Customer: {{ $sale->customer->name }}<br>
        Cashier: {{ $sale->cashier->name ?? '—' }}
    </div>
    <div class="divider"></div>
    <table>
        @foreach ($sale->items as $item)
            <tr>
                <td colspan="2">{{ $item->product->name }}</td>
            </tr>
            <tr>
                <td>{{ $item->quantity }} x {{ $setting->currency_symbol }}{{ number_format($item->unit_price, 2) }}</td>
                <td class="right">{{ $setting->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <div class="divider"></div>
    <table>
        <tr class="totals-row">
            <td>TOTAL</td>
            <td class="right">{{ $setting->currency_symbol }}{{ number_format($sale->total_amount, 2) }}</td>
        </tr>
    </table>
    <div class="divider"></div>
    <div class="center">Thank you for your purchase!</div>

    <button type="button" class="print-btn no-print" onclick="window.print()">Print</button>

    <script>
        window.onload = function () {
            window.print();
        };
    </script>
</body>
</html>
