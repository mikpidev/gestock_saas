<input type="hidden" name="company_id" value="{{ $store->company->id }}">
<input type="hidden" name="store_id" value="{{ $store->id }}">

{{-- NIT --}}
<div class="row mb-3 justify-content-center align-items-center">

    <div class="col-md-6">
        <label for="nit">
            NIT
        </label>
    </div>
    <div class="col-md-6">
        <input type="text" name="nit" id="edit_nit" value="{{ old('nit', $storeTaxInfo->nit ?? '') }}" class="form-control" required>
        @error('nit') <div class="text-danger">{{ $message }}</div> @enderror
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
        <input type="text" name="nrc" id="edit_nrc" class="form-control" value="{{ old('nrc', $storeTaxInfo->nrc ?? '') }}" required>
        @error('nrc') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Razón Social --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="razon_social">Razón Social</label>
    </div>
    <div class="col-md-6">
        <input type="text" name="razon_social" id="razon_social" class="form-control" value="{{ old('razon_social', $storeTaxInfo->razon_social ?? '') }}" required>
        @error('razon_social') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Nombre Comercial --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="actividad_economica">
            Nombre Comercial
        </label>
    </div>
    <div class="col-md-6">
        <input type="text" name="actividad_economica" id="actividad_economica" class="form-control" value="{{ old('actividad_economica', $storeTaxInfo->actividad_economica ?? '') }}" required>
        @error('actividad_economica') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Actividad Económica --}}

<div class="row mb-3 justify-content-center align-items-center">

    <div class="col-md-6">
        <label for="codActividad">Actividad Económica</label>
    </div>


    <div class="col-md-6">
        <select id="codActividad" name="codActividad" class="select2" required>
            <option value="">Seleccione</option>
            @foreach($actividades as $act)
            <option value="{{ $act->codigo }}" {{ old('codActividad', $storeTaxInfo->codActividad ?? '') == $act->codigo ? 'selected' : '' }}>
                {{ $act->codigo }} - {{ $act->nombre }}
            </option>
            @endforeach
        </select>
        @error('codActividad') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>




{{-- Departamento --}}

<div class="row mb-3 justify-content-center align-items-center">

    <div class="col-md-6">
        <label for="direccion_departamento">Departamento</label>
    </div>


    <div class="col-md-6">
        <select id="direccion_departamento" name="direccion_departamento" class="select2" required>
            <option value="">Seleccione</option>
            @foreach($departamentos as $dep)
            <option value="{{ $dep->codigo }}"
                {{ old('direccion_departamento', $storeTaxInfo->direccion_departamento ?? '') == $dep->codigo ? 'selected' : '' }}>
                {{ $dep->codigo }} - {{ $dep->nombre }}
            </option>
            @endforeach
        </select>
        @error('direccion_departamento') <div class="text-danger">{{ $message }}</div> @enderror
    </div>

</div>

{{-- Municipio --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="direccion_municipio">Municipio</label>
    </div>
    <div class="col-md-6">
        <select id="direccion_municipio" name="direccion_municipio" class="select2" required>
            <option value="">Seleccione</option>
            @foreach($municipios as $mun)
            <option value="{{ $mun->codigo }}" {{ old('direccion_municipio', $storeTaxInfo->direccion_municipio ?? '') == $mun->codigo ? 'selected' : '' }}>
                {{ $mun->codigo }} - {{ $mun->nombre }}
            </option>
            @endforeach
        </select>
        @error('direccion_municipio') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Dirección Fiscal --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="direccion_fiscal">Dirección Fiscal</label>
    </div>
    <div class="col-md-6">
        <input type="text" name="direccion_fiscal" id="direccion_fiscal" class="form-control" value="{{ old('direccion_fiscal', $storeTaxInfo->direccion_fiscal ?? '') }}" required>
        @error('direccion_fiscal') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Teléfono --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="telefono">Teléfono</label>
    </div>
    <div class="col-md-6">
        <input type="text" name="telefono" id="telefono" class="form-control" maxlength="15" value="{{ old('telefono', $storeTaxInfo->telefono ?? '') }}">
        @error('telefono') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Correo Electrónico --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="email">Correo Electrónico</label>
    </div>
    <div class="col-md-6">
        <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $storeTaxInfo->email ?? '') }}">
        @error('email') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Estado --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="estado">Estado</label>
    </div>
    <div class="col-md-6">
        <select name="estado" id="estado" class="form-control" required>
            <option value="activo" {{ (old('estado', $storeTaxInfo->estado ?? '') == 'activo') ? 'selected' : '' }}>Activo</option>
            <option value="suspendido" {{ (old('estado', $storeTaxInfo->estado ?? '') == 'suspendido') ? 'selected' : '' }}>Suspendido</option>
            <option value="vencido" {{ (old('estado', $storeTaxInfo->estado ?? '') == 'vencido') ? 'selected' : '' }}>Vencido</option>
        </select>
        @error('estado') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Comentarios --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="comentarios">Comentarios</label>
    </div>
    <div class="col-md-6">
        <textarea name="comentarios" id="comentarios" class="form-control" rows="4">{{ old('comentarios', $storeTaxInfo->comentarios ?? '') }}</textarea>
        @error('comentarios') <div class="text-danger">{{ $message }}</div> @enderror
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

        <button type="submit"
            class="btn btn-modal"
            data-redirect="{{ route('stores.customers.index', $store->id) }}">

            <span class="gradient-text">
                Guardar
            </span>

        </button>

    </div>
</div>
<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    $(document).ready(function() {
        $('.select2').select2({
            placeholder: "Seleccione",
            allowClear: true,
            width: '100%'
        });
    });
</script>