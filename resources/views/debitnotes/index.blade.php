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
        <h2>Notas de Débito</h2>
        <a href="{{ route('stores.debitnotes.create', $store->id) }}" class="btn-new">Nueva Nota</a>
    </div>

    @if (session('success'))
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

    <div class="gestok-table-body">
        @if($debitNotes->isEmpty())
        <p class="no-data">No hay notas de débito registradas.</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>Código Generación</th>
                    <th>Cliente</th>
                    <th>Venta Relacionada</th>
                    <th>Fecha</th>
                    <th>Total</th>
                    <th>Estado DTE</th>
                    <th>Acciones</th>
                </tr>
            </thead>

            <tbody>
                @foreach($debitNotes as $debitNote)
                @php
                $status = $debitNote->dte_status ?? 'PENDIENTE';
                $statusClass = match($status) {
                'RECIBIDO', 'PROCESADO' => 'badge-procesado',
                'RECHAZADO' => 'badge-rechazado',
                default => 'badge-pendiente'
                };
                @endphp

                <tr>
                    <td>{{ $debitNote->codigo_generacion ?? '—' }}</td>
                    <td>{{ $debitNote->customer->nombre ?? 'Sin cliente' }}</td>
                    <td>{{ $debitNote->sale->invoice_number ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($debitNote->debit_note_date)->format('d/m/Y') }}</td>
                    <td>${{ number_format($debitNote->total_amount, 2) }}</td>

                    <!-- Estado DTE -->
                    <td>
                        @if($status === 'PENDIENTE')
                        <form action="{{ route('stores.debitnotes.refreshDTE', [$store->id, $debitNote->id]) }}"
                            method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="badge {{ $statusClass }}" title="Actualizar estado DTE">
                                {{ $status }}
                            </button>
                        </form>
                        @else
                        <span class="badge {{ $statusClass }}">{{ $status }}</span>
                        @endif
                    </td>

                    <!-- Acciones -->
                    <td class="actions">
                        <a href="{{ route('stores.debitnotes.show', [$store->id, $debitNote->id]) }}"
                            class="btn btn-info">
                            Ver
                        </a>

                        @php
                        $canDelete = $debitNote->created_at->greaterThan(now()->subHours(24));
                        @endphp

                        @if ($canDelete)
                        <form action="{{ route('stores.debitnotes.destroy', [$store->id, $debitNote->id]) }}"
                            method="POST"
                            onsubmit="return confirm('¿Eliminar nota de débito?');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-delete">
                                <i class="bi bi-trash"></i>
                            </button>
                        </form>
                        @else
                        <span data-bs-toggle="tooltip"
                            data-bs-placement="top"
                            title="No se puede eliminar notas de débito con más de 24 horas">
                            <button class="btn btn-delete" disabled style="opacity:0.6; cursor:not-allowed;">
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
</div>


<script>
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el)
        })
    });
</script>
@endsection