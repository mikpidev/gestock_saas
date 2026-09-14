@php
    $storeTaxInfo = $store->taxInfo;
@endphp

<input type="hidden" name="company_id" value="{{ $store->company_id }}">
<input type="hidden" name="store_id" value="{{ $store->id }}">

<div class="row mb-3">
    <label class="col-sm-4 col-form-label">NIT</label>
    <div class="col-sm-8">
        <input type="text" name="nit" class="form-control" value="{{ old('nit', $storeTaxInfo->nit ?? '') }}" required>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">NRC</label>
    <div class="col-sm-8">
        <input type="text" name="nrc" class="form-control" value="{{ old('nrc', $storeTaxInfo->nrc ?? '') }}" required>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Razón Social</label>
    <div class="col-sm-8">
        <input type="text" name="razon_social" class="form-control" value="{{ old('razon_social', $storeTaxInfo->razon_social ?? '') }}" required>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Nombre Comercial</label>
    <div class="col-sm-8">
        <input type="text" name="actividad_economica" class="form-control" value="{{ old('actividad_economica', $storeTaxInfo->actividad_economica ?? '') }}" required>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Actividad Económica</label>
    <div class="col-sm-8">
        <select name="codActividad" class="form-control js-select2" required>
            <option value="">Seleccione</option>
            @foreach($actividades as $act)
                <option value="{{ $act->codigo }}" @selected(old('codActividad', $storeTaxInfo->codActividad ?? '') == $act->codigo)>
                    {{ $act->codigo }} - {{ $act->nombre }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Departamento</label>
    <div class="col-sm-8">
        <select name="direccion_departamento" class="form-control js-select2" required>
            <option value="">Seleccione</option>
            @foreach($departamentos as $dep)
                <option value="{{ $dep->codigo }}" @selected(old('direccion_departamento', $storeTaxInfo->direccion_departamento ?? '') == $dep->codigo)>
                    {{ $dep->codigo }} - {{ $dep->nombre }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Municipio</label>
    <div class="col-sm-8">
        <select name="direccion_municipio" class="form-control js-select2" required>
            <option value="">Seleccione</option>
            @foreach($municipios as $mun)
                <option value="{{ $mun->codigo }}" @selected(old('direccion_municipio', $storeTaxInfo->direccion_municipio ?? '') == $mun->codigo)>
                    {{ $mun->codigo }} - {{ $mun->nombre }}
                </option>
            @endforeach
        </select>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Dirección Fiscal</label>
    <div class="col-sm-8">
        <input type="text" name="direccion_fiscal" class="form-control" value="{{ old('direccion_fiscal', $storeTaxInfo->direccion_fiscal ?? '') }}" required>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Teléfono</label>
    <div class="col-sm-8">
        <input type="text" name="telefono" class="form-control" maxlength="15" value="{{ old('telefono', $storeTaxInfo->telefono ?? '') }}">
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Correo Electrónico</label>
    <div class="col-sm-8">
        <input type="email" name="email" class="form-control" value="{{ old('email', $storeTaxInfo->email ?? '') }}">
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Estado</label>
    <div class="col-sm-8">
        <select name="estado" class="form-control" required>
            <option value="activo" @selected(old('estado', $storeTaxInfo->estado ?? '') == 'activo')>Activo</option>
            <option value="suspendido" @selected(old('estado', $storeTaxInfo->estado ?? '') == 'suspendido')>Suspendido</option>
            <option value="vencido" @selected(old('estado', $storeTaxInfo->estado ?? '') == 'vencido')>Vencido</option>
        </select>
    </div>
</div>
<div class="row mb-3">
    <label class="col-sm-4 col-form-label">Comentarios</label>
    <div class="col-sm-8">
        <textarea name="comentarios" class="form-control" rows="3">{{ old('comentarios', $storeTaxInfo->comentarios ?? '') }}</textarea>
    </div>
</div>
