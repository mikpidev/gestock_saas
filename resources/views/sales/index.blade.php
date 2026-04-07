@extends('layouts.admin')

@section('content')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

<style>
    .card-table {
        background: #fff;
        width: 100%;
        border-radius: 12px;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.10);
        overflow: hidden;
        margin: 2rem auto;
        max-width: 1100px;
    }

    .card-header-custom {
        background: #1f2937;
        color: #fff;
        padding: 1.2rem;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .card-header-custom h2 {
        font-size: 1.4rem;
        margin: 0;
        font-weight: 600;
    }

    .btn-new {
        background: #3b82f6;
        padding: 8px 14px;
        border-radius: 6px;
        font-weight: 600;
        color: #fff;
        text-decoration: none;
        display: flex;
        gap: 6px;
        align-items: center;
    }

    .btn-new:hover {
        background: #2563eb;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        font-size: 0.95rem;
    }

    th {
        background: #f3f4f6;
        font-weight: 600;
    }

    th,
    td {
        padding: 12px;
        border-bottom: 1px solid #e5e7eb;
    }

    tr:hover {
        background: #fafafa;
    }



    .btn-act {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 6px;
        font-size: 13px;
        padding: 6px 10px;
        background: fixed #10b981;
        color: #fff;
        border: none;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-act:hover {
        background: #059669;
    }

    .btn {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 5px;
        border-radius: 6px;
        font-size: 13px;
        padding: 6px 10px;
        color: #fff;
        border: none;
        cursor: pointer;
        transition: 0.2s;
    }

    .btn-info {
        background: #3b82f6;
    }

    .btn-info:hover {
        background: #2563eb;
    }

    .btn-print {
        background: #10b981;
    }

    .btn-print:hover {
        background: #059669;
    }

    .btn-email {
        background: #10b981;
    }

    .btn-email:hover {
        background: #059669;
    }

    .btn-delete {
        background: #ef4444;
    }

    .btn-delete:hover {
        background: #dc2626;
    }

    .badge {
        padding: 5px 8px;
        font-size: 11px;
        border-radius: 6px;
        font-weight: bold;
        color: #fff;
    }

    .badge-procesado {
        background: #10b981;
    }

    .badge-rechazado {
        background: #ef4444;
    }

    .badge-pendiente {
        background: #ca8a04;
    }

    .no-data {
        text-align: center;
        padding: 2rem;
        color: #6b7280;
        font-style: italic;
    }

    .filter-bar {
        background: #f9fafb;
        padding: 12px;
        display: flex;
        gap: 8px;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .filter-bar input {
        display: inline-block;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 6px;
    }


    .filters {
        background: #f9fafb;
        padding: 12px;
        gap: 8px;
        align-items: center;
        border-bottom: 1px solid #e5e7eb;
    }

    .filters input {
        display: inline-block;
        border: 1px solid #d1d5db;
        border-radius: 6px;
        padding: 6px;
    }
</style>

<div class="card-table">

    <div class="card-header-custom">
        <h2>{{ $store->store_name }} — Ventas</h2>
        <a href="{{ route('stores.sales.create', $store->id) }}" class="btn-new">
            <i class="bi bi-plus-circle"></i> Nueva venta
        </a>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:10px;margin:12px;border-radius:6px;">
        {{ session('success') }}
    </div>
    @endif

    <form method="GET" class="filter-bar">
        <!-- Button trigger modal -->
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#filtros">
            Filtros
        </button>
        <a href="?from={{ now()->toDateString() }}&to={{ now()->toDateString() }}" class="btn btn-print">
            <i class="bi bi-calendar-check"></i> Hoy
        </a>

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#reportes">
            Reportes
        </button>


    </form>



    @if($sales->isEmpty())
    <div class="no-data">No hay ventas para <b>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</b> a <b>{{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</b></div>
    @else

    <table>
        <thead>
            <tr>
                <th># Código</th>
                <th>Cliente</th>
                <th>Total</th>
                <th>Fecha</th>
                <th>DTE</th>
                <th width="200">Acciones</th>
            </tr>
        </thead>
        <tbody>

            @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->codigo_generacion ?? '—' }}</td>
                <td>{{ $sale->customer->nombre ?? 'Sin cliente' }}</td>
                <td>${{ number_format($sale->net_amount, 2) }}</td>
                <td>{{ $sale->sale_date->format('d/m/Y H:i') }}</td>

                <td>
                    @php
                    $cls = 'badge-pendiente';
                    if($sale->dte_status === 'PROCESADO') $cls = 'badge-procesado';
                    if($sale->dte_status === 'RECHAZADO') $cls = 'badge-rechazado';
                    @endphp

                    @if(!$sale->dte_status || $sale->dte_status === 'PENDIENTE')
                    <form action="{{ route('stores.sales.refreshDTE', [$store->id, $sale->id]) }}" method="POST">
                        @csrf
                        <button class="badge {{ $cls }}" title="Actualizar DTE">
                            {{ $sale->dte_status ?? 'PENDIENTE' }}
                        </button>
                    </form>
                    @else
                    <span class="badge {{ $cls }}">{{ $sale->dte_status }}</span>
                    @endif
                </td>

                <td class="actions">
                    <div class="dropdown">
                        <button class="btn-act btn-secondary dropdown-toggle"
                            type="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Acciones
                        </button>

                        <ul class="dropdown-menu">

                            {{-- Ver DTE --}}
                            <li>
                                <button class="dropdown-item"
                                    onclick="mostrarDTE('{{ route('dte.public', $sale->codigo_generacion) }}')">
                                    <i class="bi bi-eye"></i> Ver DTE
                                </button>
                            </li>

                            {{-- Imprimir --}}
                            <li>
                                <button class="dropdown-item"
                                    onclick="mostrarModalImpresion('{{ route('ticket.print', [$store->id, $sale->id]) }}')">
                                    <i class="bi bi-printer"></i> Imprimir
                                </button>
                            </li>

                            {{-- Enviar correo --}}
                            <li>
                                <button class="dropdown-item"
                                    onclick="enviarDTEPorCorreo('{{ route('stores.email.send', [$store->id, $sale->id]) }}')">
                                    <i class="bi bi-envelope"></i> Enviar correo
                                </button>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            {{-- Eliminar --}}
                            @php
                            $canDelete = $sale->created_at->greaterThan(now()->subHours(24));
                            @endphp

                            @if ($canDelete)
                            <li>
                                <form action="{{ route('stores.sales.destroy', [$store->id, $sale->id]) }}"
                                    method="POST"
                                    onsubmit="return confirm('¿Eliminar venta?');">
                                    @csrf
                                    @method('DELETE')
                                    <button class="dropdown-item text-danger">
                                        <i class="bi bi-trash"></i> Eliminar
                                    </button>
                                </form>
                            </li>
                            @else
                            <li>
                                <span class="dropdown-item text-muted"
                                    data-bs-toggle="tooltip"
                                    title="No se puede eliminar ventas con más de 24 horas">
                                    <i class="bi bi-trash"></i> Eliminar
                                </span>
                            </li>
                            @endif

                        </ul>
                    </div>
                </td>

            </tr>
            @endforeach

        </tbody>
    </table>

    @endif

</div>

<!-- MODAL PARA VER DTE -->
<div class="modal fade" id="modalDTE" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width:900px;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista del Documento Tributario Electrónico</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body" style="height:80vh; overflow:auto; padding:0;">
                <iframe id="iframeDTE"
                    style="width:100%; height:100%; border:0;">
                </iframe>
            </div>

            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">
                    Cerrar
                </button>
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
                    <input type="date" name="from" value="{{ request('from', now()->toDateString()) }}">

                    <label>Hasta:</label>
                    <input type="date" name="to" value="{{ request('to', now()->toDateString()) }} ">

                    <label>Cliente:</label>
                    <select name="customer_id" class="form-control">
                        <option value="">Seleccionar cliente</option>
                        <option value="">-- Selecciona Cliente --</option>
                        @foreach($customers as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->nombre }}</option>
                        @endforeach
                    </select>

                    <label>Código de Generación:</label>
                    <input type="text" name="codigo_generacion" value="{{ request('codigo_generacion') }}" class="form-control" placeholder="Código de generación">

                    <label>Estado DTE:</label>
                    <select name="dte_status" class="form-control">
                        <option value="">Seleccionar estado</option>
                        @foreach($dteStatuses as $status)
                        <option value="{{ $status }}" {{ request('dte_status') == $status ? 'selected' : '' }}>
                            {{ $status }}
                        </option>
                        @endforeach
                    </select>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="document.querySelector('.filters').submit()">Filtrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal filtros -->
<div class="modal fade" id="reportes" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Generar Reportes</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET" class="filter-bar" action="{{ route('reportes.ventas') }}">
                    <label>Desde:</label>
                    <input type="date" name="from" value=" {{ $dateFrom }} ">

                    <label>Hasta:</label>
                    <input type="date" name="to" value="{{ $dateTo }} ">


            </div>
            <div class="modal-footer">
                <a href="{{ route('reportes.ventas', ['from' => $dateFrom, 'to' => $dateTo]) }}">
                    <button type="button" class="btn btn-primary" onclick="document.querySelector('.filter-bar').submit()">Generar Reporte</button>
            </div>
            </form>

        </div>
    </div>
</div>

<script>
    function mostrarModalImpresion(url) {
        const w = window.open(url, '_blank', 'width=400,height=800');
        w.onload = () => w.print();
    }
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el)
        })
    });


    function mostrarDTE(url) {
        const w = window.open(url, '_blank', 'width=800,height=800');
        w.onload = () => w.print();
    }
    document.addEventListener("DOMContentLoaded", function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
        tooltipTriggerList.map(function(el) {
            return new bootstrap.Tooltip(el)
        })
    });


    function enviarDTEPorCorreo(url) {
        if (confirm('¿Enviar DTE por correo electrónico?')) {
            fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json',
                    }
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.redirect) {
                        window.location.href = data.redirect;
                    }
                });
        }
    }
</script>
@endsection