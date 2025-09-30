<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Recibo</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            margin: 0;
            padding: 0;
        }
        .recibo {
            width: 100%;
            text-align: center;
        }
        .linea {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }
        .totales {
            text-align: right;
            margin-top: 10px;
        }
        .item {
            text-align: left;
        }
    </style>
</head>
<body>
    <div class="recibo">
        <h3>{{ $venta->store->store_name ?? 'Mi Tienda' }}</h3>
        <p><strong>Fecha:</strong> {{ $venta->sale_date->format('d/m/Y H:i') }}</p>
        <p><strong>Cliente:</strong> {{ $venta->customer->name ?? 'Consumidor Final' }}</p>

        <div class="linea"></div>

        {{-- Productos --}}
        @foreach ($venta->saleDetails as $item)
            <p class="item">
                {{ $item->productType->name }} x{{ $item->quantity }}
                <span style="float:right;">${{ number_format($item->subtotal, 2) }}</span>
            </p>
        @endforeach

        <div class="linea"></div>

        {{-- Totales --}}
        <p class="totales"><strong>Total: ${{ number_format($venta->total_amount, 2) }}</strong></p>

        @if ($venta->tax_amount > 0)
            <p class="totales">IVA: ${{ number_format($venta->tax_amount, 2) }}</p>
        @endif

        @if ($venta->discount_amount > 0)
            <p class="totales">Descuento: -${{ number_format($venta->discount_amount, 2) }}</p>
        @endif

        <div class="linea"></div>

        <p>¡Gracias por su compra!</p>
    </div>
</body>
</html>
