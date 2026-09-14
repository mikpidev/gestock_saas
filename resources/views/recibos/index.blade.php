@extends('layouts.admin')

@section('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<div class="card-table">

    <div class="card-header-custom">
        <h2>Cortes de Caja</h2>

        <form action="{{ route('cash.closures.close', $store->id) }}" method="POST">
            @csrf
            <input type="hidden" name="store_id" value="{{ $store->id }}">
            <button type="submit" class="btn-new" title="Crear corte de caja">
                <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
                    <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
                </svg>
            </button>
        </form>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:10px;margin:12px;border-radius:6px;">
        {{ session('success') }}
    </div>
    @endif

    @if($closures->isEmpty())
    <div class="no-data">No hay cortes de caja registrados todavía.</div>
    @else

    <table class="table table-hover">
        <thead>
            <tr>
                <th>Folio</th>
                <th>Cajero</th>
                <th>Desde Venta</th>
                <th>Hasta Venta</th>
                <th>Apertura</th>
                <th>Cierre</th>
                <th>Ventas</th>
                <th>NC (cant.)</th>
                <th>NC (monto)</th>
                <th>ND (cant.)</th>
                <th>ND (monto)</th>
                <th>Total Ventas</th>
                <th>Efectivo</th>
                <th>Tarjeta</th>
                <th width="120">Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach($closures as $closure)
            <tr>
                <td>{{ $closure->folio ?? $closure->id }}</td>
                <td>{{ $closure->cashier->nombre ?? $closure->cashier_name ?? '—' }}</td>
                <td>{{ $closure->from_sale_id }}</td>
                <td>{{ $closure->to_sale_id }}</td>
                <td>{{ optional($closure->opened_at)->format('d/m/Y H:i') ?? '—' }}</td>
                <td>{{ optional($closure->closed_at ?? $closure->created_at)->format('d/m/Y H:i') ?? '—' }}</td>
                <td>{{ $closure->total_sales }}</td>
                <td>{{ $closure->total_credit_notes }}</td>
                <td>${{ number_format($closure->credit_notes_amount ?? 0, 2) }}</td>
                <td>{{ $closure->total_debit_notes }}</td>
                <td>${{ number_format($closure->debit_notes_amount ?? 0, 2) }}</td>
                <td>${{ number_format($closure->amount_sales, 2) }}</td>
                <td>${{ number_format($closure->total_cash, 2) }}</td>
                <td>${{ number_format($closure->total_card, 2) }}</td>
                <td class="actions">
                    <div class="d-flex justify-content-center align-items-center gap-2">
                        <button
                            class="btn btn-outline-secondary btn-sm"
                            title="Imprimir corte"
                            onclick="mostrarModalImpresion('{{ route('cash.closures.print', [$store->id, $closure->id]) }}')">
                            <i class="bi bi-printer"></i>
                        </button>
                    </div>
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