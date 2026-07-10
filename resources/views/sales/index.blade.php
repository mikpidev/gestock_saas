@extends('layouts.admin')

@section('content')

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">



<div class="card-table">

    <div class="card-header-custom">
        <h2>{{ $store->store_name }} — Ventas</h2>
        <a href="{{ route('stores.sales.create', $store->id) }}" class="btn-new">
            <svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
                <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
            </svg> </a>
    </div>

    @if(session('success'))
    <div style="background:#d1fae5;color:#065f46;padding:10px;margin:12px;border-radius:6px;">
        {{ session('success') }}
    </div>
    @endif

    <form method="GET" class="filter-bar">

        <div class="filter-actions">
            <!-- Button trigger modal -->
            <button type="button" class="filter-svg" data-bs-toggle="modal" data-bs-target="#filtros">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-funnel" viewBox="0 0 16 16">

                    <path d="M1.5 1.5A.5.5 0 0 1 2 1h12a.5.5 0 0 1 .5.5v2a.5.5 0 0 1-.128.334L10 8.692V13.5a.5.5 0 0 1-.342.474l-3 1A.5.5 0 0 1 6 14.5V8.692L1.628 3.834A.5.5 0 0 1 1.5 3.5zm1 .5v1.308l4.372 4.858A.5.5 0 0 1 7 8.5v5.306l2-.666V8.5a.5.5 0 0 1 .128-.334L13.5 3.308V2z" />
                </svg>
            </button>
            <a href="?from={{ now()->toDateString() }}&to={{ now()->toDateString() }}" class="">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-calendar-check" viewBox="0 0 16 16">
                    <path d="M10.854 7.146a.5.5 0 0 1 0 .708l-3 3a.5.5 0 0 1-.708 0l-1.5-1.5a.5.5 0 1 1 .708-.708L7.5 9.793l2.646-2.647a.5.5 0 0 1 .708 0" />
                    <path d="M3.5 0a.5.5 0 0 1 .5.5V1h8V.5a.5.5 0 0 1 1 0V1h1a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V3a2 2 0 0 1 2-2h1V.5a.5.5 0 0 1 .5-.5M1 4v10a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1V4z" />
                </svg> </a>

            <button type="button" class="filter-svg" data-bs-toggle="modal" data-bs-target="#reportes">
                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-archive" viewBox="0 0 16 16">
                    <path d="M0 2a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v2a1 1 0 0 1-1 1v7.5a2.5 2.5 0 0 1-2.5 2.5h-9A2.5 2.5 0 0 1 1 12.5V5a1 1 0 0 1-1-1zm2 3v7.5A1.5 1.5 0 0 0 3.5 14h9a1.5 1.5 0 0 0 1.5-1.5V5zm13-3H1v2h14zM5 7.5a.5.5 0 0 1 .5-.5h5a.5.5 0 0 1 0 1h-5a.5.5 0 0 1-.5-.5" />
                </svg>
            </button>

        </div>


    </form>



    @if($sales->isEmpty())
    <div class="no-data">No hay ventas para <b>{{ \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') }}</b> a <b>{{ \Carbon\Carbon::parse($dateTo)->format('d/m/Y') }}</b></div>
    @else

    <table>
        <thead>
            <tr>
                <th>Código de Generacion</th>
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
                <td>{{ $sale->created_at->format('d/m/Y H:i') }}</td>

                <td>
                    @php

                    $cls = 'badge-pendiente';
                    $mensaje = '';

                    if($sale->dte_status === 'PROCESADO') {
                    $cls = 'badge-procesado';
                    }

                    if($sale->dte_status === 'RECHAZADO') {

                    $cls = 'badge-rechazado';

                    $rechazo = $sale->dteResponses()
                    ->where('estado', 'RECHAZADO')
                    ->latest()
                    ->first();

                    if ($rechazo) {

                    $observaciones = $rechazo->observaciones ?? [];

                    $mensaje = is_array($observaciones)
                    ? implode('<br>', $observaciones)
                    : ($observaciones ?: 'DTE rechazado');
                    }
                    }

                    @endphp

                    @if(!$sale->dte_status || $sale->dte_status === 'PENDIENTE' || $sale->dte_status === 'RECHAZADO')
                    <form action="{{ route('stores.sales.refreshDTE', [$store->id, $sale->id]) }}" method="POST">
                        @csrf
                        <button class="badge {{ $cls }}" title="{{ $mensaje }}">
                            {{ $sale->dte_status ?? 'RECHAZADO' }}
                        </button>
                    </form>
                    @else
                    <span class="badge {{ $cls }}">{{ $sale->dte_status }}</span>
                    @endif
                </td>

                <td class="actions">
                    <div class="dropdown">

                        <button id="config-btn" class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-gear-wide-connected" viewBox="0 0 16 16">
                                <path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c-.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z" />
                            </svg>
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
                    <input id="from" type="date" name="from" value="{{ request('from', now()->toDateString()) }}">

                    <label>Hasta:</label>
                    <input id="to" type="date" name="to" value="{{ request('to', now()->toDateString()) }}">

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
                <button type="button" class="btn btn-edit" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-edit" onclick="document.querySelector('.filters').submit()">Filtrar</button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Reportes -->
<div class="modal fade" id="reportes" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h1 class="modal-title fs-5" id="staticBackdropLabel">Generar Reportes</h1>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="GET"
                    class="filter-bar"
                    action="{{ route('reportes.ventas', ['store' => $store->id, 'dateFrom' => $dateFrom, 'dateTo' => $dateTo]) }}">

                    <label>Desde:</label>
                    <input type="date" name="dateFrom" value="{{ $dateFrom }}">

                    <label>Hasta:</label>
                    <input type="date" name="dateTo" value="{{ $dateTo }}">
            </div>

            <div class="modal-footer">
                <button type="submit" class="btn btn-edit">
                    Generar Reporte
                </button>
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

        document.addEventListener("DOMContentLoaded", () => {
            getPaginationData();
            $("#from, #to").on("change", function() {
                getData();
            });
        });
    </script>
    @endsection