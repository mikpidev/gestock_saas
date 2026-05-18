@extends('layouts.admin')

@section('content')

<div class="container-fluid mt-4">

    <!-- Accesos rápidos como tarjetas grandes -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div type="button" class="card text-center shadow-sm p-3 touch-card" onclick="location.href='{{ route('store_tax_info.show', $store->id) }}'">
                <i class="bi bi-receipt display-4 mb-2"></i>
                <h5>Info Fiscal</h5>
            </div>
        </div>



            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm p-3 touch-card" onclick="location.href='{{ route('stores.sales.index', $store->id) }}'">
                    <i class="bi bi-cart-check display-4 mb-2"></i>
                    <h5>Todas las Ventas</h5>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm p-3 touch-card">
                    <h6>Total Hoy</h6>
                    <h3>${{ number_format($salesTodayTotal ?? 0, 2) }}</h3>
                    <small>{{ $salesTodayCount ?? 0 }} ventas</small>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="card text-center shadow-sm p-3 touch-card">
                    <h6>Total Semana</h6>
                    <h3>${{ number_format($salesWeekTotal ?? 0, 2) }}</h3>
                    <small>{{ $salesWeekCount ?? 0 }} ventas</small>
                </div>
            </div>
        </div>

        <div class="card mb-4 p-3 shadow-sm" style="height: 300px; ">
            <h5 class="mb-3">Ventas Última Semana</h5>
            <canvas id="salesChart" style="height: 300px; width: 100%;"></canvas>
        </div>


        <!-- Últimas 5 ventas grandes para touch -->
        <div class="row g-3">
            @forelse($sales as $sale)
            <div class="col-12 col-md-6">
                <div class="card touch-card p-3 shadow-sm" style="cursor: pointer;">
                    <div class="d-flex justify-content-between">
                        <div>
                            <strong>{{ $sale->customer->nombre ?? 'Cliente desconocido' }}</strong><br>
                            <small>{{ $sale->created_at->format('d/m/Y') }}</small>
                        </div>
                        <div class="text-end">
                            <h5>${{ number_format($sale->total_amount ?? 0, 2) }}</h5>
                            @if($sale->estado === 'emitida')
                            <span class="badge bg-success">Emitida</span>
                            @elseif($sale->estado === 'anulada')
                            <span class="badge bg-danger">Anulada</span>
                            @else
                            <span class="badge bg-secondary">{{ ucfirst($sale->estado ?? 'N/A') }}</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @empty
            <div class="col-12">
                <div class="card text-center p-3 text-muted">No hay ventas registradas aún.</div>
            </div>
            @endforelse
        </div>

    </div>
    @endsection

    @section('scripts')
    <!-- Scripts con los datos -->
    <script id="weeklySalesLabels" type="application/json">
        {
            !!json_encode($weeklySalesLabels, JSON_UNESCAPED_UNICODE) !!
        }
    </script>

    <script id="weeklySalesData" type="application/json">
        {
            !!json_encode($weeklySalesData, JSON_UNESCAPED_UNICODE) !!
        }
    </script>


    @endsection