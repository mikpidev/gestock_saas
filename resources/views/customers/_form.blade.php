<style>
    .gestok-form-card {
        background: #fff;
        color: #000;
        width: 100%;
        max-width: 450px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0,0,0,0.2);
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
    .gestok-form-body input[type="password"],
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
    .gestok-form-body select {
        background: #fff;
        cursor: pointer;
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
    .gestok-form-body .form-text {
        font-size: 0.8rem;
        color: #666;
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

                {{-- Solo para editar --}}
                @if(isset($customer))
                @method('PUT')
                @endif

            <label for="nombre">Nombre</label>
            <input id="nombre" type="text" name="nombre" value="{{ old('nombre', $customer->nombre ?? '') }}" required>
            @error('nombre') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="tipo_documento">Tipo de Documento</label>
            <select id="tipo_documento" name="tipo_documento" required>
                <option value="">Seleccione</option>
                @foreach(['DUI','NIT','Pasaporte'] as $tipo)
                    <option value="{{ $tipo }}" {{ old('tipo_documento', $customer->tipo_documento ?? '') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                @endforeach
            </select>
            @error('tipo_documento') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="numero_documento">Número de Documento</label>
            <input id="numero_documento" type="text" name="numero_documento" value="{{ old('numero_documento', $customer->numero_documento ?? '') }}" required>
            @error('numero_documento') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="nrc">NRC</label>
            <input id="nrc" type="text" name="nrc" value="{{ old('nrc', $customer->nrc ?? '') }}">
            @error('nrc') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="razon_social">Razón Social</label>
            <input id="razon_social" type="text" name="razon_social" value="{{ old('razon_social', $customer->razon_social ?? '') }}">
            @error('razon_social') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="actividad_economica">Actividad Económica</label>
            <input id="actividad_economica" type="text" name="actividad_economica" value="{{ old('actividad_economica', $customer->actividad_economica ?? '') }}">
            @error('actividad_economica') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="direccion_fiscal">Dirección Fiscal</label>
            <input type="text" id="direccion_fiscal" name="direccion_fiscal" value="{{ old('direccion_fiscal', $customer->direccion_fiscal ?? '') }}"required></input>
            @error('direccion_fiscal') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="email">Correo</label>
            <input id="email" type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" required>
            @error('email') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="telefono">Teléfono</label>
            <input id="telefono" type="text" name="telefono" value="{{ old('telefono', $customer->telefono ?? '') }}">
            @error('telefono') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="tipo_cliente">Tipo de Cliente</label>
            <select id="tipo_cliente" name="tipo_cliente" required>
                <option value="">Seleccione</option>
                @foreach(['Natural','Juridico'] as $tipo)
                    <option value="{{ $tipo }}" {{ old('tipo_cliente', $customer->tipo_cliente ?? '') == $tipo ? 'selected' : '' }}>{{ $tipo }}</option>
                @endforeach
            </select>
            @error('tipo_cliente') <div class="text-danger">{{ $message }}</div> @enderror

            <label for="comentarios">Comentarios</label>
            <input type="text" name="comentarios" value="{{ old('comentarios', $customer->comentarios ?? '') }}"></input>
            @error('comentarios') <div class="text-danger">{{ $message }}</div> @enderror

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

