<div class="row mb-3 justify-content-center align-items-center">
    <label for="name" class="col-sm-3 col-form-label">
        Nombre
    </label>


    <div class="col-sm-6">
        <input type="text" name="name" id="edit_user_name" value="{{ old('name', $user->name ?? '') }}" class="form-control" required>
        @error('name')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="email" class="col-sm-3 col-form-label">
        Correo Electrónico
    </label>
    <div class="col-sm-6">
        <input type="email" name="email" id="edit_user_email" value="{{ old('email', $user->email ?? '') }}" class="form-control" required>
        @error('email')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="password" class="col-sm-3 col-form-label">
        {{ isset($user) ? 'Nueva Contraseña (opcional)' : 'Contraseña' }}
    </label>

    <div class="col-sm-6">
        <input id="password" type="password" name="password" {{ !isset($user) ? 'required' : '' }} class="form-control">
        @if(isset($user))
        <div class="form-text">Deja en blanco si no quieres cambiar la contraseña</div>
        @endif
        @error('password')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">

    <label for="password_confirmation" class="col-sm-3 col-form-label">Confirmar Contraseña</label>

    <div class="col-sm-6">
        <input id="password_confirmation" type="password" id="edit_user_password_confirmation" name="password_confirmation" {{ !isset($user) ? 'required' : '' }} class="form-control">
        @error('password_confirmation')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>


<div class="row mb-3 justify-content-center align-items-center">

    <label for="role" class="col-sm-3 col-form-label">Rol</label>
    <div class="col-sm-6">
        <select id="role" name="role" required class="form-control">
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
            data-redirect="{{ route('stores.users.index', $store->id) }}">

            <span class="gradient-text">
                Guardar
            </span>

        </button>

    </div>
</div>