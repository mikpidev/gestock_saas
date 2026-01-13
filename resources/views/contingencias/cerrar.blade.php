@extends('layouts.admin')

@section('content')
<div class="container">
    <h4>Cerrar Contingencia</h4>

    <div class="alert alert-warning">
        <strong>Atención:</strong> Se asociarán todos los documentos pendientes
        desde el inicio de la contingencia hasta el cierre.
    </div>

    <ul>
        <li>Ventas pendientes: <strong>{{ $ventas }}</strong></li>
        <li>Notas débito pendientes: <strong>{{ $nd }}</strong></li>
        <li>Notas crédito pendientes: <strong>{{ $nc }}</strong></li>
    </ul>

    <form method="POST" action="{{ route('contingencias.cerrar.post', $contingencia->id) }}">
        @csrf

        <div class="row">
            <div class="col-md-6">
                <label>Fecha fin</label>
                <input type="date" name="fecha_fin" class="form-control" required>
            </div>

            <div class="col-md-6">
                <label>Hora fin</label>
                <input type="time" name="hora_fin" class="form-control" required>
            </div>
        </div>

        <button class="btn btn-danger mt-3">
            Cerrar Contingencia
        </button>

        <a href="{{ route('contingencias.index') }}" class="btn btn-secondary mt-3">
            Cancelar
        </a>
    </form>
</div>
@endsection
