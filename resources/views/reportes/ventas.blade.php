<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>DTE {{ $dte['identificacion']['numeroControl'] ?? '' }}</title>

<style>
    body {
        font-family: Arial, sans-serif;
        font-size: 11px;
        margin: 15px;
    }
    .title {
        font-size: 15px;
        font-weight: bold;
        text-align: center;
        margin-bottom: 8px;
    }
    .section-title {
        font-weight: bold;
        background: #e5e5e5;
        padding: 4px;
        font-size: 11px;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 6px;
    }
    td, th {
        padding: 4px;
        border: 1px solid #000;
    }
    .no-border td {
        border: none;
        padding: 2px 0;
    }
    .totales td {
        font-weight: bold;
    }
</style>
</head>

<body>

{{-- --------------------- ENCABEZADO ------------------------- --}}
<div class="title">DOCUMENTO TRIBUTARIO ELECTRÓNICO</div>

<table class="no-border">
    <tr>
        <td><strong>CONSUMIDOR_FINAL</strong></td>
        <td style="text-align:right;">
            <strong>Código DTE:</strong> {{ $dte['identificacion']['codigoGeneracion'] }}
        </td>
    </tr>
</table>

<table>
    <tr>
        <td><strong>Número de Control:</strong><br>
            {{ $dte['identificacion']['numeroControl'] }}
        </td>
        <td><strong>Sello DTE:</strong><br>
            {{ $dte['sello'] ?? '---' }}
        </td>
    </tr>
</table>

{{-- --------------------- EMISOR ------------------------- --}}
<div class="section-title">DATOS DEL EMISOR</div>

<table>
    <tr>
        <td><strong>Nombre o Razón Social:</strong><br>
            {{ $emisor['nombre'] }}
        </td>

        <td><strong>NIT:</strong><br>
            {{ $emisor['nit'] }}
        </td>

        <td><strong>NRC:</strong><br>
            {{ $emisor['nrc'] }}
        </td>
    </tr>

    <tr>
        <td><strong>Actividad:</strong><br>{{ $emisor['nombreComercial'] }}</td>
        <td colspan="2">
            <strong>Dirección:</strong><br>
            {{ $emisor['direccion']['complemento'] }}
        </td>
    </tr>

    <tr>
        <td><strong>Teléfono:</strong><br> {{ $emisor['telefono'] }}</td>
        <td colspan="2"><strong>Correo:</strong><br>{{ $emisor['correo'] }}</td>
    </tr>
</table>

{{-- --------------------- RECEPTOR ------------------------- --}}
<div class="section-title">DATOS DEL CLIENTE</div>

<table>
    <tr>
        <td><strong>Nombre:</strong><br>{{ $receptor['nombre'] }}</td>
        <td><strong>Documento:</strong><br>{{ $receptor['numDocumento'] ?? 'N/A' }}</td>
        <td><strong>NRC:</strong><br>{{ $receptor['nrc'] ?? 'N/A' }}</td>
    </tr>

    <tr>
        <td colspan="3">
            <strong>Dirección:</strong><br>
            {{ $receptor['direccion']['complemento'] }}
        </td>
    </tr>

    <tr>
        <td><strong>Departamento:</strong> {{ $receptor['direccion']['departamento'] }}</td>
        <td><strong>Municipio:</strong> {{ $receptor['direccion']['municipio'] }}</td>
        <td><strong>Teléfono:</strong> {{ $receptor['telefono'] }}</td>
    </tr>

    <tr>
        <td colspan="3"><strong>Email:</strong> {{ $receptor['correo'] }}</td>
    </tr>
</table>

{{-- --------------------- INFO DTE ------------------------- --}}
<table>
    <tr>
        <td><strong>Fecha:</strong><br>{{ $dte['identificacion']['fecEmi'] }}</td>
        <td><strong>Tipo de DTE:</strong><br>{{ $dte['identificacion']['tipoDte'] }}</td>
        <td><strong>N° Factura:</strong><br>{{ $dte['numeroFactura'] ?? '' }}</td>
    </tr>
</table>

{{-- --------------------- CUERPO DOCUMENTO ------------------------- --}}
<div class="section-title">DETALLE</div>

<table>
    <thead>
        <tr>
            <th>Cant.</th>
            <th>Descripción</th>
            <th>P. Unitario</th>
            <th>No Suj.</th>
            <th>Exenta</th>
            <th>Gravadas</th>
        </tr>
    </thead>

    <tbody>
        @foreach ($dte['cuerpoDocumento'] as $item)
        <tr>
            <td>{{ number_format($item['cantidad'], 2) }}</td>
            <td>{{ $item['descripcion'] }}</td>
            <td>${{ number_format($item['precioUni'], 2) }}</td>
            <td>$0.00</td>
            <td>$0.00</td>
            <td>${{ number_format($item['ventaGravada'], 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- --------------------- RESUMEN ------------------------- --}}
<div class="section-title">TOTALES</div>

<table class="totales">
    <tr>
        <td>SUMAS:</td>
        <td>${{ number_format($resumen['subTotalVentas'], 2) }}</td>
    </tr>
    <tr>
        <td>IVA:</td>
        <td>${{ number_format($resumen['totalIva'], 2) }}</td>
    </tr>
    <tr>
        <td>SUB-TOTAL:</td>
        <td>${{ number_format($resumen['subTotal'], 2) }}</td>
    </tr>
    <tr>
        <td>TOTAL A PAGAR:</td>
        <td>${{ number_format($resumen['totalPagar'], 2) }}</td>
    </tr>
</table>

<p><strong>SON:</strong> {{ strtoupper($resumen['totalLetras']) }}</p>

</body>
</html>
