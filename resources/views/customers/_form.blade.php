@php $pfx = $idPrefix ?? 'edit'; @endphp
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="{{ $pfx }}_tipodocumento">Tipo de Documento</label>
    </div>


    <div class="col-md-6">
        <select id="{{ $pfx }}_tipodocumento" name="tipoDocumento" class="select2 form-control">
            <option value="">Seleccione</option>
            @foreach($tiposDocumento as $tipo)
            <option value="{{ $tipo->codigo }}"
                {{ old('tipoDocumento', $customer->tipoDocumento ?? '') == $tipo->codigo ? 'selected' : '' }}>
                {{ $tipo->codigo }} - {{ $tipo->nombre }}
            </option>
            @endforeach
        </select>
    </div>
    @error('tipoDocumento')
    <div class="text-danger">{{ $message }}</div> @enderror

</div>


{{-- Número de Documento --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="numDocumento">
            Número de Documento
        </label>
    </div>

    <div class="col-md-6">
        <input id="{{ $pfx }}_numdocumento" type="text" name="numDocumento" maxlength="14"
            value="{{ old('numDocumento', $customer->numDocumento ?? '') }}" class="form-control">
        @error('numDocumento') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- NRC --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="nrc">
            NRC
        </label>
    </div>

    <div class="col-md-6">
        <input id="{{ $pfx }}_nrc" type="text" name="nrc" maxlength="10"
            value="{{ old('nrc', $customer->nrc ?? '') }}" class="form-control">
        @error('nrc') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="nombre">Nombre *</label>
    </div>
    <div class="col-md-6">
        <input id="{{ $pfx }}_nombre" type="text" name="nombre" value="{{ old('nombre', $customer->nombre ?? '') }}" class="form-control" required>
        @error('nombre') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="nombreComercial">Nombre Comercial</label>
    </div>
    <div class="col-md-6">
        <input id="{{ $pfx }}_nombrecomercial" type="text" name="nombreComercial" value="{{ old('nombreComercial', $customer->nombreComercial ?? '') }}" class="form-control">
        @error('nombreComercial') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>



{{-- Actividad Económica --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="codActividad">Actividad Económica</label>
    </div>
    <div class="col-md-6">
        <select id="{{ $pfx }}_codactividad" name="codActividad" class="select2 form-control">
            <option value="">Seleccione</option>
            @foreach($actividades as $act)
            <option value="{{ $act->codigo }}"
                {{ old('codActividad', $customer->codActividad ?? '') == $act->codigo ? 'selected' : '' }}>
                {{ $act->codigo }} - {{ $act->nombre }}
            </option>
            @endforeach
        </select>
        @error('codActividad') 
        <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="descActividad">Descripción de Actividad</label>
    </div>
    <div class="col-md-6">
        <input id="{{ $pfx }}_descactividad" type="text" name="descActividad"
            value="{{ old('descActividad', $customer->descActividad ?? '') }}" class="form-control">
        @error('descActividad') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Departamento --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="{{ $pfx }}_departamento_id">Departamento *</label>
    </div>

    <div class="col-md-6">
        <select
            id="{{ $pfx }}_departamento_id"
            name="departamento_id"
            class="select2 form-control">

            <option value="">Seleccione</option>

            @foreach($departamentos as $dep)
            <option
                value="{{ $dep->id }}"
                {{ old('departamento_id', $customer->departamento_id ?? '') == $dep->id ? 'selected' : '' }}>
                {{ $dep->codigo }} - {{ $dep->nombre }}
            </option>
            @endforeach

        </select>

        @error('departamento_id')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>


{{-- Municipio --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="{{ $pfx }}_municipio_id">Municipio *</label>
    </div>

    <div class="col-md-6">
        <select
            id="{{ $pfx }}_municipio_id"
            name="municipio_id"
            class="select2 form-control">

            <option value="">Seleccione</option>

            @foreach($municipios as $mun)
            <option
                value="{{ $mun->id }}"
                {{ old('municipio_id', $customer->municipio_id ?? '') == $mun->id ? 'selected' : '' }}>
                {{ $mun->codigo }} - {{ $mun->nombre }}
            </option>
            @endforeach

        </select>

        @error('municipio_id')
        <div class="text-danger">{{ $message }}</div>
        @enderror

    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    {{-- Dirección Complementaria --}}
    <div class="col-md-6">
        <label for="direccion_complemento">Dirección Complementaria</label>
    </div>
    <div class="col-md-6">
        <input id="{{ $pfx }}_direccion_complemento" type="text" name="direccion_complemento"
            value="{{ old('direccion_complemento', $customer->direccion_complemento ?? '') }}" class="form-control">
        @error('direccion_complemento') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Teléfono --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="telefono">Teléfono</label>
    </div>
    <div class="col-md-6">
        <input id="{{ $pfx }}_telefono" type="text" name="telefono" maxlength="15"
            value="{{ old('telefono', $customer->telefono ?? '') }}" class="form-control">
        @error('telefono') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Correo --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="correo">Correo Electrónico</label>
    </div>
    <div class="col-md-6">
        <input id="{{ $pfx }}_correo" type="email" name="correo"
            value="{{ old('correo', $customer->correo ?? '') }}" class="form-control">
        @error('correo') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>



<!-- Botón -->
<div class="row justify-content-center">
    <div class="col-sm-8 d-flex justify-content-end gap-2">
        <button type="button"
            class="btn btn-modal"
            data-bs-dismiss="modal">

            <span class="gradient-text">
                Cerrar
            </span>

        </button>

        <button type="submit" class="btn btn-modal">
            <span class="gradient-text">
                Guardar
            </span>
        </button>

    </div>
</div>