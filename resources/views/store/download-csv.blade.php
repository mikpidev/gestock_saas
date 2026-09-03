<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">

    <title>Reporte de Ventas</title>

    <style>
        @page {
            margin: 35px 35px 45px 35px;
        }

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 10px;
            color: #1f2937;
        }

        h1,
        h2,
        h3,
        p {
            margin: 0;
        }

        .header {
            border-bottom: 2px solid #111827;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }

        .header-table {
            width: 100%;
        }

        .header-left {
            width: 70%;
        }

        .header-right {
            width: 30%;
            text-align: right;
        }

        .company {
            font-size: 20px;
            font-weight: bold;
            color: #111827;
        }

        .report-title {
            font-size: 13px;
            margin-top: 4px;
            color: #4b5563;
        }

        .store {
            font-size: 11px;
            margin-top: 5px;
        }

        .date {
            font-size: 9px;
            color: #6b7280;
        }

        /*
        |--------------------------------------------------------------------------
        | KPIs
        |--------------------------------------------------------------------------
        */

        .kpi-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 8px;
            margin: 0 -8px 15px -8px;
        }

        .kpi {
            border: 1px solid #e5e7eb;
            padding: 12px;
            background: #f9fafb;
        }

        .kpi-label {
            font-size: 8px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .kpi-value {
            font-size: 17px;
            font-weight: bold;
            margin-top: 5px;
            color: #111827;
        }

        /*
        |--------------------------------------------------------------------------
        | Sections
        |--------------------------------------------------------------------------
        */

        .section {
            margin-top: 18px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 8px;
            border-bottom: 1px solid #d1d5db;
            padding-bottom: 5px;
        }

        /*
        |--------------------------------------------------------------------------
        | Tables
        |--------------------------------------------------------------------------
        */

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: #f3f4f6;
            font-size: 8px;
            text-transform: uppercase;
            color: #4b5563;
            padding: 7px;
            border-bottom: 1px solid #d1d5db;
            text-align: left;
        }

        td {
            padding: 7px;
            border-bottom: 1px solid #e5e7eb;
        }

        .text-right {
            text-align: right;
        }

        .text-center {
            text-align: center;
        }

        /*
        |--------------------------------------------------------------------------
        | Two columns
        |--------------------------------------------------------------------------
        */

        .columns {
            width: 100%;
        }

        .column {
            width: 48%;
            vertical-align: top;
        }

        /*
        |--------------------------------------------------------------------------
        | Status
        |--------------------------------------------------------------------------
        */

        .status {
            font-weight: bold;
        }

        /*
        |--------------------------------------------------------------------------
        | Bar
        |--------------------------------------------------------------------------
        */

        .bar-container {
            width: 100%;
            height: 7px;
            background: #e5e7eb;
        }

        .bar {
            height: 7px;
            background: #374151;
        }

        /*
        |--------------------------------------------------------------------------
        | Footer
        |--------------------------------------------------------------------------
        */

        .footer {
            position: fixed;
            bottom: -25px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #9ca3af;
        }
    </style>
</head>

<body>

    {{-- HEADER --}}
    <div class="header">

        <table class="header-table">
            <tr>

                <td class="header-left">

                    <div class="company">
                        <img src="{{ public_path('Logo_recortado.png') }}"
                            style="width: 100px; height: auto; filter: drop-shadow(1px 1px 3px rgba(0,0,0,0.3));">
                    </div>

                    <div class="report-title">
                        Reporte de Ventas y Facturación
                    </div>

                    <div class="store">
                        {{ $store->store_name }}
                    </div>

                </td>

                <td class="header-right">

                    <div class="date">
                        PERÍODO
                    </div>

                    <strong>
                        {{ $dateFrom->format('d/m/Y') }}
                        -
                        {{ $dateTo->format('d/m/Y') }}
                    </strong>

                    <br>

                    <div class="date" style="margin-top: 5px;">
                        Generado:
                        {{ now()->format('d/m/Y H:i') }}
                    </div>

                </td>

            </tr>
        </table>

    </div>


    {{-- KPIs --}}

    <table class="kpi-table">

        <tr>

            <td class="kpi">

                <div class="kpi-label">
                    Ventas totales
                </div>

                <div class="kpi-value">
                    ${{ number_format($totalSales, 2) }}
                </div>

            </td>

            <td class="kpi">

                <div class="kpi-label">
                    Cantidad de ventas
                </div>

                <div class="kpi-value">
                    {{ number_format($totalCount) }}
                </div>

            </td>

            <td class="kpi">

                <div class="kpi-label">
                    Ticket promedio
                </div>

                <div class="kpi-value">
                    ${{ number_format($averageTicket, 2) }}
                </div>

            </td>

            <td class="kpi">

                <div class="kpi-label">
                    Estado DTE
                </div>

                <div class="kpi-value" style="font-size: 13px;">
                    {{ $dte_status }}
                </div>

            </td>

        </tr>

    </table>


    {{-- VENTAS DIARIAS --}}

    <div class="section">

        <div class="section-title">
            Ventas por día
        </div>

        <table>

            <thead>
                <tr>
                    <th>Fecha</th>
                    <th class="text-center">Ventas</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>

            <tbody>

                @forelse($chartData as $day)

                <tr>

                    <td>
                        {{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}
                    </td>

                    <td class="text-center">
                        {{ number_format($day->quantity) }}
                    </td>

                    <td class="text-right">
                        ${{ number_format($day->total, 2) }}
                    </td>

                </tr>

                @empty

                <tr>
                    <td colspan="3" class="text-center">
                        No existen ventas para este período.
                    </td>
                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- DTE + PAGOS --}}

    <div class="section">

        <table class="columns">

            <tr>

                {{-- DTE --}}

                <td class="column">

                    <div class="section-title">
                        Documentos electrónicos
                    </div>

                    <table>

                        <tr>
                            <td>Factura</td>
                            <td class="text-right">
                                {{ number_format($dteSummary->factura ?? 0) }}
                            </td>
                            <td class="text-right">
                                ${{ number_format($dteSummary->monto_factura ?? 0, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td>CCF</td>
                            <td class="text-right">
                                {{ number_format($dteSummary->CCF ?? 0) }}
                            </td>
                            <td class="text-right">
                                ${{ number_format($dteSummary->monto_CCF ?? 0, 2) }}
                            </td>
                        </tr>

                        <tr>
                            <td>Sujeto Excluido</td>
                            <td class="text-right">
                                {{ number_format($dteSummary->SE ?? 0) }}
                            </td>
                            <td class="text-right">
                                ${{ number_format($dteSummary->monto_SE ?? 0, 2) }}
                            </td>
                        </tr>

                    </table>

                    <br>

                    <table>

                        <tr>
                            <td>Procesados</td>
                            <td class="text-right">
                                {{ number_format($dteApproved) }}
                            </td>
                        </tr>

                        <tr>
                            <td>Rechazados</td>
                            <td class="text-right">
                                {{ number_format($dteDenied) }}
                            </td>
                        </tr>

                        <tr>
                            <td>Pendientes</td>
                            <td class="text-right">
                                {{ number_format($dtePending) }}
                            </td>
                        </tr>

                    </table>

                </td>


                {{-- PAGOS --}}

                <td class="column">

                    <div class="section-title">
                        Métodos de pago
                    </div>

                    <table>

                        <tr>

                            <td>
                                Efectivo
                            </td>

                            <td class="text-center">
                                {{ number_format($methodPaymentData->efectivo ?? 0) }}
                            </td>

                            <td class="text-right">
                                ${{ number_format($methodPaymentData->monto_efectivo ?? 0, 2) }}
                            </td>

                        </tr>

                        <tr>

                            <td>
                                Tarjeta
                            </td>

                            <td class="text-center">
                                {{ number_format($methodPaymentData->tarjeta ?? 0) }}
                            </td>

                            <td class="text-right">
                                ${{ number_format($methodPaymentData->monto_tarjeta ?? 0, 2) }}
                            </td>

                        </tr>

                        <tr>

                            <td>
                                Transferencia
                            </td>

                            <td class="text-center">
                                {{ number_format($methodPaymentData->transferencia ?? 0) }}
                            </td>

                            <td class="text-right">
                                ${{ number_format($methodPaymentData->monto_transferencia ?? 0, 2) }}
                            </td>

                        </tr>

                    </table>

                </td>

            </tr>

        </table>

    </div>


    {{-- TOP PRODUCTOS --}}

    <div class="section">

        <div class="section-title">
            Top 10 productos más vendidos
        </div>

        <table>

            <thead>

                <tr>

                    <th>#</th>

                    <th>Producto</th>

                    <th class="text-center">
                        Cantidad
                    </th>

                    <th class="text-right">
                        Ventas
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($topProducts as $index => $product)

                <tr>

                    <td>
                        {{ $index + 1 }}
                    </td>

                    <td>
                        {{ $product->productType->name ?? 'Producto eliminado' }}
                    </td>

                    <td class="text-center">
                        {{ number_format($product->total_sold, 2) }}
                    </td>

                    <td class="text-right">
                        ${{ number_format($product->total, 2) }}
                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="4" class="text-center">
                        No hay información disponible.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    {{-- HORARIOS PICO --}}

    <div class="section">

        <div class="section-title">
            Horarios con mayor cantidad de ventas
        </div>

        <table>

            <thead>

                <tr>

                    <th>
                        Hora
                    </th>

                    <th class="text-center">
                        Ventas
                    </th>

                    <th class="text-right">
                        Monto
                    </th>

                </tr>

            </thead>

            <tbody>

                @forelse($peakHours as $hour)

                <tr>

                    <td>
                        {{ sprintf('%02d:00', $hour->hour) }}
                        -
                        {{ sprintf('%02d:59', $hour->hour) }}
                    </td>

                    <td class="text-center">
                        {{ number_format($hour->total_sales) }}
                    </td>

                    <td class="text-right">
                        ${{ number_format($hour->total_amount, 2) }}
                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="3" class="text-center">
                        No hay información disponible.
                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>


    <div class="footer">

        Gestock · Reporte generado automáticamente ·
        {{ now()->format('d/m/Y H:i:s') }}

    </div>

</body>

</html>