@extends('layouts.admin')

@section('content')
<div class="gestok-form-card">
    <div class="gestok-form-header">
        <h1>Detalle de Venta #{{ $sale->id }}</h1>
        <p>{{ $store->store_name }}</p>
    </div>

    <div class="gestok-form-body">
        <p><strong>Cliente:</strong> {{ $sale->customer->nombre }}</p>
        <p><strong>Fecha:</strong> {{ $sale->sale_date->format('d/m/Y') }}</p>
        <p><strong>Estado de pago:</strong> {{ ucfirst($sale->payment_status) }}</p>

        <h4>Productos</h4>
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th>Cantidad</th>
                    <th>Precio Unitario</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->details as $detail)
                <tr>
                    <td>{{ $detail->productType->name }}</td>
                    <td>{{ $detail->quantity }}</td>
                    <td>{{ number_format($detail->unit_price, 2) }}</td>
                    <td>{{ number_format($detail->quantity * $detail->unit_price, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <p><strong>Total:</strong> {{ number_format($sale->total, 2) }}</p>
    </div>

    <div class="gestok-form-actions mt-3">
        <a href="{{ route('stores.sales.index', $store->id) }}" class="btn btn-secondary">Volver</a>
        <a href="{{ route('stores.sales.edit', [$store->id, $sale->id]) }}" class="btn btn-primary">Editar</a>
    </div>
</div>
@endsection
