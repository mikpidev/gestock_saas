@extends('layouts.admin')
@section('content')

<div class="row g-3 mb-4">

<div class="container-fluid">

    <!-- Botón filtros -->
    <div class="d-flex justify-content-end mb-3">
        <button type="button" class="filter-svg" data-bs-toggle="modal" data-bs-target="#filtros">
            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor"
                class="bi bi-funnel" viewBox="0 0 16 16">
                <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z"/>
            </svg>
        </button>
    </div>

    <div class="row g-3">

        <!-- IZQUIERDA -->
        <div class="col-lg-2">

            <div class="card shadow-sm p-3 mb-3">
                <h6 class="text-center">Método de Pago</h6>
                <canvas id="paymentChart"></canvas>
            </div>

            <!-- Aquí luego puedes poner Top Productos -->
            <!--
            <div class="card shadow-sm p-3">
                Top Productos
            </div>
            -->

        </div>

        <!-- CENTRO -->
        <div class="col-lg-8">

            <!-- Tarjetas -->
            <div class="row g-3 mb-3">

                <div class="col-md-4">
                    <div class="card text-center shadow-sm p-3">
                        <h6>Total Hoy</h6>
                        <h3 id="salesTodayTotalCard"></h3>
                        <small id="salesTodayCountCard"></small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card text-center shadow-sm p-3">
                        <h6>Total Semana</h6>
                        <h3 id="salesWeekTotalCard"></h3>
                        <small id="salesWeekCountCard"></small>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card text-center shadow-sm p-3">
                        <h6>Total Mes</h6>
                        <h3 id="salesMonthTotalCard"></h3>
                        <small id="salesMonthCountCard"></small>
                    </div>
                </div>

            </div>

            <!-- Gráfico principal -->
            <div class="card shadow-sm p-3 mb-3" style="height:400px;">
                <h5>Ventas Última Semana</h5>
                <canvas id="salesChart"></canvas>
            </div>

            <!-- Aquí puedes agregar otro gráfico como Horas Pico -->

        </div>

        <!-- DERECHA -->
        <div class="col-lg-2">

            <div class="card text-center shadow-sm p-3 mb-3">
                <h6>DTE Aprobadas</h6>
                <h2 id="dteAprovedCard"></h2>
            </div>

            <div class="card text-center shadow-sm p-3 mb-3">
                <h6>DTE Rechazadas</h6>
                <h2 id="dteDenyCard"></h2>
            </div>

            <div class="card shadow-sm p-3">
                <h6 class="text-center">Tipos DTE</h6>
                <canvas id="dteChart"></canvas>
            </div>

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

    document.addEventListener("DOMContentLoaded", () => {
        console.log("DOM Pagination listo");
        getData();
    });
</script>

@endsection