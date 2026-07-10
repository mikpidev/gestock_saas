@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
<style>
    .btn-print {
        background: #10b981;
        color: #fff;
        border: none;
        padding: 6px 10px;
        border-radius: 6px;
        font-weight: 600;
        display: flex;
        align-items: center;
        gap: 6px;
        cursor: pointer;
    }

    .btn-print:hover {
        background: #059669;
    }
</style>
<div class="container">
    <h2 class="mb-3">Cortes de Caja</h2>

    {{-- BOTÓN PARA REALIZAR NUEVO CORTE --}}
    <form action="{{ route('cash.closures.close', $store->id) }}" method="POST" class="mb-4">
        @csrf
        <input type="hidden" name="store_id" value="{{ $store->id }}"> {{-- Cambia según tu tienda --}}
        <button type="submit" class="btn btn-edit">
            Crear corte de caja
        </button>
    </form>


    {{-- SI NO HAY CIERRES --}}
    @if($closures->isEmpty())
    <div class="alert alert-warning">No hay cortes de caja registrados todavía.</div>
    @else

    <table class="table table-bordered">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Desde Venta</th>
                <th>Hasta Venta</th>
                <th>Ventas</th>
                <th>NC</th>
                <th>ND</th>
                <th>Total Ventas</th>
                <th>Efectivo</th>
                <th>Tarjeta</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($closures as $closure)
            <tr>
                <td>{{ $closure->id }}</td>
                <td>{{ $closure->from_sale_id }}</td>
                <td>{{ $closure->to_sale_id }}</td>
                <td>{{ $closure->total_sales }}</td>
                <td>{{ $closure->total_credit_notes }}</td>
                <td>{{ $closure->total_debit_notes }}</td>
                <td>${{ number_format($closure->amount_sales, 2) }}</td>
                <td>${{ number_format($closure->total_cash, 2) }}</td>
                <td>${{ number_format($closure->total_card, 2) }}</td>
                <td>{{ $closure->created_at->format('d/m/Y H:i') }}</td>
                <td>
                    <button onclick="mostrarModalImpresion('{{ route('cash.closures.print', [$store->id, $closure->id]) }}')" class="btn btn-print">
                        <i class="bi bi-printer"></i> Imprimir Corte
                    </button>

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
</script>

@endsection