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
.gestok-form-body input[type="password"],

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
        <h1>{{ isset($mhAccess) ? 'Editar Acceso a Hacienda' : 'Nuevo Acceso a Hacienda' }}</h1>
        <p>{{ $store->nombre ?? 'Tienda' }}</p>
    </div>

    <div class="gestok-form-body">
        <form action="{{ isset($mhAccess) 
            ?  route('mh_access.update', $store)
            : route('mh_access.store', $store->id) }}" method="POST">
            @csrf
            @if(isset($mhAccess))
                @method('PUT')
            @endif

            <input type="hidden" name="store_id" value="{{ $store->id }}">

            {{-- Hacienda API Password --}}
            <label for="api_key">Hacienda API Password</label>
            <input type="text" name="api_key" id="api_key" class="form-control" value="{{ old('api_key', $store->mh_access->api_key ?? '') }}" required>
            @error('api_key') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Password Privada --}}
            <label for="password_pri">Password Privada </label>
            <input type="text" name="password_pri" id="password_pri" class="form-control" value="{{ old('password_pri',$store->mh_access->password_pri ?? '') }}">
            @error('password_pri') <div class="text-danger">{{ $message }}</div> @enderror

            {{-- Razón Social --}}
            <label for="port_firma_digital">Port Firma Digital</label>
            <input type="text" name="port_firma_digital" id="port_firma_digital" class="form-control" value="{{ old('port_firma_digital', $store->mh_access->port_firma_digital ?? '') }}" required>
            @error('port_firma_digital') <div class="text-danger">{{ $message }}</div> @enderror


            <div class="gestok-form-actions">
                <button type="submit" class="btn">
                    {{ isset($mhAccess) ? 'Actualizar Acceso' : 'Crear Acceso' }}
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
