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



        <div class="linea"></div>

        <!-- INFO VENTA -->
        <p class="small">
        <h1>Orden #: {{ $venta->id }} </h1>
        <div class="linea"></div>

        <strong>Fecha:</strong> {{ $venta->sale_date->format('d/m/Y H:i') }}<br>
        <strong>Atendió:</strong> {{ $venta->user->name ?? 'Cajero' }}<br>
        </p>

        <div class="linea"></div>

        <!-- CLIENTE -->
        <p class="small">
            <strong>Cliente:</strong> {{ $venta->customer->nombre ?? 'Consumidor Final' }}<br>
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
            {{ $item->productType->name }}
            <span style="float:right;">{{ number_format($item->quantity, 0) }}</span>
        </p>
        @endforeach

        <div class="linea"></div>


        <!-- FOOTER -->
        <p class="small" style="text-align:center;">
            Pre Orden<br>
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