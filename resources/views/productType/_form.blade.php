<input type="hidden" name="company_id" id="company_id" class="form-control" value="{{ old('company_id', $store->company_id ?? '') }}" required>
<input type="hidden" name="store_id" id="store_id" class="form-control" value="{{ old('store_id', $store->id ?? '') }}" required>


<div class="row mb-3 justify-content-center align-items-center">
    <label for="name" class="col-sm-3 col-form-label">Nombre</label>
    <div class="col-sm-6">
        <input type="text" id="edit_name" name="name" value="{{ old('name', $productType->name ?? '') }}" class="form-control" required>
        @error('name')
        <div class="text-danger">{{ $message }}</div>
        @enderror

    </div>
</div>


<div class="row mb-3 justify-content-center align-items-center">
    <label for="price" class="col-sm-3 col-form-label">
        Precio
    </label>
    
    <div class="col-sm-6">
        <input type="numeric" id="edit_price" name="price" step="0.00" value="{{ old('price', $productType->price ?? '') }}" class="form-control" required>
        @error('price')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="stock" class="col-sm-3 col-form-label">Cantidad</label>
    <div class="col-sm-6">
        <input type="number" id="edit_stock" name="stock" min="1" step="1" value="{{ old('stock', $productType->stock ?? '') }}" class="form-control" required>
        @error('stock')
        <div class="text-danger">{{ $message }}</div>
        @enderror

    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="category" class="col-sm-3 col-form-label">
        Categoría
    </label>
    
    <div class="col-sm-6">
        <input type="text" id="edit_category" name="category" value="{{ old('category', $productType->category ?? '') }}" class="form-control" required>
        @error('category')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="description" class="col-sm-3 col-form-label">
        Descripción
    </label>
    <div class="col-sm-6">
        <input type="text" id="edit_description" name="description" value="{{ old('description', $productType->description ?? '') }}" class="form-control" required>
        @error('description')
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
            data-redirect="{{ route('stores.product_types.index', $store->id) }}">

            <span class="gradient-text">
                Guardar
            </span>

        </button>

    </div>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('ProductTypeForm'); // ID del form de Productos
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