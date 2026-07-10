<div id="dte-print-area">

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

    <div class="documento">

        <!-- ENCABEZADO -->
        <div class="encabezado">
            <div>
                @if(file_exists(public_path($store . '.png')))
                <img src="{{ asset($store . '.png') }}" style="width:150px;height:auto;">
                @else
                <img src="{{ asset($store . '.jpeg') }}" style="width:150px;height:auto;">
                @endif
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
                            <td>{{ $dteResponse?->sello_recibido ?? 'N/A' }}</td>
                        </tr>
                    </table>
                </td>

                <!-- QR -->
                <td style="width:16%;" class="qr">
                    <!-- QR -->
                    @if ($qrImage)
                    <img src="data:image/svg+xml;base64,{{ $qrImage }}"
                        alt="QR" style="width:140px; margin-top:10px;">
                    @else
                    <p style="color:red;">QR no disponible</p>
                    @endif
                </td>

                <!-- EMPRESA -->
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
                            <td>{{ $emisor['descActividad'] ?? ' '}}</td>
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
                    <div>{{ $receptor['numDocumento'] ?? $receptor['nit'] ?? 'N/A' }}</div>
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
                    <td>{{ number_format($item['cantidad']) }}</td>
                    <td style="text-align:left;">{{ $item['descripcion'] }}</td>
                    <td>${{ number_format($item['precioUni'], 2) }}</td>
                    <td>$0.00</td>
                    <td>$0.00</td>
                    <td>${{ number_format($item['ventaGravada'] ?? 0, 2) }}</td>
                </tr>
                @endforeach

                <tr>
                    <td colspan="3" style="text-align:right;">
                        Sumas:
                    </td>
                    <td>${{ number_format($resumen['totalNoSuj'] ?? 0, 2) }}</td>
                    <td>${{ number_format($resumen['totalExenta'] ?? 0, 2) }}</td>
                    <td>
                        ${{ number_format($resumen['totalGravada'] ?? 0, 2) }}
                    </td>
                </tr>
            </tbody>
        </table>

        <div style="clear: both;"></div>



        <table class="tabla-totales">

            <tr>
                <th colspan="3" style="text-align:right;">Suma Total de Operaciones (IVA No Incluido): </th>
                <td><span>${{ number_format($resumen['subTotal'] ?? 0, 2) }} </span></td>

            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Descuento global a ventas no sujetas:</th>
                <td><span>${{ number_format($resumen['descuNoSuj'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Descuento global a ventas exentas: </th>
                <td><span>${{ number_format($resumen['descuExenta'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Descuento global a ventas gravadas:</th>
                <td><span>${{ number_format($resumen['descuGravada'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Impuesto al Valor Agregado 13%:</th>
                <td><span>${{ number_format($resumen['totalIva'] ?? $resumen['tributos'][0]['valor'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Sub-Total: </th>
                <td><span>${{ number_format($resumen['subTotal'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">IVA Percibido: </th>
                <td><span>${{ number_format($resumen['ivaPerci1'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">IVA Retenido: </th>
                <td><span>${{ number_format($resumen['ivaRete1'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Monto Total de la Operacion (IVA Incluido): </th>
                <td><span>${{ number_format($resumen['montoTotalOperacion'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Total Otros montos no afectados:</th>
                <td><span>${{ number_format($resumen['totalNoGravado'] ?? 0, 2) }} </span></td>
            </tr>
            <tr>
                <th colspan="3" style="text-align:right;">Total a Pagar:</th>
                <td><span>${{ number_format($resumen['montoTotalOperacion'] ?? 0, 2) }} </span></td>
            </tr>
            </tbody>
        </table>
        <div style="clear: both;"></div>

        <div class="divisor"></div>

        <table class="tabla-otros">
            <tbody>
                <tr>
                    <td>Valor en Letras:</td>
                    <td>{{ strtoupper($resumen['totalLetras']) }} DOLARES AMERICANOS</td>
                    <td>Condicion de la Operacion: </td>
                    <td> {{ strtoupper($resumen['condicionOperacion'] == 1 ? 'Contado' : 'Crédito') }}
                    </td>
                </tr>

                <tr>

                </tr>

                <tr>
                    <td>Observaciones: </td>
                    <td> @if(!empty($dteResponse['observaciones']))
                        @foreach((array) $dteResponse['observaciones'] as $obs)
                        {{ $obs }}<br>
                        @endforeach
                        @else
                        Sin observaciones
                        @endif
                    </td>
                </tr>
            </tbody>
        </table>


        <div style="clear: both;"></div>





        <div class="pie">
            <div class="divisor"></div>
            {{ $emisor['nombre'] }} - {{ $emisor['direccion']['complemento'] }} <br>
            Email: {{ $emisor['correo'] }}
        </div>

    </div>
</div>