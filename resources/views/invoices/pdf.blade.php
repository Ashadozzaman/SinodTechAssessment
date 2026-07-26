<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $sale->invoice_number }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #111; }
        h1 { font-size: 20px; margin-bottom: 0; }
        .meta { margin-bottom: 24px; color: #555; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { padding: 6px 8px; border-bottom: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .totals { margin-top: 16px; text-align: right; font-size: 14px; }
    </style>
</head>
<body>
    <table style="width: 100%; margin-bottom: 12px;">
        <tr>
            <td style="width: 48px; vertical-align: middle;">
                @if ($setting->logo_file_path)
                    <img src="{{ $setting->logo_file_path }}" alt="{{ $setting->company_name }}" style="max-width: 48px; max-height: 48px;">
                @endif
            </td>
            <td style="vertical-align: middle;">
                <strong style="font-size: 16px;">{{ $setting->company_name }}</strong>
            </td>
        </tr>
    </table>

    <h1>Invoice {{ $sale->invoice_number }}</h1>
    <div class="meta">{{ $sale->sale_date->format('Y-m-d H:i') }}</div>

    <table style="margin-bottom: 24px;">
        <tr>
            <td><strong>Branch</strong><br>{{ $sale->branch->name }}<br>{{ $sale->branch->address }}</td>
            <td><strong>Customer</strong><br>{{ $sale->customer->name }}<br>{{ $sale->customer->phone }}</td>
        </tr>
    </table>

    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit Price</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sale->items as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">{{ $setting->currency_symbol }}{{ number_format($item->unit_price, 2) }}</td>
                    <td class="text-right">{{ $setting->currency_symbol }}{{ number_format($item->subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">Total: {{ $setting->currency_symbol }}{{ number_format($sale->total_amount, 2) }}</div>
</body>
</html>
