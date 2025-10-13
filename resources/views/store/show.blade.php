@extends('layouts.admin')

@section('content')
<div class="container mt-4">

    <!-- Sub-card: Información Fiscal -->
    <div class="d-flex justify-content-between align-items-center mb-3">

        @if($store->taxInfo)
        <a href="{{ route('store_tax_info.show', $store->id) }}" class="btn btn-sm btn-primary">
            <i class="bi bi-receipt"></i> Información Fiscal
        </a>
        @else
        <a href="{{ route('store_tax_info.create', $store->id) }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-plus-circle"></i> Agregar Información Fiscal
        </a>
        @endif
    </div>

    <!-- Main-card: Últimas 5 Ventas -->
    <div class="gestok-form-card" style="max-width: 900px;">
        <div class="gestok-form-header">
            <h1>Últimas 5 Ventas</h1>
            <p>Resumen de actividad reciente</p>
        </div>

        <div class="gestok-form-body" style="overflow-x: auto;">
            <table class="table table-striped" style="width: 100%; border-collapse: collapse;">
                <thead style="background: #f5f5f5;">
                    <tr>
                        <th style="padding: 0.6rem;">Fecha</th>
                        <th style="padding: 0.6rem;">Usuario</th>
                        <th style="padding: 0.6rem;">Cliente</th>
                        <th style="padding: 0.6rem; text-align: right;">Total</th>
                        <th style="padding: 0.6rem;">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td style="padding: 0.6rem;" class="text-nowrap">
                            {{ $sale->created_at->format('d/m/Y') }}
                        </td>
                        <td style="padding: 0.6rem;" class="text-nowrap">
                            {{ $sale->user->name ?? 'Factura' }}
                        </td>
                        <td style="padding: 0.6rem;">
                            {{ $sale->customer->nombre ?? 'Cliente desconocido' }}
                        </td>
                        <td style="padding: 0.6rem; text-align: right;">
                            ${{ number_format($sale->total_amount ?? 0, 2) }}
                        </td>
                        <td style="padding: 0.6rem;">
                            @if($sale->estado === 'emitida')
                                <span class="badge bg-success">Emitida</span>
                            @elseif($sale->estado === 'anulada')
                                <span class="badge bg-danger">Anulada</span>
                            @else
                                <span class="badge bg-secondary">{{ ucfirst($sale->estado ?? 'N/A') }}</span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" style="padding: 1rem; text-align: center; color: #999;">
                            No hay ventas registradas aún.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>

            <div class="gestok-form-actions mt-3">
                <a href="{{ route('stores.sales.index', $store->id) }}" class="btn btn-primary">Ver todas las ventas</a>
                <a href="{{ route('stores.index') }}" class="btn btn-secondary">Volver</a>
            </div>
        </div>
    </div>

</div>
@endsection
