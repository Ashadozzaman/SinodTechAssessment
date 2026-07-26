<!DOCTYPE html>
<html>
<head><meta charset="utf-8"></head>
<body style="font-family: sans-serif; font-size: 14px; color: #111;">
    <p>Hi {{ $sale->customer->name }},</p>
    <p>Thank you for your purchase at {{ $sale->branch->name }}. Your invoice <strong>{{ $sale->invoice_number }}</strong> is attached.</p>
    <p>Total: {{ number_format($sale->total_amount, 2) }}</p>
</body>
</html>
