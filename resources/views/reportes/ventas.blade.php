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

        .logo {
            width: 80px;
            height: auto;
            margin-right: 15px;
        }

        .store-info {
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

        th, td {
            border: 1px solid #333;
            padding: 6px 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
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

    {{-- Encabezado con logo y datos de la tienda --}}
    <div class="header">
        <img src="{{ public_path('Logo.png') }}" class="logo">
        <div class="store-info">
            <strong>{{ $store->nombre ?? 'Tienda' }}</strong><br>
            {{ $store->direccion ?? 'Dirección no registrada' }}<br>
            Tel: {{ $store->telefono ?? 'N/A' }}<br>
            {{ $store->email ?? '' }}
        </div>
    </div>

    <h2>Reporte de Ventas</h2>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th>Vendedor</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($sales as $sale)
                <tr>
                    <td>{{ $loop->iteration }}</td>
                    <td>{{ \Carbon\Carbon::parse($sale->created_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $sale->customer->nombre ?? 'N/A' }}</td>
                    <td>{{ $sale->user->name ?? 'N/A' }}</td>
                    <td>${{ number_format($sale->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="footer">
        <p>Generado el {{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

</body>
</html>
