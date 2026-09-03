@extends('layouts.admin')
@section('content')

<div class="row g-3 mb-4">

    <div class="chart-container">

        <!-- Botón filtros -->
        <div class="d-flex justify-content-end mb-3">
            <button type="button"
                class="date-filter-btn"
                data-bs-toggle="modal"
                data-bs-target="#filtros">

                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-calendar-week" viewBox="0 0 16 16">
                    <path d="M11 6.5a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm-5 3a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5zm3 0a.5.5 0 0 1 .5-.5h1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-1a.5.5 0 0 1-.5-.5z" />
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
                </svg>

                <span>
                    {{ \Carbon\Carbon::parse(request('from') ?? now()->subMonth())->format('F Y') }}
                    -
                    {{ \Carbon\Carbon::parse(request('to') ?? now())->format('F Y') }}
                </span>

                <svg xmlns="http://www.w3.org/2000/svg"
                    width="10"
                    height="10"
                    fill="currentColor"
                    class="bi bi-chevron-down"
                    viewBox="0 0 16 16">
                    <path fill-rule="evenodd"
                        d="M1.5 5.5a.5.5 0 0 1 .5-.5h12a.5.5 0 0 1 .374.832l-6 7a.5.5 0 0 1-.748 0l-6-7A.5.5 0 0 1 1.5 5.5" />
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

                <div class="card-dashboard">
                    <h5 class="card-title mb-3">Top Productos</h5>

                    <div class="table-responsive table-scroll">
                        <table id="topProductsTable" class="table table-hover align-middle mb-0">
                            <thead>
                                <tr>
                                    <th width="60">Posicion</th>
                                    <th>Producto</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>

                            <tbody>

                            </tbody>
                        </table>
                    </div>
                </div>

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

                <!-- Gráfico principal -->
                <div class="card shadow-sm p-3 mb-3" style="height:400px;">
                    <h5>Horas Pico</h5>
                    <canvas id="peakHoursChart"></canvas>
                </div>

                <!-- Aquí puedes agregar otro gráfico como Horas Pico -->

            </div>

            <!-- DERECHA -->
            <div class="col-lg-2">

                <div class="card-approved text-center shadow-sm p-3 mb-3">
                    <h6>DTE Aprobadas</h6>
                    <h2 id="dteAprovedCard"></h2>
                </div>

                <div class="card-deny text-center shadow-sm p-3 mb-3">
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

                        <label>Estado de DTE:</label>
                        <select name="dte_status" id="dte_status" class="form-control">
                            <option value="PROCESADO" {{ request('dte_status') == 'PROCESADO' ? 'selected' : '' }}>PROCESADO</option>
                            <option value="RECHAZADO" {{ request('dte_status') == 'RECHAZADO' ? 'selected' : '' }}>RECHAZADO</option>
                            <option value="PENDIENTE" {{ request('dte_status') == 'PENDIENTE' ? 'selected' : '' }}>PENDIENTE</option>
                        </select>

                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="document.querySelector('.filters').submit()">Filtrar</button>

                    <a href="#"
                        id="download-pdf"
                        class="btn btn-primary"
                        title="Generar reporte PDF">
                        Descargar Reporte
                    </a>
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

    document.getElementById('download-pdf').addEventListener('click', function(e) {

        e.preventDefault();

        const from = document.getElementById('from').value;
        const to = document.getElementById('to').value;
        const dteStatus = document.getElementById('dte_status').value;

        const url = new URL(
            "{{ route('stores.dashboard.download-csv', ['store' => $store->id]) }}",
            window.location.origin
        );

        url.searchParams.set('from', from);
        url.searchParams.set('to', to);
        url.searchParams.set('dte_status', dteStatus);

        window.open(url.toString(), '_blank');
    });
</script>

@endsection