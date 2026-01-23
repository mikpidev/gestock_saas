<!DOCTYPE html>
<html>
@php use SimpleSoftwareIO\QrCode\Facades\QrCode; @endphp

<head>
    <meta charset="utf-8">
    <title>Recibo</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            margin: 0;
            padding: 0;
        }

        .recibo {
            width: 100%;
            text-align: left;
        }

        .recibo img.qr {
            display: inline-block;
            text-align: center;
            margin: 0 auto;
            width: 100px;
            height: 100px;
        }
        


        .linea {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .totales,
        .info {
            text-align: right;
            margin-top: 5px;
        }

        .item {
            text-align: left;
        }

        .small {
            font-size: 9px;
        }
    </style>
</head>

<body>

    <div class="recibo">


        <!-- ENCABEZADO -->
        <div style="width:175px; height:100px; overflow:hidden; margin: 0 auto; text-align:center;">
            <img src="{{ public_path('Logo_recortado.png') }}" style="width:100px; height:auto; display:inline-block;">
        </div>
        <h3 style="margin:0; padding:0;">{{ $venta->store->store_name ?? 'Mi Tienda' }}</h3>
        <p class="small" style="margin:0; padding:0;">
            Dirección: {{ $venta->store->address ?? '' }}<br>
            Tel: {{ $venta->store->taxInfo->telefono ?? '' }}<br>
            NIT: {{ $venta->store->taxInfo->nit ?? '' }} | NRC: {{ $venta->store->taxInfo->nrc ?? '' }}
        </p>


        <!-- DATOS DTE -->
        <p class="small">
            <strong>Tipo DTE:</strong> {{ $venta->tipoDte->nombre ?? 'TICKET' }}<br>
            <strong>No. Control:</strong> {{ $venta->numero_control ?? 'N/A' }}<br>
            <strong>Código Generación:</strong> {{ $venta->codigo_generacion ?? 'N/A' }}<br>
            <strong>Sello de Recibido:</strong> {{ $dteResponse->sello_recibido ?? 'N/A' }}

        </p>

        <div class="linea"></div>

        <!-- INFO VENTA -->
        <p class="small">
            <strong>Fecha:</strong> {{ $venta->sale_date->format('d/m/Y H:i') }}<br>
            <strong>Atendió:</strong> {{ $venta->user->name ?? 'Cajero' }}
        </p>

        <div class="linea"></div>

        <!-- CLIENTE -->
        <p class="small">
            <strong>Cliente:</strong> {{ $venta->customer->name ?? 'Consumidor Final' }}<br>
            @if($venta->customer)
            @if($venta->customer->numDocumento)
            <strong>Doc:</strong> {{ $venta->customer->numDocumento }}<br>
            @endif
            @if($venta->customer->nrc)
            <strong>NRC:</strong> {{ $venta->customer->nrc }}<br>
            @endif
            @endif
        </p>

        <div class="linea"></div>

        <!-- DETALLE PRODUCTOS -->
        @foreach ($venta->details as $item)
        <p class="item">
            {{ $item->productType->name }} x{{ $item->quantity }}
            <span style="float:right;">${{ number_format($item->subtotal, 2) }}</span>
        </p>
        @endforeach

        <div class="linea"></div>

        <!-- TOTALES -->
        @if ($venta->total_exenta > 0)
        <p class="totales">Exento: ${{ number_format($venta->total_exenta, 2) }}</p>
        @endif
        @if ($venta->total_no_gravado > 0)
        <p class="totales">No Gravado: ${{ number_format($venta->total_no_gravado, 2) }}</p>
        @endif
        @if ($venta->total_amount > 0)
        <p class="totales">Subtotal: ${{ number_format($venta->total_amount, 2) }}</p>
        @endif
        @if ($venta->tax_amount > 0)
        <p class="totales">IVA 13%: ${{ number_format($venta->tax_amount, 2) }}</p>
        @endif
        @if ($venta->discount_amount > 0)
        <p class="totales">Descuento: -${{ number_format($venta->discount_amount, 2) }}</p>
        @endif
        <p class="totales">Total: ${{ number_format($venta->net_amount, 2) }}</p>
 

        <div class="linea"></div>

        <!-- QR Centrado-->

        <div style="width:175px; height:100px; overflow:hidden; margin: 0 auto; text-align:center;">
            <img src="data:image/png;base64,{{ $qrImage }}" style="width:100px; height:auto; display:inline-block;">
        </div>
        <!-- FOOTER -->
        <p class="small" style="text-align:center;">
            Documento generado electrónicamente<br>
            ¡Gracias por su compra!
        </p>

    </div>

    <script>
        let urlRecibo = '';

        function mostrarModalImpresion(url) {
            urlRecibo = url;
            document.getElementById('modalImprimir').style.display = 'flex';
        }

        function abrirRecibo() {
            window.open(urlRecibo, '_blank');
            cerrarModal();
        }

        function cerrarModal() {
            document.getElementById('modalImprimir').style.display = 'none';
        }
    </script>

</body>

</html>