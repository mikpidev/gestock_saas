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

.gestok-form-header {
    background: #000;
    color: #fff;
    padding: 1.5rem;
    text-align: center;
}

.gestok-form-header h1 {
    font-size: 1.6rem;
    font-weight: bold;
    margin: 0;
}

.gestok-form-header p {
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
    <div class="gestok-form-header">
        <h1>{{ isset($customer) ? 'Editar Cliente' : 'Nuevo Cliente' }}</h1>
        <p>{{ $store->store_name }}</p>
    </div>

    <div class="gestok-form-body">
        <form action="{{ isset($customer) 
            ? route('stores.customers.update', [$store->id, $customer->id]) 
            : route('stores.customers.store', $store->id) }}" method="POST">
            @csrf
            @if(isset($customer))
                @method('PUT')
            @endif

            {{-- Tipo de Documento --}}
            <label for="tipoDocumento">Tipo de Documento</label>
            <select id="tipoDocumento" name="tipoDocumento" class="select2" required>
                <option value="">Seleccione</option>
                @foreach($tiposDocumento as $tipo)
                    <option value="{{ $tipo->codigo }}" 
                        {{ old('tipoDocumento', $customer->tipoDocumento ?? '') == $tipo->codigo ? 'selected' : '' }}>
                        {{ $tipo->codigo }} - {{ $tipo->nombre }}
                    </option>
                @endforeach
            </select>
            @error('tipoDocumento') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Número de Documento --}}
            <label for="numDocumento">Número de Documento</label>
            <input id="numDocumento" type="text" name="numDocumento" maxlength="14"
                value="{{ old('numDocumento', $customer->numDocumento ?? '') }}" required>
            @error('numDocumento') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- NRC --}}
            <label for="nrc">NRC</label>
            <input id="nrc" type="text" name="nrc" maxlength="10"
                value="{{ old('nrc', $customer->nrc ?? '') }}">
            @error('nrc') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Nombre --}}
            <label for="nombre">Nombre</label>
            <input id="nombre" type="text" name="nombre"
                value="{{ old('nombre', $customer->nombre ?? '') }}" required>
            @error('nombre') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Nombre Comercial --}}
            <label for="nombreComercial">Nombre Comercial</label>
            <input id="nombreComercial" type="text" name="nombreComercial"
                value="{{ old('nombreComercial', $customer->nombreComercial ?? '') }}">
            @error('nombreComercial') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Actividad Económica --}}
            <label for="codActividad">Actividad Económica</label>
            <select id="codActividad" name="codActividad" class="select2" required>
                <option value="">Seleccione</option>
                @foreach($actividades as $act)
                    <option value="{{ $act->codigo }}"
                        {{ old('codActividad', $customer->codActividad ?? '') == $act->codigo ? 'selected' : '' }}>
                        {{ $act->codigo }} - {{ $act->nombre }}
                    </option>
                @endforeach
            </select>
            @error('codActividad') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="descActividad">Descripción de Actividad</label>
            <input id="descActividad" type="text" name="descActividad"
                value="{{ old('descActividad', $customer->descActividad ?? '') }}">
            @error('descActividad') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Departamento --}}
            <label for="direccion_departamento">Departamento</label>
            <select id="direccion_departamento" name="direccion_departamento" class="select2" required>
                <option value="">Seleccione</option>
                @foreach($departamentos as $dep)
                    <option value="{{ $dep->codigo }}"
                        {{ old('direccion_departamento', $customer->direccion_departamento ?? '') == $dep->codigo ? 'selected' : '' }}>
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
                    <option value="{{ $mun->codigo }}"
                        {{ old('direccion_municipio', $customer->direccion_municipio ?? '') == $mun->codigo ? 'selected' : '' }}>
                        {{ $mun->codigo }} - {{ $mun->nombre }}
                    </option>
                @endforeach
            </select>
            @error('direccion_municipio') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Dirección Complementaria --}}
            <label for="direccion_complemento">Dirección Complementaria</label>
            <input id="direccion_complemento" type="text" name="direccion_complemento"
                value="{{ old('direccion_complemento', $customer->direccion_complemento ?? '') }}">
            @error('direccion_complemento') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Teléfono --}}
            <label for="telefono">Teléfono</label>
            <input id="telefono" type="text" name="telefono" maxlength="15"
                value="{{ old('telefono', $customer->telefono ?? '') }}">
            @error('telefono') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Correo --}}
            <label for="correo">Correo Electrónico</label>
            <input id="correo" type="email" name="correo"
                value="{{ old('correo', $customer->correo ?? '') }}">
            @error('correo') <div class="text-danger">{{ $message }}</div> @enderror

            <div class="gestok-form-actions">
                <button type="submit" class="btn">
                    {{ isset($customer) ? 'Actualizar Cliente' : 'Crear Cliente' }}
                </button>
                <a href="{{ route('stores.customers.index', $store->id) }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js" integrity="sha256-oP6HI9z1XaZNBrJURtCoUT5SUnxFr8s3BzRl+cbzUq8=" crossorigin="anonymous"></script>

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
