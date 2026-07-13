@php
    $subtotal = $order->items->sum(fn ($item) => $item->price * $item->quantity);
    $deliveryCharge = max((float) $order->total - $subtotal, 0);
@endphp

<div style="font-family: Arial, sans-serif; color: #111827; line-height: 1.5;">
    <h1 style="margin: 0 0 12px; color: #2563eb;">Thank you for your order</h1>

    <p>Hi {{ $order->customer_name }},</p>

    <p>
        We received your order for the following products/items at a2z mobiles and repairing kuwait. We will contact you shortly for delivery confirmation.
    </p>

    <p dir="rtl" style="text-align: right;">
        لقد استلمنا طلبك للمنتجات/العناصر التالية لدى A2Z Mobiles and Repairing Kuwait. سنتواصل معك قريباً لتأكيد التوصيل.
    </p>

    <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
        <thead>
            <tr>
                <th align="left" style="border-bottom: 1px solid #e5e7eb; padding: 8px;">Product</th>
                <th align="left" style="border-bottom: 1px solid #e5e7eb; padding: 8px;">Qty</th>
                <th align="left" style="border-bottom: 1px solid #e5e7eb; padding: 8px;">Price</th>
                <th align="left" style="border-bottom: 1px solid #e5e7eb; padding: 8px;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $item)
                <tr>
                    <td style="border-bottom: 1px solid #f3f4f6; padding: 8px;">{{ $item->product?->name ?? 'Product' }}</td>
                    <td style="border-bottom: 1px solid #f3f4f6; padding: 8px;">{{ $item->quantity }}</td>
                    <td style="border-bottom: 1px solid #f3f4f6; padding: 8px;">{{ number_format($item->price, 3) }} KWD</td>
                    <td style="border-bottom: 1px solid #f3f4f6; padding: 8px;">{{ number_format($item->price * $item->quantity, 3) }} KWD</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <p>Items subtotal: <strong>{{ number_format($subtotal, 3) }} KWD</strong></p>
    <p>Delivery: <strong>{{ number_format($deliveryCharge, 3) }} KWD</strong></p>
    <p>Total: <strong>{{ number_format($order->total, 3) }} KWD</strong></p>

</div>
