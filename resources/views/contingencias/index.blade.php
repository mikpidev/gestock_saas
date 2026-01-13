@extends('layouts.admin')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h4>Contingencias</h4>
        <a href="{{ route('contingencias.create', $store) }}" class="btn btn-primary">
            Iniciar contingencia
        </a>
    </div>

    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th>ID</th>
                <th>Código Generación</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($contingencias as $c)
            <tr>
                <td>{{ $c->id }}</td>
                <td>{{ $c->codigoGeneracion }}</td>
                <td>{{ $c->fecha_hora_inicio }}</td>
                <td>{{ $c->fecha_hora_fin ?? '—' }}</td>
                <td>
                    @if($c->fecha_hora_fin)
                    <span class="badge bg-success">CERRADA</span>
                    @else
                    <span class="badge bg-warning">ABIERTA</span>
                    @endif
                </td>
                <td>
                    @if(!$c->fecha_hora_fin)
                    <form action="{{ route('contingencias.cerrar', [
    'store' => $store->id,
    'contingencia' => $c->id
]) }}" method="POST">
                        @csrf
                        <button class="btn btn-sm btn-danger"
                            onclick="return confirm('¿Deseas cerrar esta contingencia?')">
                            Cerrar
                        </button>
                    </form>

                    @endif

                </td>

            </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection