@extends('layouts.admin')

@section('content')
<div class="container">
    <h4>Iniciar Contingencia</h4>

    <form action="{{ route('contingencias.store', $store->id) }}" method="POST">
        @csrf


        <div class="row">
            <div class="col-md-6 mb-2">
                <label>Fecha inicio</label>
                <input type="date" name="fecha_inicio" class="form-control" required>
            </div>

            <div class="col-md-6 mb-2">
                <label>Hora inicio</label>
                <input type="time" name="hora_inicio" class="form-control" required>
            </div>

            <div class="col-md-12 mb-2">
                <label>Tipo contingencia</label>
                <select name="tipo_contingencia" class="form-control" required>
                    <option value="">Seleccione</option>
                    @foreach($tipoContingencias as $tipo)
                    <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-12 mb-2">
                <label>Motivo</label>
                <textarea name="motivo_contingencia" class="form-control"></textarea>
            </div>
        </div>

        <button class="btn btn-success mt-3">Iniciar Contingencia</button>
        <a href="{{ route('contingencias.index', $store->id) }}" class="btn btn-secondary mt-3">
            Cancelar
        </a>
    </form>
</div>
@endsection