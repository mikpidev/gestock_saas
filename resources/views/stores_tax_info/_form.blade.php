<style>
.gestok-form-card {
    background: #fff;
    color: #000;
    width: 100%;
    max-width: 450px;
    border-radius: 10px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
    overflow: hidden;
    margin: 2rem auto;
}

.gestok-form-header-2 {
    background: #000;
    color: #fff;
    padding: 1.5rem;
    text-align: center;
}

.gestok-form-header-2 h1 {
    font-size: 1.6rem;
    font-weight: bold;
    margin: 0;
}

.gestok-form-header-2 p {
    font-size: 0.9rem;
    margin-top: 0.5rem;
}

.gestok-form-body {
    padding: 2rem;
}

.gestok-form-body label {
    font-size: 0.9rem;
    display: block;
    margin-bottom: 0.3rem;
    font-weight: 500;
}

.gestok-form-body input[type="email"],
.gestok-form-body input[type="text"],
.gestok-form-body select {
    width: 100%;
    padding: 0.6rem;
    margin-bottom: 1rem;
    border: 1px solid #ccc;
    border-radius: 5px;
    font-size: 0.95rem;
    box-sizing: border-box;
}

.gestok-form-body .btn {
    background: #000;
    color: #fff;
    border: none;
    padding: 0.8rem 1.5rem;
    border-radius: 5px;
    cursor: pointer;
    font-size: 0.95rem;
    font-weight: bold;
    width: 100%;
    margin-bottom: 1rem;
    text-decoration: none;
    display: inline-block;
    text-align: center;
}

.gestok-form-body .btn:hover {
    background: #333;
}

.gestok-form-body .btn-secondary {
    background: #666;
    color: #fff;
}

.gestok-form-body .btn-secondary:hover {
    background: #555;
}

.gestok-form-body .text-danger {
    color: #dc3545;
    font-size: 0.8rem;
    margin-top: -0.8rem;
    margin-bottom: 0.8rem;
}

.gestok-form-actions {
    display: flex;
    gap: 0.5rem;
    flex-direction: column;
}

@media (min-width: 400px) {
    .gestok-form-actions {
        flex-direction: row;
    }

    .gestok-form-actions .btn {
        width: auto;
        flex: 1;
        margin-bottom: 0;
    }
}
</style>

<div class="gestok-form-card">
    <div class="gestok-form-header-2">
        <h1>{{ isset($storeTaxInfo) ? 'Editar Información Fiscal' : 'Nueva Información Fiscal' }}</h1>
        <p>{{ $store->nombre ?? 'Tienda' }}</p>
    </div>

    <div class="gestok-form-body">
        <form action="{{ isset($storeTaxInfo) 
            ? route('stores_tax_info.update', $storeTaxInfo->id) 
            : route('stores_tax_info.store', $store->id) }}" method="POST">
            @csrf
            @if(isset($storeTaxInfo))
                @method('PUT')
            @endif

            <input type="hidden" name="company_id" value="{{ $store->company->id }}">
            <input type="hidden" name="store_id" value="{{ $store->id }}">

            {{-- NIT --}}
            <label for="nit">NIT</label>
            <input type="text" name="nit" id="nit" class="form-control" value="{{ old('nit', $storeTaxInfo->nit ?? '') }}" required>
            @error('nit') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- NRC --}}
            <label for="nrc">NRC</label>
            <input type="text" name="nrc" id="nrc" class="form-control" value="{{ old('nrc', $storeTaxInfo->nrc ?? '') }}">
            @error('nrc') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Razón Social --}}
            <label for="razon_social">Razón Social</label>
            <input type="text" name="razon_social" id="razon_social" class="form-control" value="{{ old('razon_social', $storeTaxInfo->razon_social ?? '') }}" required>
            @error('razon_social') <div class="text-danger">{{ $message }}</div> @enderror



            {{-- Nombre Comercial --}}
            <label for="actividad_economica">Nombre Comercial</label>
            <input type="text" name="actividad_economica" id="actividad_economica" class="form-control" value="{{ old('actividad_economica', $storeTaxInfo->actividad_economica ?? '') }}" required>
            @error('actividad_economica') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Actividad Económica --}}
            <label for="codActividad">Actividad Económica</label>
            <select id="codActividad" name="codActividad" class="select2" required>
                <option value="">Seleccione</option>
                @foreach($actividades as $act)
                    <option value="{{ $act->codigo }}" {{ old('codActividad', $storeTaxInfo->codActividad ?? '') == $act->codigo ? 'selected' : '' }}>
                        {{ $act->codigo }} - {{ $act->nombre }}
                    </option>
                @endforeach
            </select>
            @error('codActividad') <div class="text-danger">{{ $message }}</div> @enderror




            
            {{-- Departamento --}}
            <label for="direccion_departamento">Departamento</label>
            <select id="direccion_departamento" name="direccion_departamento" class="select2" required>
                <option value="">Seleccione</option>
                @foreach($departamentos as $dep)
                    <option value="{{ $dep->id }}" {{ old('direccion_departamento', $storeTaxInfo->direccion_departamento ?? '') == $dep->id ? 'selected' : '' }}>
                    {{ $dep->codigo }} - {{ $dep->nombre }}
                    </option>
                @endforeach
            </select>
            @error('direccion_departamento') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Municipio --}}
            <label for="direccion_municipio">Municipio</label>
            <select id="direccion_municipio" name="direccion_municipio" class="select2" required>
                <option value="">Seleccione</option>
                @foreach($municipios as $mun)
                    <option value="{{ $mun->id }}" {{ old('direccion_municipio', $storeTaxInfo->direccion_municipio ?? '') == $mun->id ? 'selected' : '' }}>
                    {{ $mun->codigo }} - {{ $mun->nombre }}
                    </option>
                @endforeach
            </select>
            @error('direccion_municipio') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Dirección Fiscal --}}
            <label for="direccion_fiscal">Dirección Fiscal</label>
            <input type="text" name="direccion_fiscal" id="direccion_fiscal" class="form-control" value="{{ old('direccion_fiscal', $storeTaxInfo->direccion_fiscal ?? '') }}" required>
            @error('direccion_fiscal') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Teléfono --}}
            <label for="telefono">Teléfono</label>
            <input type="text" name="telefono" id="telefono" class="form-control" maxlength="15" value="{{ old('telefono', $storeTaxInfo->telefono ?? '') }}">
            @error('telefono') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Correo Electrónico --}}
            <label for="email">Correo Electrónico</label>
            <input type="email" name="email" id="email" class="form-control" value="{{ old('email', $storeTaxInfo->email ?? '') }}">
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Certificado de Firma Digital --}}
            <label for="cert_firma_digital">Certificado de Firma Digital</label>
            <input type="text" name="cert_firma_digital" id="cert_firma_digital" class="form-control" value="{{ old('cert_firma_digital', $storeTaxInfo->cert_firma_digital ?? '') }}" required>
            @error('cert_firma_digital') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Estado --}}
            <label for="estado">Estado</label>
            <select name="estado" id="estado" class="form-control" required>
                <option value="activo" {{ (old('estado', $storeTaxInfo->estado ?? '') == 'activo') ? 'selected' : '' }}>Activo</option>
                <option value="suspendido" {{ (old('estado', $storeTaxInfo->estado ?? '') == 'suspendido') ? 'selected' : '' }}>Suspendido</option>
                <option value="vencido" {{ (old('estado', $storeTaxInfo->estado ?? '') == 'vencido') ? 'selected' : '' }}>Vencido</option>
            </select>
            @error('estado') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Comentarios --}}
            <label for="comentarios">Comentarios</label>
            <textarea name="comentarios" id="comentarios" class="form-control" rows="4">{{ old('comentarios', $storeTaxInfo->comentarios ?? '') }}</textarea>
            @error('comentarios') <div class="text-danger">{{ $message }}</div> @enderror

            <div class="gestok-form-actions">
                <button type="submit" class="btn">
                    {{ isset($storeTaxInfo) ? 'Actualizar Información' : 'Crear Información' }}
                </button>
                <a href="{{ route('stores.show', $store->id) }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
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
