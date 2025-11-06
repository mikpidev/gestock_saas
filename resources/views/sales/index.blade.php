@extends('layouts.admin')

@section('content')
<style>
    .gestok-table-card {
        background: #fff;
        color: #000;
        width: 100%;
        border-radius: 10px;
        box-shadow: 0 3px 8px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        margin: 2rem auto;
        max-width: 1000px;
    }

    .gestok-table-header {
        background: #374151;
        /* gris azulado suave */
        color: #fff;
        padding: 1.2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .gestok-table-header h1 {
        font-size: 1.4rem;
        font-weight: bold;
        margin: 0;
    }

    .gestok-table-header a.btn {
        background: #60a5fa;
        /* azul suave */
        color: #fff;
        padding: 0.6rem 1.2rem;
        border-radius: 5px;
        font-weight: bold;
        text-decoration: none;
        transition: 0.2s;
    }

    .gestok-table-header a.btn:hover {
        background: #3b82f6;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    th,
    td {
        padding: 0.8rem;
        text-align: left;
        border-bottom: 1px solid #e5e7eb;
    }

    th {
        background: #f3f4f6;
        /* gris muy claro */
        font-weight: bold;
    }

    tr:hover {
        background: #f9fafb;
    }

    .actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-view {
        background: #60a5fa;
        /* azul suave */
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 5px;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.2rem;
        text-decoration: none;
        transition: 0.2s;
    }

    .btn-view:hover {
        background: #3b82f6;
    }

    .btn-delete {
        background: #f87171;
        /* rojo suave */
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        gap: 0.2rem;
        transition: 0.2s;
    }

    .btn-delete:hover {
        background: #ef4444;
    }

    .btn-dte-refresh {
        background: #fbbf24;
        /* amarillo/café suave */
        color: #fff;
        padding: 0.3rem 0.6rem;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 0.8rem;
        display: flex;
        align-items: center;
        gap: 0.2rem;
        transition: 0.2s;
    }

    .btn-dte-refresh:hover {
        background: #f59e0b;
    }

    /* Estados DTE */
    .dte-status {
        padding: 0.2rem 0.6rem;
        border-radius: 5px;
        color: #fff;
        font-weight: bold;
        font-size: 0.8rem;
    }

    .dte-status.procesado {
        background: #10b981;
        /* verde */
    }

    .dte-status.rechazado {
        background: #ef4444;
        /* rojo */
    }

    .dte-status.pendiente {
        background: #a16207;
        /* café */
    }

    .no-data {
        text-align: center;
        padding: 2rem;
        font-style: italic;
        color: #6b7280;
    }
</style>

<div class="gestok-table-card">
    <div class="gestok-table-header">
        <h1>{{ $store->store_name }} - Ventas</h1>
        <a href="{{ route('stores.sales.create', $store->id) }}" class="btn">
            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-circle" viewBox="0 0 16 16">
                <path d="M8 15A7 7 0 1 0 8 1a7 7 0 0 0 0 14zm0-1A6 6 0 1 1 8 2a6 6 0 0 1 0 12z" />
                <path d="M8 4a.5.5 0 0 1 .5.5v3h3a.5.5 0 0 1 0 1h-3v3a.5.5 0 0 1-1 0v-3h-3a.5.5 0 0 1 0-1h3v-3A.5.5 0 0 1 8 4z" />
            </svg>
            Nueva Venta
        </a>
    </div>

    @if (session('success'))
    <div style="background: #d1fae5; color: #065f46; padding: 1rem; margin: 1rem; border-radius: 5px;">
        {{ session('success') }}
    </div>
    @endif

    <div class="gestok-table-body">
        @if($sales->isEmpty())
        <p class="no-data">No hay ventas registradas.</p>
        @else
        <table>
            <thead>
                <tr>
                    <th># Código</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Fecha</th>
                    <th>Estado DTE</th>
                    <th>Acciones</th>
                    <th><a href="{{ route('reportes.ventas') }}" class="btn btn-primary">
                            Descargar reporte de ventas (PDF)
                        </a>
                    </th>
                </tr>
            </thead>
            <tbody>
                @foreach($sales as $sale)
                <tr>
                    <td>{{ $sale->codigo_generacion ?? 'N/A' }}</td>
                    <td>{{ $sale->customer->nombre ?? 'Sin cliente' }}</td>
                    <td>${{ number_format($sale->net_amount, 2) }}</td>
                    <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                    <td>
                        @php
                        $statusClass = 'pendiente';
                        if($sale->dte_status === 'PROCESADO') $statusClass = 'procesado';
                        if($sale->dte_status === 'RECHAZADO') $statusClass = 'rechazado';
                        @endphp

                        @if($sale->dte_status === null || $sale->dte_status === 'PENDIENTE')
                        <form action="{{ route('stores.sales.refreshDTE', [$store->id, $sale->id]) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn-dte-refresh {{ $statusClass }}" title="Actualizar estado DTE">
                                {{ $sale->dte_status ?? 'PENDIENTE' }}
                            </button>
                        </form>
                        @else
                        <span class="dte-status {{ $statusClass }}">{{ $sale->dte_status }}</span>
                        @endif
                    </td>

                    <td class="actions">
                        <a href="{{ route('stores.sales.show', [$store->id, $sale->id]) }}" class="btn-view">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-eye" viewBox="0 0 16 16">
                                <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z" />
                                <path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6z" />
                            </svg>
                            Ver
                        </a>

                        <form action="{{ route('stores.sales.destroy', [$store->id, $sale->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar esta venta?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-trash" viewBox="0 0 16 16">
                                    <path d="M5.5 5.5a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0v-6a.5.5 0 0 1 .5-.5zM8 5.5a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0v-6a.5.5 0 0 1 .5-.5zM10.5 5.5a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0v-6a.5.5 0 0 1 .5-.5z" />
                                    <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1h-1v9.5A1.5 1.5 0 0 1 11 15H5a1.5 1.5 0 0 1-1.5-1.5V4h-1a1 1 0 0 1 0-2h3.5a.5.5 0 0 1 .5.5V3h3v-.5a.5.5 0 0 1 .5-.5H14a1 1 0 0 1 1 1z" />
                                </svg>
                                Eliminar
                            </button>
                        </form>
                        <button onclick="mostrarModalImpresion('{{ route('ticket.print', [$store->id, $sale->id]) }}')">
                            🖨️ Imprimir ticket
                        </button>

                        <button onclick="mostrarModalImpresion('{{ route('ticket.reprint', [$store->id, $sale->id]) }}')">
                            ♻️ Reimprimir ticket
                        </button>




                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>

<script>
    function mostrarModalImpresion(url) {
        // abrir modal o ventana para imprimir
        const win = window.open(url, '_blank', 'width=400,height=800');

        // auto imprimir cuando cargue
        win.onload = function() {
            win.print();
        };
    }
</script>

@endsection