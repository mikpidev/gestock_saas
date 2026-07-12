<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Corte de Caja</title>
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

        .linea {
            border-top: 1px dashed #000;
            margin: 5px 0;
        }

        .totales,
        .info {
            text-align: right;
            margin-top: 5px;
        }

        .small {
            font-size: 9px;
        }
    </style>
</head>

<body>
    <div class="recibo">

        <!-- LOGO -->
        <div style="margin-bottom:10px; text-align:center;">
            @if(file_exists(public_path($closure->store->store_name . '.png')))
            <img src="{{ public_path( $closure->store->store_name .'.png') }}" style="width:100%; height:auto; display:inline-block;">
            @else
            <img src="{{ public_path( $closure->store->store_name .'.jpeg') }}" style="width:100%; height:auto; display:inline-block;">
            @endif
        </div>

        <h3 style="margin:0; padding:0;">{{ $closure->store->store_name ?? 'Mi Tienda' }}</h3>
        <p class="small" style="margin:0; padding:0;">
            Dirección: {{ $closure->store->address ?? '' }}<br>
            Tel: {{ $closure->store->taxInfo->telefono ?? '' }}<br>
            NIT: {{ $closure->store->taxInfo->nit ?? '' }} | NRC: {{ $closure->store->taxInfo->nrc ?? '' }}
        </p>

        <div class="linea"></div>

        <!-- INFO CORTE -->
        <p class="small">
            <strong>Corte ID:</strong> {{ $closure->id }}<br>
            <strong>Fecha:</strong> {{ $closure->created_at->format('d/m/Y H:i') }}<br>
            <strong>Usuario:</strong> {{ $closure->user->name ?? 'N/A' }}<br>
            <strong>Rango ventas:</strong> #{{ $closure->from_sale_id }} → #{{ $closure->to_sale_id }}
        </p>

        <div class="linea"></div>

        <!-- RESUMEN -->
        <p class="small">
            <strong>Total Ventas:</strong> {{ $closure->total_sales }}<br>
            <strong>Notas de Crédito:</strong> {{ $closure->total_credit_notes }}<br>
            <strong>Notas de Débito:</strong> {{ $closure->total_debit_notes }}
        </p>

        <div class="linea"></div>

        <!-- TOTALES $$ -->
        <p class="totales">Ventas: ${{ number_format($closure->amount_sales, 2) }}</p>
        <p class="totales">Créditos: -${{ number_format($closure->amount_credit_notes, 2) }}</p>
        <p class="totales">Débitos: +${{ number_format($closure->amount_debit_notes, 2) }}</p>

        <div class="linea"></div>

        <p class="totales">Efectivo: ${{ number_format($closure->total_cash, 2) }}</p>
        <p class="totales">Tarjeta: ${{ number_format($closure->total_card, 2) }}</p>

        <div class="linea"></div>

        <p class="small" style="text-align:center;">
            <strong>Corte generado correctamente</strong><br>
            Documento interno sin validez fiscal
        </p>

    </div>
</body>

</html>