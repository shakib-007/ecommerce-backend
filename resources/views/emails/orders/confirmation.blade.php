{{-- resources/views/emails/orders/confirmation.blade.php --}}
<x-mail::message>
# Order Confirmed!

Hi {{ $order->user->name }},

Thank you for your order. We've received it and are processing it now.

**Order Number:** {{ $order->order_number }}
**Order Date:** {{ $order->created_at->format('d M Y') }}

<x-mail::table>
| Product | Qty | Price |
|:--------|:----|------:|
@foreach ($order->items as $item)
| {{ $item->variant_snapshot['product_name'] }} | {{ $item->qty }} | ৳{{ number_format($item->line_total, 2) }} |
@endforeach
</x-mail::table>

| | |
|:--|--:|
| Subtotal | ৳{{ number_format($order->subtotal, 2) }} |
| Discount | -৳{{ number_format($order->discount_total, 2) }} |
| Shipping | ৳{{ number_format($order->shipping_fee, 2) }} |
| **Total** | **৳{{ number_format($order->total, 2) }}** |

**Shipping To:**
{{ $order->address->line1 }}, {{ $order->address->city }}, {{ $order->address->country }}

We'll notify you when your order ships.

Thanks,
{{ config('app.name') }}
</x-mail::message>