<!--Formulario para crear o editar una tienda-->

<input type="hidden" name="company_id" value="{{ old('company_id', $company->id ?? '') }}">

<label for="store_name">Nombre de la Tienda</label>
<input type="text" name="store_name" id="store_name" class="form-control" value="{{ old('store_name', $store->store_name ?? '') }}" required>

<label for="address">Dirección</label>
<input type="text" name="address" id="address" class="form-control" value="{{ old('address', $store->address ?? '') }}" required>

<label for="phone">Teléfono</label>
<input type="text" name="phone" id="phone" class="form-control" value="{{ old('phone', $store->phone ?? '') }}" required>

<label for="manager">Gerente</label>
<input type="text" name="manager" id="manager" class="form-control" value="{{ old('manager', $store->manager ?? '') }}" required>

<label for="email">Correo Electrónico</label>
<input type="email" name="email" id="email" class="form-control" value="{{ old('email', $store->email ?? '') }}" required>

<label for="status">Estado</label>
<select name="status" id="status" class="form-control" required>
    <option value="activa" {{ (old('status', $store->status ?? '') == 'activa') ? 'selected' : '' }}>Activa</option>
    <option value="suspendida" {{ (old('status', $store->status ?? '') == 'suspendida') ? 'selected' : '' }}>Suspendida</option>
    <option value="inactiva" {{ (old('status', $store->status ?? '') == 'inactiva') ? 'selected' : '' }}>Inactiva</option>
</select>
<label for="comments">Comentarios</label>
<textarea name="comments" id="comments" class="form-control" rows="4">{{ old('comments', $store->comments ?? '') }}</textarea>


<button type="submit" class="btn btn-primary mt-3" data-redirect="{{ route('companies.index') }}">Guardar</button>
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
