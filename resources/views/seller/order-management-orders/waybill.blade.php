<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Vendo Shipping Label · {{ $order->number }}</title>
    @vite('resources/css/seller/shipping-label.css')
</head>
<body>
    <div class="print-toolbar">
        <span>Vendo shipping label · 4 × 6 in</span>
        <button type="button" onclick="window.print()">Print label</button>
    </div>

    <main class="shipping-label" aria-label="Shipping label for {{ $order->number }}">
        <header class="label-header">
            <div>
                <strong class="brand">{{ $order->logisticsCenter->business_name }}</strong>
                <span class="brand-subtitle">SHIPPING LABEL · VENDO NETWORK</span>
            </div>
            <div class="service-block">
                <strong>{{ strtoupper($order->payment_mode ?? 'COD') }}</strong>
                <span>{{ $order->number }}</span>
            </div>
        </header>

        <div class="route-block">
            <div>
                <span class="field-label">PICKUP HUB</span>
                <strong>{{ $order->logisticsCenter->business_name }}</strong>
                <small>{{ $order->logisticsCenter->municipality }}, {{ $order->logisticsCenter->province }}</small>
            </div>
            <span class="route-arrow" aria-hidden="true">→</span>
            <div>
                <span class="field-label">DESTINATION {{ $order->destinationLogisticsCenter ? 'HUB' : 'AREA' }}</span>
                <strong>{{ $order->destinationLogisticsCenter?->business_name ?? ($order->shipping_city ?: 'Destination pending') }}</strong>
                <small>{{ $order->destinationLogisticsCenter
                    ? implode(', ', array_filter([$order->destinationLogisticsCenter->municipality, $order->destinationLogisticsCenter->province]))
                    : ($order->shipping_province ? $order->shipping_province.' · Hub assignment pending' : 'Hub assignment pending') }}</small>
            </div>
        </div>

        <section class="tracking-block" aria-label="Parcel tracking code">
            <span class="field-label">VENDO PARCEL TRACKING</span>
            <img class="barcode" src="{{ $labelCodes['barcodeDataUri'] }}" alt="Code 128 barcode for {{ $order->tracking_number }}">
            <strong class="tracking-number">{{ $order->tracking_number }}</strong>
        </section>

        <section class="address-block">
            <div class="address-tag">TO<br>BUYER</div>
            <div class="address-body">
                <div class="address-name">{{ $order->buyer?->name ?? 'Buyer unavailable' }}</div>
                <div class="address-detail">{{ $order->shipping_address }}</div>
                @if ($order->buyer?->phone_number)
                    <div class="address-phone">{{ $order->buyer->phone_number }}</div>
                @endif
            </div>
        </section>

        <section class="address-block">
            <div class="address-tag">FROM<br>SELLER</div>
            <div class="address-body">
                <div class="address-name">{{ $seller->sellerDetail?->business_name ?: $seller->name }}</div>
                <div class="address-detail">{{ implode(', ', array_filter([
                    $seller->sellerDetail?->house_no,
                    $seller->sellerDetail?->street,
                    $seller->sellerDetail?->barangay,
                    $seller->sellerDetail?->municipality,
                    $seller->sellerDetail?->province,
                    $seller->sellerDetail?->zip_code,
                ])) }}</div>
                @if ($seller->phone_number)
                    <div class="address-phone">{{ $seller->phone_number }}</div>
                @endif
            </div>
        </section>

        <footer class="label-footer">
            <div class="parcel-details">
                <div><span class="field-label">ITEM QTY</span><strong>{{ $order->items->sum('quantity') }}</strong></div>
                <div><span class="field-label">PAYMENT</span><strong>{{ strtoupper($order->payment_mode ?? 'COD') }}</strong></div>
                <div><span class="field-label">{{ strtolower($order->payment_mode ?? 'cod') === 'cod' ? 'COLLECT ON DELIVERY' : 'ORDER TOTAL' }}</span><strong>₱{{ number_format((float) $order->total_amount + (float) $order->shipping_fee, 2) }}</strong></div>
                <small>One label per parcel · Keep barcode and QR flat and readable.</small>
            </div>
            <div class="qr-block">
                <img src="{{ $labelCodes['qrDataUri'] }}" alt="QR code for {{ $order->tracking_number }}">
                <span>SCAN PARCEL</span>
            </div>
        </footer>
    </main>
</body>
</html>
