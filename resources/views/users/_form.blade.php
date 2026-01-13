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
        <h1>{{ isset($user) ? 'Editar Usuario' : 'Nuevo Usuario' }}</h1>
        <p>{{ $store->store_name }}</p>
    </div>
    <div class="gestok-form-body">
        <form action="{{ isset($user) ? route('stores.users.update',[$store->id, $user->id]) : route('stores.users.store', $store->id) }}" method="POST">
            @csrf
            @if(isset($user))
                @method('PUT')
            @endif

            <label for="name">Nombre</label>
            <input id="name" type="text" name="name" value="{{ old('name', $user->name ?? '') }}" required autofocus>
            @error('name')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <label for="email">Correo</label>
            <input id="email" type="email" name="email" value="{{ old('email', $user->email ?? '') }}" required>
            @error('email')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <label for="password">
                {{ isset($user) ? 'Nueva Contraseña (opcional)' : 'Contraseña' }}
            </label>
            <input id="password" type="password" name="password" {{ !isset($user) ? 'required' : '' }}>
            @if(isset($user))
                <div class="form-text">Deja en blanco si no quieres cambiar la contraseña</div>
            @endif
            @error('password')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <label for="password_confirmation">Confirmar Contraseña</label>
            <input id="password_confirmation" type="password" name="password_confirmation" {{ !isset($user) ? 'required' : '' }}>
            @error('password_confirmation')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <label for="role">Rol</label>
            <select id="role" name="role" required>
                <option value="">Seleccione un rol</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" 
                            {{ old('role', isset($user) && $user->roles->first() ? $user->roles->first()->name : '') == $role->name ? 'selected' : '' }}>
                        {{ ucfirst($role->name) }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <div class="gestok-form-actions">
                <button type="submit" class="btn">
                    {{ isset($user) ? 'Actualizar Usuario' : 'Crear Usuario' }}
                </button>
                <a href="{{ route('stores.users.index', $store->id) }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>