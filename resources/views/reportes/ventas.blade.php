<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>DTE {{ $dte['identificacion']['numeroControl'] ?? '' }}</title>

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
        }

        body {
            background: white;
            padding: 0;
            margin: 0;
        }

        .documento {
            width: 740px;
            margin: 0 auto;
            padding: 10px;
            font-size: 11px;
        }

        .encabezado {
            text-align: center;
            margin-bottom: 8px;
        }

        .encabezado h1 {
            font-size: 14px;
            margin-bottom: 2px;
        }

        .encabezado h2 {
            font-size: 12px;
            font-weight: bold;
        }

        .contenedor-superior {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
            padding: 0;
        }

        .contenedor-superior td {
            vertical-align: top;
            padding: 4px;
        }

        .tabla-info {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-info .etiqueta {
            font-weight: bold;
            width: 38%;
        }

        .tabla-info td {
            padding: 1px 0;
            font-size: 10.5px;
        }

        .qr img {
            width: 100px;
            height: auto;
            display: block;
            margin: 0 auto;
        }

        .receptor {
            background: #f4f4f4;
            padding: 8px;
            border-radius: 4px;
            margin-bottom: 10px;
            font-size: 10.5px;
        }

        .receptor h3 {
            font-size: 12px;
            margin-bottom: 5px;
        }

        .receptor .datos {
            display: flex;
            flex-wrap: wrap;
        }

        .receptor .columna {
            width: 50%;
            margin-bottom: 5px;
        }

        .receptor .etiqueta {
            font-weight: bold;
        }

        .detalles-factura {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 11px;
        }

        .tabla-productos {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .tabla-productos th {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 4px;
            font-size: 10px;
        }

        .tabla-productos td {
            border: 1px solid #ccc;
            padding: 4px;
            font-size: 10px;
        }

        .totales {
            width: 260px;
            margin-left: auto;
            font-size: 11px;
        }

        .fila-total {
            display: flex;
            justify-content: space-between;
            margin-bottom: 3px;
        }

        .divisor {
            border-top: 1px solid #ccc;
            margin: 10px 0;
        }

        .pie {
            text-align: center;
            font-size: 10px;
            color: #555;
            margin-top: 10px;
        }

        @page {
            margin: 20px 25px;
        }
    </style>
</head>

<body>
    <div class="documento">

        <!-- ENCABEZADO -->
        <div class="encabezado">
            <div style="width:120px; height:120px; display:flex; justify-content:center; align-items:center; margin:0 auto;">
                <img src="{{ public_path('Logo_recortado.png') }}"
                    style="width:100%; height:100%; object-fit:contain;" />
            </div>


            <h1>DOCUMENTO TRIBUTARIO ELECTRÓNICO</h1>
            <h2>{{ $tipoDteDescripcion }}</h2>
        </div>

        <div class="divisor"></div>

        <!-- SUPERIOR -->
        <table class="contenedor-superior">
            <tr>
                <!-- INFO DTE -->
                <td style="width:42%;">
                    <table class="tabla-info">
                        <tr>
                            <td class="etiqueta">Código DTE:</td>
                            <td>{{ $dte['identificacion']['codigoGeneracion'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">Número de Control:</td>
                            <td>{{ $dte['identificacion']['numeroControl'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">Sello DTE:</td>
                            <td>{{ $dte['sello'] ?? '---' }}</td>
                        </tr>
                    </table>
                </td>

                <!-- QR -->
                <td>
                    <div style="text-align:center;">
                        <div style="width:120px; height:120px; display:flex; justify-content:center; align-items:center; margin:0 auto;">
                            @if ($qrImage)
                            <img src="data:image/svg+xml;base64,{{ $qrImage }}"
                                style="width:100%; height:100%; object-fit:contain;" />
                            @endif
                        </div>
                    </div>
                </td>

                <!-- INFO EMPRESA -->
                <td style="width:42%;">
                    <table class="tabla-info">
                        <tr>
                            <td class="etiqueta">Razón Social:</td>
                            <td>{{ $emisor['nombre'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">NIT:</td>
                            <td>{{ $emisor['nit'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">NRC:</td>
                            <td>{{ $emisor['nrc'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">Actividad:</td>
                            <td>{{ $emisor['nombreComercial'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">Dirección:</td>
                            <td>{{ $emisor['direccion']['complemento'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">Teléfono:</td>
                            <td>{{ $emisor['telefono'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">Correo:</td>
                            <td>{{ $emisor['correo'] }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <div class="divisor"></div>

        <!-- RECEPTOR -->
        <div class="receptor">
            <h3>RECEPTOR</h3>
            <div class="datos">
                <div class="columna">
                    <div class="etiqueta">Nombre:</div>
                    <div>{{ $receptor['nombre'] }}</div>
                </div>
                <div class="columna">
                    <div class="etiqueta">Documento:</div>
                    <div>{{ $receptor['numDocumento'] ?? 'N/A' }}</div>
                </div>
                <div class="columna">
                    <div class="etiqueta">NRC:</div>
                    <div>{{ $receptor['nrc'] ?? 'N/A' }}</div>
                </div>
                <div class="columna">
                    <div class="etiqueta">Dirección:</div>
                    <div>{{ $receptor['direccion']['complemento'] }}</div>
                </div>
                <div class="columna">
                    <div class="etiqueta">Departamento:</div>
                    <div>{{ $receptor['direccion']['departamento'] }}</div>
                </div>
                <div class="columna">
                    <div class="etiqueta">Email:</div>
                    <div>{{ $receptor['correo'] }}</div>
                </div>
            </div>
        </div>

        <!-- DETALLES -->
        <div class="detalles-factura">
            <div><strong>Fecha:</strong> {{ $dte['identificacion']['fecEmi'] }}</div>
            <div><strong>N° Factura:</strong> {{ $dte['numeroFactura'] ?? '' }}</div>
        </div>

        <!-- PRODUCTOS -->
        <table class="tabla-productos">
            <thead>
                <tr>
                    <th>Cant.</th>
                    <th style="text-align:left;">Descripción</th>
                    <th>P.Unit</th>
                    <th>NoSuj.</th>
                    <th>Exenta</th>
                    <th>Gravada</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($dte['cuerpoDocumento'] as $item)
                <tr>
                    <td>{{ number_format($item['cantidad'], 2) }}</td>
                    <td style="text-align:left;">{{ $item['descripcion'] }}</td>
                    <td>${{ number_format($item['precioUni'], 2) }}</td>
                    <td>$0.00</td>
                    <td>$0.00</td>
                    <td>${{ number_format($item['ventaGravada'], 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- TOTALES -->
        <div class="totales">
            <div class="fila-total"><span>SUMAS:</span><span>${{ number_format($resumen['subTotalVentas'], 2) }}</span></div>
            <div class="fila-total"><span>IVA:</span><span>${{ number_format($resumen['totalIva'] ?? 0, 2) }}</span></div>
            <div class="fila-total"><span>SUBTOTAL:</span><span>${{ number_format($resumen['subTotal'], 2) }}</span></div>
            <div class="fila-total"><strong>TOTAL:</strong><strong>${{ number_format($resumen['totalPagar'], 2) }}</strong></div>
        </div>

        <p><strong>SON:</strong> {{ strtoupper($resumen['totalLetras']) }}</p>

        <div class="divisor"></div>

        <div class="pie">
            {{ $emisor['nombre'] }} - {{ $emisor['direccion']['complemento'] }} <br>
            Email: {{ $emisor['correo'] }}
        </div>

    </div>

</body>

</html>