@extends('layouts.admin')
@section('content')

<div class="row g-3 mb-4">

    <button type="button" class="filter-svg" data-bs-toggle="modal" data-bs-target="#filtros">
        <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">

            <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
        </svg>
    </button>

    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card" onclick="location.href='{{ route('stores.sales.index', $store->id) }}'">
            <i class="bi bi-cart-check display-4 mb-2"></i>
            <h5>Todas las Ventas</h5>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card">
            <h6>Total Hoy</h6>
            <h3 id="salesTodayTotalCard"></h3>
            <small id="salesTodayCountCard"></small>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card">
            <h6>Total Semana</h6>
            <h3 id="salesWeekTotalCard"></h3>
            <small id="salesWeekCountCard"></small>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card">
            <h6>Total Mes</h6>
            <h3 id="salesMonthTotalCard"></h3>
            <small id="salesMonthCountCard"></small>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card">
            <h6>DTE Aprobadas</h6>
            <h3 id="dteAprovedCard"></h3>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card">
            <h6>DTE Rechazadas</h6>
            <h3 id="dteDenyCard"></h3>
        </div>
    </div>


</div>

<div class="container">
    <div class="card mb-4 p-3 shadow-sm" style="height: 300px; ">
        <h5 class="mb-3">Ventas Última Semana</h5>
        <canvas id="salesChart" style="height: 300px; width: 100%;"></canvas>
    </div>
</div>


<div class="row g-3 mb-4">

    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card">
            <h5 class="mb-3">Metodo de pago mas utilizado</h5>
            <canvas id="paymentChart"></canvas>
        </div>
    </div>

    <div class="col-6 col-md-3">
        <div class="card text-center shadow-sm p-3 touch-card">
            <h5 class="mb-3">DTE Conteo</h5>
            <canvas id="dteChart"></canvas>
        </div>
    </div>
</div>

<!-- Modal filtros -->
<div class="modal fade" id="filtros" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Filtros</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET" class="filters">
                    <label>Desde:</label>
                    <input type="date" id="from" name="from" value="{{ request('from') ?? now()->subMonth()->toDateString() }}">

                    <label>Hasta:</label>
                    <input type="date" id="to" name="to" value="{{ request('to') ?? now()->toDateString() }}">

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="document.querySelector('.filters').submit()">Filtrar</button>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@section('scripts')
<script>
    window.storeId = {{$store -> id}};
</script>

@endsection