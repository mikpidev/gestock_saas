<!DOCTYPE html>
<html lang="es">

<head>

    <meta charset="UTF-8">

    <title> DTE {{ $dte['identificacion']['numeroControl'] ?? '' }} </title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: Arial, sans-serif;
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

        .qr {
            text-align: center;
        }

        .qr img {
            width: 120px;
            height: 120px;
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

        .tabla-totales {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .tabla-totales th {
            background: #f0f0f0;
            border: 1px solid #ccc;
            padding: 4px;
            font-size: 10px;
            text-align: left;
        }

        .tabla-totales td {
            border: 1px solid #ccc;
            padding: 4px;
            font-size: 10px;
            text-align: right;
        }


        .tabla-otros {
            width: 100%;
            border-collapse: collapse;
            margin-top: 5px;
        }

        .tabla-otros th {
            background: #f0f0f0;
            padding: 4px;
            font-size: 10px;
            text-align: left;
        }

        .tabla-otros td {
            padding: 4px;
            font-size: 10px;
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
    </style>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-9ndCyUaIbzAi2FUVXJi0CjmCapSmO7SnpJef0486qhLnuZ2cdeRhO02iuK6FUUVM" crossorigin="anonymous">

</head>

<body>
    <div class="documento">

        <!-- ENCABEZADO -->
        <div class="encabezado">
            <div>
                @if(file_exists(public_path($store . '.png')))
                <img src="{{ public_path($store . '.png') }}" style="width:150px;height:auto;">
                @else
                <img src="{{ public_path($store . '.jpeg') }}" style="width:150px;height:auto;">
                @endif
            </div>
            <h1>DOCUMENTO TRIBUTARIO ELECTRÓNICO</h1>
            <h2>EVENTO DE INVALIDACIÓN</h2>

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
                            <td class="etiqueta">Código DTE:</td>
                            <td>{{ $dte['identificacion']['fecEmi'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">Sello DTE:</td>
                            <td>{{ $dteResponse->sello_recibido ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </td>

                <!-- QR -->


                <!-- INFO EMPRESA -->
                <td style="width:42%;">
                    <table class="tabla-info">
                        <h3>Identificación del Emisor</h3>

                        <tr>
                            <td class="etiqueta">Razón Social:</td>
                            <td>{{ $emisor['nombre'] }}</td>
                        </tr>
                        <tr>
                            <td class="etiqueta">NIT:</td>
                            <td>{{ $emisor['nit'] }}</td>
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
            <h3>Identificación del Receptor</h3>
            <div class="datos">
                <div class="columna">
                    <div class="etiqueta">Nombre:</div>
                    <div>{{ $documento['nombre'] }}</div>
                </div>
                <div class="columna">
                    <div class="etiqueta">Email:</div>
                    <div>{{ $documento['correo'] }}</div>
                </div>
            </div>
        </div>


        <!-- RECEPTOR -->
        <div class="receptor">

            <h3>Información relativa al Motivo de Invalidación</h3>

            <table style="width:100%; border-collapse:collapse;">
                <tr>
                    <td style="width:50%; padding:3px;">
                        <strong>Tipo de Invalidación:</strong><br>
                        {{ $motivo['tipoAnulacion'] }}
                    </td>

                    <td style="width:50%; padding:3px;">
                        <strong>Motivo:</strong><br>
                        {{ $motivo['motivoAnulacion'] }}
                    </td>
                </tr>

                <tr>
                    <td style="padding:3px;">
                        <strong>Nombre de quien realiza el evento:</strong><br>
                        {{ $motivo['nombreResponsable'] }}
                    </td>

                    <td style="padding:3px;">
                        <strong>NIT:</strong><br>
                        {{ $motivo['numDocResponsable'] }}
                    </td>
                </tr>

                <tr>
                    <td style="padding:3px;">
                        <strong>Nombre de quien solicita el evento:</strong><br>
                        {{ $motivo['nombreSolicita'] }}
                    </td>

                    <td style="padding:3px;">
                        <strong>NIT:</strong><br>
                        {{ $motivo['numDocSolicita'] }}
                    </td>
                </tr>
            </table>

        </div>

        <!-- PRODUCTOS -->
        <table class="tabla-productos">
            <thead>
                <tr>
                    <th>Tipo DTE</th>
                    <th style="text-align:left;">Código de Generación aplicado</th>
                    <th>Sello de Recepción</th>
                    <th>Número de Control del DTE</th>
                    <th>Fecha de Generación</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>{{ $tipoDteDescripcion }}</td>
                    <td style="text-align:left;">{{ $dte['documento']['codigoGeneracion'] }}</td>
                    <td> {{ $dte['documento']['numeroControl'] }}</td>
                    <td>{{ $dte['documento']['selloRecibido'] }}</td>
                    <td>{{ $dte['documento']['fecEmi'] }}</td>
                </tr>
            </tbody>
        </table>

        <h3>Código de Generación que reemplaza al invalidado: </h3>


    </div>

</body>

</html>