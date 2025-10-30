@extends('layouts.admin')

@section('content')
<style>
    .gestok-table-card {
        background: #fff;
        color: #000;
        width: 100%;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        margin: 2rem auto;
        max-width: 1000px;
    }

    .gestok-table-header {
        background: #000;
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
        background: #fff;
        color: #000;
        padding: 0.6rem 1.2rem;
        border-radius: 5px;
        font-weight: bold;
        text-decoration: none;
        transition: background 0.2s;
    }

    .gestok-table-header a.btn:hover {
        background: #e6e6e6;
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
        border-bottom: 1px solid #ddd;
    }

    th {
        background: #f8f8f8;
        font-weight: bold;
    }

    tr:hover {
        background: #f9f9f9;
    }

    .actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-view {
        background: #007bff;
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.85rem;
        transition: background 0.2s;
    }

    .btn-view:hover {
        background: #0056b3;
    }

    .btn-delete {
        background: #dc3545;
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 0.85rem;
        transition: background 0.2s;
    }

    .btn-delete:hover {
        background: #b02a37;
    }

    /* Estados DTE */
    .dte-status {
        font-weight: bold;
        border-radius: 4px;
        padding: 0.3rem 0.6rem;
        text-align: center;
        display: inline-block;
        min-width: 100px;
    }

    .dte-status.procesado {
        background: #d4edda;
        color: #155724;
    }

    .dte-status.rechazado {
        background: #f8d7da;
        color: #721c24;
    }

    .dte-status.pendiente {
        background: #f3e5ab;
        color: #5c3d00;
        cursor: pointer;
        transition: background 0.2s;
    }

    .dte-status.pendiente:hover {
        background: #e8d890;
    }

    .no-data {
        text-align: center;
        padding: 2rem;
        font-style: italic;
        color: #666;
    }
</style>

<div class="gestok-table-card">
    <div class="gestok-table-header">
        <h1>Notas de Débito</h1>
        <a href="{{ route('stores.debitnotes.create', $store->id) }}" class="btn">Nueva Nota</a>
    </div>

    @if (session('success'))
    <div style="background: #d4edda; color: #155724; padding: 1rem; margin: 1rem; border-radius: 5px;">
        {{ session('success') }}
    </div>
    @endif

    <div class="gestok-table-body">
        @if($debitNotes->isEmpty())
        <p class="no-data">No hay notas de débito registradas.</p>
        @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
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
                'RECIBIDO' => 'procesado',
                'RECHAZADO' => 'rechazado',
                default => 'pendiente'
                };
                @endphp

                <tr>
                    <td>{{ $debitNote->codigo_generacion ?? '—' }}</td>
                    <td>{{ $debitNote->customer->nombre ?? 'Sin cliente' }}</td>
                    <td>{{ $debitNote->sale->invoice_number ?? 'N/A' }}</td>
                    <td>{{ \Carbon\Carbon::parse($debitNote->debit_note_date)->format('d/m/Y') }}</td>
                    <td>${{ number_format($debitNote->total_amount, 2) }}</td>
                    <td>
                        @php
                        $status = $debitNote->dte_status ?? 'PENDIENTE';
                        $statusClass = match($status) {
                        'RECIBIDO' => 'procesado',
                        'RECHAZADO' => 'rechazado',
                        default => 'pendiente'
                        };
                        @endphp

                        @if($status === 'PENDIENTE')
                        <form action="{{ route('stores.debitnotes.refreshDTE', [$store->id, $debitNote->id]) }}" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="dte-status {{ $statusClass }}" title="Actualizar estado DTE">
                                {{ $status }}
                            </button>
                        </form>
                        @else
                        <span class="dte-status {{ $statusClass }}">{{ $status }}</span>
                        @endif
                    </td>
                    <td class="actions">
                        <a href="{{ route('stores.debitnotes.show', [$store->id, $debitNote->id]) }}" class="btn-view">Ver</a>
                        <form action="{{ route('stores.debitnotes.destroy', [$store->id, $debitNote->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar esta nota de débito?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-delete">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
</div>
@endsection