@extends('layouts.admin')

@section('content')
<style>
    .gestok-table-card {
        background: #fff;
        color: #000;
        width: 100%;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        margin: 2rem auto;
        max-width: 900px;
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
    }

    .gestok-table-header a.btn:hover {
        background: #ddd;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    th, td {
        padding: 0.8rem;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    th {
        background: #f8f8f8;
        font-weight: bold;
    }

    tr:hover {
        background: #f3f3f3;
    }

    .actions {
        display: flex;
        gap: 0.5rem;
    }

    .btn-view {
        background: #000;
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 5px;
        text-decoration: none;
        font-size: 0.85rem;
    }

    .btn-view:hover {
        background: #333;
    }

    .btn-delete {
        background: #dc3545;
        color: #fff;
        padding: 0.4rem 0.8rem;
        border-radius: 5px;
        border: none;
        cursor: pointer;
        font-size: 0.85rem;
    }

    .btn-delete:hover {
        background: #b02a37;
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
        <h1>Notas de Crédito</h1>
        <a href="{{ route('stores.creditnotes.create', $store->id) }}" class="btn">Nueva Nota</a>
    </div>

    @if (session('success'))
        <div style="background: #d4edda; color: #155724; padding: 1rem; margin: 1rem; border-radius: 5px;">
            {{ session('success') }}
        </div>
    @endif

    <div class="gestok-table-body">
        @if($creditNotes->isEmpty())
            <p class="no-data">No hay notas de crédito registradas.</p>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Venta Relacionada</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($creditNotes as $note)
                        <tr>
                            <td>{{ $note->codigo_generacion ?? '—' }}</td>
                            <td>{{ $note->customer->nombre ?? 'Sin cliente' }}</td>
                            <td>{{ $note->sale->invoice_number ?? 'N/A' }}</td>
                            <td>{{ \Carbon\Carbon::parse($note->credit_note_date)->format('d/m/Y') }}</td>
                            <td>${{ number_format($note->total_amount, 2) }}</td>
                            <td class="actions">
                                <a href="{{ route('stores.creditnotes.show', [$store->id, $note->id]) }}" class="btn-view">Ver</a>
                                <form action="{{ route('stores.creditnotes.destroy', [$store->id, $note->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar esta nota de crédito?');">
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
