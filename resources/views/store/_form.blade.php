<!--Formulario para crear o editar una tienda-->

<!-- Nombre -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="store_name" class="col-sm-3 col-form-label">
        Nombre de la Tienda
    </label>

    <div class="col-sm-6">
        <input type="text"
            name="store_name"
            id="edit_store_name"
            class="form-control"
            required>
    </div>
</div>

<!-- Nombre -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="store_name" class="col-sm-3 col-form-label">
        Establecimiento
    </label>

    <div class="col-sm-6">
        <input type="text"
            name="establecimiento"
            id="edit_establecimiento"
            class="form-control"
            required>
    </div>
</div>

<!-- Nombre -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="store_name" class="col-sm-3 col-form-label">
        Punto de Venta
    </label>

    <div class="col-sm-6">
        <input type="text"
            name="punto_venta"
            id="edit_punto_venta"
            class="form-control"
            required>
    </div>
</div>


<div class="row mb-3 justify-content-center align-items-center">
    <label for="address" class="col-sm-3 col-form-label">Dirección</label>
    <div class="col-sm-6">
        <input type="text" name="address" id="edit_address" class="form-control" value="{{ old('address', $store->address ?? '') }}" required>
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="phone" class="col-sm-3 col-form-label">Teléfono</label>
    <div class="col-sm-6">
        <input type="text" name="phone" id="edit_phone" class="form-control" value="{{ old('phone', $store->phone ?? '') }}" required>
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="manager" class="col-sm-3 col-form-label">Gerente</label>
    <div class="col-sm-6">
        <input type="text" name="manager" id="edit_manager" class="form-control" value="{{ old('manager', $store->manager ?? '') }}" required>
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="email" class="col-sm-3 col-form-label">Correo Electrónico</label>
    <div class="col-sm-6">
        <input type="email" name="email" id="edit_email" class="form-control" value="{{ old('email', $store->email ?? '') }}" required>
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="status" class="col-sm-3 col-form-label">Estado</label>
    <div class="col-sm-6">
        <select name="status" id="status" class="form-control" required>
            <option value="activa" {{ (old('status', $store->status ?? '') == 'activa') ? 'selected' : '' }}>Activa</option>
            <option value="suspendida" {{ (old('status', $store->status ?? '') == 'suspendida') ? 'selected' : '' }}>Suspendida</option>
            <option value="inactiva" {{ (old('status', $store->status ?? '') == 'inactiva') ? 'selected' : '' }}>Inactiva</option>
        </select>
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="environment" class="col-sm-3 col-form-label">Entorno</label>
    <div class="col-sm-6">
        <select name="environment" id="environment" class="form-control" required>
            <option value="Production" {{ (old('environment', $store->environment ?? '') == 'Production') ? 'selected' : '' }}>Production</option>
            <option value="Development" {{ (old('environment', $store->environment ?? '') == 'Development') ? 'selected' : '' }}>Development</option>
        </select>
    </div>
</div>

<!-- Comentarios -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="comments" class="col-sm-3 col-form-label">
        Comentarios
    </label>

    <div class="col-sm-6">
        <textarea name="comments"
            id="edit_comments"
            rows="4"
            class="form-control"></textarea>
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
            data-redirect="{{ route('companies.index') }}">

            <span class="gradient-text">
                Guardar
            </span>

        </button>

    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('storeForm'); // ID del form de stores
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const responseDiv = document.getElementById('formResponse');
            const actionUrl = form.action; // URL dinámica del form

            fetch(actionUrl, {
                    method: 'POST', // Laravel acepta POST + @method('PUT') si es edición
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        responseDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;

                        // Cerrar modal si existe
                        const modalEl = form.closest('.modal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        // Limpiar formulario
                        form.reset();

                        // Opcional: actualizar tabla de stores
                        if (typeof refreshStoresList === 'function') {
                            refreshStoresList(result.store);
                        }

                    } else {
                        responseDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Ocurrió un error'}</div>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    responseDiv.innerHTML = `<div class="alert alert-danger">Error al procesar la solicitud</div>`;
                });
        });
    });
</script>