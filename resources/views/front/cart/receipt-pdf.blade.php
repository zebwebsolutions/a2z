<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Receipt #{{ $order->id }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        .header { margin-bottom: 18px; }
        .title { font-size: 20px; font-weight: 700; margin: 0; }
        .sub { color: #4b5563; margin-top: 3px; }
        .meta { margin: 14px 0; }
        .meta-row { margin-bottom: 4px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: left; }
        th { background: #f3f4f6; font-weight: 700; }
        .num { text-align: right; }
        .totals { margin-top: 12px; width: 40%; margin-left: auto; }
        .totals-row { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .bold { font-weight: 700; }
        .footer { margin-top: 20px; color: #4b5563; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        <p class="title">A to Z Electronics &amp; Repairing</p>
        <p class="sub">Order Receipt</p>
    </div>

    <div class="meta">
        <div class="meta-row"><strong>Order #:</strong> {{ $order->id }}</div>
        <div class="meta-row"><strong>Date:</strong> {{ optional($order->created_at)->format('d M Y, h:i A') }}</div>
        <div class="meta-row"><strong>Customer:</strong> {{ $order->customer_name ?: 'Guest' }}</div>
        <div class="meta-row"><strong>Phone:</strong> {{ $order->customer_phone_e164 ?: ($order->customer_phone ?: '-') }}</div>
        <div class="meta-row"><strong>Address:</strong> {{ $order->customer_address ?: '-' }}</div>
        <div class="meta-row"><strong>Payment:</strong> {{ ucfirst($order->payment_method ?? 'cash') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Item</th>
                <th class="num">Qty</th>
                <th class="num">Price (KWD)</th>
                <th class="num">Subtotal (KWD)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                @php
                    $subtotal = $item->subtotal ?? ($item->price * $item->quantity);
                @endphp
                <tr>
                    <td>{{ $item->product->name ?? ('Product #' . $item->product_id) }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format((float) $item->price, 2) }}</td>
                    <td class="num">{{ number_format((float) $subtotal, 2) }}</td>
                </tr>
            @endforeach
            @foreach($order->sparePartItems as $item)
                @php
                    $subtotal = $item->price * $item->quantity;
                @endphp
                <tr>
                    <td>{{ $item->sparePart->name ?? ('Spare part #' . $item->spare_part_id) }}</td>
                    <td class="num">{{ $item->quantity }}</td>
                    <td class="num">{{ number_format((float) $item->price, 2) }}</td>
                    <td class="num">{{ number_format((float) $subtotal, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    @php
        $discount = (float) ($order->discount ?? 0);
        $total = (float) ($order->total ?? 0);
        $subtotal = $order->items->sum(function ($item) {
            return (float) ($item->subtotal ?? ($item->price * $item->quantity));
        }) + $order->sparePartItems->sum(function ($item) {
            return (float) ($item->price * $item->quantity);
        });
    @endphp

    <div class="totals">
        <div class="totals-row">
            <span>Subtotal:</span>
            <span>KWD {{ number_format($subtotal, 2) }}</span>
        </div>
        <div class="totals-row">
            <span>Discount:</span>
            <span>KWD {{ number_format($discount, 2) }}</span>
        </div>
        <div class="totals-row bold">
            <span>Total:</span>
            <span>KWD {{ number_format($total, 2) }}</span>
        </div>
    </div>

    <div class="footer">
        Thank you for your purchase.
    </div>
</body>
</html>
