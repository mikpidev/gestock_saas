<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Reporte de Ventas</title>
    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 12px;
            margin: 30px;
        }

        .header {
            display: flex;
            align-items: center;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
            margin-bottom: 25px;
        }

        .logo-container {
            width: 120px;
            text-align: center;
        }

        .logo-container img {
            width: 100%;
            height: auto;
        }

        .store-info {
            margin-left: 20px;
            line-height: 1.4;
        }

        h2 {
            text-align: center;
            margin: 10px 0 20px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .text-right {
            text-align: right;
        }

        .footer {
            text-align: right;
            margin-top: 20px;
            font-size: 11px;
            color: #555;
        }
    </style>
</head>

<body>

    {{-- ENCABEZADO --}}
    <div class="header">

        <div class="logo-container">
            <img src="{{ public_path('Logo.svg') }}">
        </div>

        <div class="store-info">
            {{-- Tomamos la tienda del primer registro si existe --}}
            @php $store = $sales->first()->store ?? null; @endphp

            <strong>{{ $store->store_name ?? 'Tienda no definida' }}</strong><br>
            {{ $store->address ?? 'Dirección no registrada' }}<br>
            Tel: {{ $store->phone ?? 'N/A' }}<br>
            {{ $store->email ?? '' }}
        </div>

    </div>

    <h2>Reporte de Ventas</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Tipo DTE</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sales as $sale)
            <tr>
                <td>{{ $loop->iteration }}</td>
                <td>{{ $sale->sale_date ? $sale->sale_date->format('d/m/Y H:i') : 'N/A' }}</td>
                <td>{{ $sale->tipoDte->nombre ?? $sale->tipoDte->codigo ?? 'N/A' }}</td>
                <td>{{ $sale->customer->nombre ?? 'Consumidor Final' }}</td>
                <td>{{ $sale->user->name ?? 'N/A' }}</td>
                <td class="text-right">${{ number_format($sale->total_amount ?? $sale->net_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        Generado el {{ now()->format('d/m/Y H:i:s') }}
    </div>

</body>

</html>
