@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .card-table {
        background: #fff;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.10);
        overflow: hidden;
        margin: 2rem auto;
        max-width: 1100px;
    }

    .card-header-custom {
        background: #1f2937;
        color: #fff;
        padding: 1.2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header-custom h2 {
        font-size: 1.4rem;
        margin: 0;
        font-weight: 600;
    }

    .btn-new {
        background: #3b82f6;
        padding: 8px 14px;
        border-radius: 6px;
        font-weight: 600;
        color: #fff;
        text-decoration: none;
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .btn-new:hover {
        background: #2563eb;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    th {
        background: #f3f4f6;
        font-weight: 600;
    }

    th,
    td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    tr:hover {
        background: #fafafa;
    }

    .actions {
        display: flex;
        gap: 6px;
    }

    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 6px;
        font-size: 13px;
        padding: 6px 10px;
        color: #fff;
        border: none;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-info {
        background: #3b82f6;
    }

    .btn-info:hover {
        background: #2563eb;
    }

    .btn-print {
        background: #10b981;
    }

    .btn-print:hover {
        background: #059669;
    }

    .btn-delete {
        background: #ef4444;
    }

    .btn-delete:hover {
        background: #dc2626;
    }

    .badge {
        padding: 5px 8px;
        font-size: 11px;
        border-radius: 6px;
        font-weight: bold;
        color: #fff;
    }

    .badge-procesado {
        background: #10b981;
    }

    .badge-rechazado {
        background: #ef4444;
    }

    .badge-pendiente {
        background: #ca8a04;
    }

    .no-data {
        text-align: center;
        padding: 2rem;
        color: #6b7280;
        font-style: italic;
    }

    .filter-bar {
        background: #f9fafb;
        padding: 12px;
        display: flex;
        gap: 8px;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .filter-bar input {
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 6px;
    }
</style>

<div class="card-table">

    <div class="card-header-custom">
        <h2>{{ $store->store_name }} — Ventas</h2>
        <a href="{{ route('stores.sales.create', $store->id) }}" class="btn-new">
            <i class="bi bi-plus-circle"></i> Nueva venta
        </a>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:10px;margin:12px;border-radius:6px;">
        {{ session('success') }}
    </div>
    @endif

    <form method="GET" class="filter-bar">
        <label><b>Fecha:</b></label>
        <input type="date" name="fecha" value="{{ $fecha }}">
        <button class="btn btn-info">Filtrar</button>
        <a href="?fecha={{ now()->toDateString() }}" class="btn btn-print">
            <i class="bi bi-calendar-check"></i> Hoy
        </a>
        <a href="{{ route('reportes.ventas') }}" class="btn btn-print">
            <i class="bi bi-file-earmark-pdf"></i> PDF
        </a>
    </form>

    @if($sales->isEmpty())
    <div class="no-data">No hay ventas para <b>{{ \Carbon\Carbon::parse($fecha)->format('d/m/Y') }}</b></div>
    @else

    <table>
        <thead>
            <tr>
                <th># Código</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Fecha</th>
                <th>DTE</th>
                <th width="200">Acciones</th>
            </tr>
        </thead>
        <tbody>

            @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->codigo_generacion ?? '—' }}</td>
                <td>{{ $sale->customer->nombre ?? 'Sin cliente' }}</td>
                <td>${{ number_format($sale->net_amount, 2) }}</td>
                <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>

                <td>
                    @php
                    $cls = 'badge-pendiente';
                    if($sale->dte_status === 'PROCESADO') $cls = 'badge-procesado';
                    if($sale->dte_status === 'RECHAZADO') $cls = 'badge-rechazado';
                    @endphp

                    @if(!$sale->dte_status || $sale->dte_status === 'PENDIENTE')
                    <form action="{{ route('stores.sales.refreshDTE', [$store->id, $sale->id]) }}" method="POST">
                        @csrf
                        <button class="badge {{ $cls }}" title="Actualizar DTE">
                            {{ $sale->dte_status ?? 'PENDIENTE' }}
                        </button>
                    </form>
                    @else
                    <span class="badge {{ $cls }}">{{ $sale->dte_status }}</span>
                    @endif
                </td>

                <td class="actions">
                    <a href="{{ route('stores.sales.show', [$store->id, $sale->id]) }}" class="btn btn-info">
                        <i class="bi bi-eye"></i> Ver
                    </a>

                    <button onclick="mostrarModalImpresion('{{ route('ticket.print', [$store->id, $sale->id]) }}')" class="btn btn-print">
                        <i class="bi bi-printer"></i> Imprimir
                    </button>
                    @php
                    $canDelete = $sale->created_at->greaterThan(now()->subHours(24));
                    @endphp

                    @if ($canDelete)
                    <form action="{{ route('stores.sales.destroy', [$store->id, $sale->id]) }}"
                        method="POST"
                        onsubmit="return confirm('¿Eliminar venta?');">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-delete">
                            <i class="bi bi-trash"></i>
                        </button>
                    </form>
                    @else
                    <span data-bs-toggle="tooltip" data-bs-placement="top"
                        title="No se puede eliminar ventas con más de 24 horas">
                        <button class="btn btn-delete" disabled>
                            <i class="bi bi-trash"></i>
                        </button>
                    </span>


                    @endif

                </td>
            </tr>
            @endforeach

        </tbody>
    </table>

    @endif

</div>

<script>
    function mostrarModalImpresion(url) {
        const w = window.open(url, '_blank', 'width=400,height=800');
        w.onload = () => w.print();
    }
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el)
        })
    });
</script>
@endsection