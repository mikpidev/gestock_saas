<!-- resources/views/company/_form.blade.php -->

<!-- Nombre -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="company_name" class="col-sm-3 col-form-label">
        Nombre de la Compañía
    </label>

    <div class="col-sm-6">
        <input type="text"
            name="company_name"
            id="company_name"
            class="form-control"
            required>
    </div>
</div>

<!-- Dirección -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="address" class="col-sm-3 col-form-label">
        Dirección
    </label>

    <div class="col-sm-6">
        <input type="text"
            name="address"
            id="address"
            class="form-control"
            required>
    </div>
</div>

<!-- Teléfono -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="phone" class="col-sm-3 col-form-label">
        Teléfono
    </label>

    <div class="col-sm-6">
        <input type="text"
            name="phone"
            id="phone"
            class="form-control"
            required>
    </div>
</div>

<!-- Dueño -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="owner" class="col-sm-3 col-form-label">
        Dueño
    </label>

    <div class="col-sm-6">
        <input type="text"
            name="owner"
            id="owner"
            class="form-control"
            required>
    </div>
</div>

<!-- Email -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="email" class="col-sm-3 col-form-label">
        Correo Electrónico
    </label>

    <div class="col-sm-6">
        <input type="email"
            name="email"
            id="email"
            class="form-control"
            required>
    </div>
</div>

<!-- Sitio Web -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="website" class="col-sm-3 col-form-label">
        Sitio Web
    </label>

    <div class="col-sm-6">
        <input type="url"
            name="website"
            id="website"
            class="form-control">
    </div>
</div>

<!-- Plan -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="plan" class="col-sm-3 col-form-label">
        Plan
    </label>

    <div class="col-sm-6">
        <select name="plan"
            id="plan"
            class="form-select"
            required>

            <option value="free">Free</option>
            <option value="basic">Basic</option>
            <option value="premium">Premium</option>

        </select>
    </div>
</div>

<!-- Tipo despliegue -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="deployment_type" class="col-sm-3 col-form-label">
        Tipo de Despliegue
    </label>

    <div class="col-sm-6">
        <select name="deployment_type"
            id="deployment_type"
            class="form-select"
            required>

            <option value="saas">SaaS</option>
            <option value="on_premise">On-Premise</option>

        </select>
    </div>
</div>

<!-- Estado -->
<div class="row mb-3 justify-content-center align-items-center">
    <label for="status" class="col-sm-3 col-form-label">
        Estado
    </label>

    <div class="col-sm-6">
        <select name="status"
            id="status"
            class="form-select"
            required>

            <option value="activa">Activa</option>
            <option value="suspendida">Suspendida</option>
            <option value="inactiva">Inactiva</option>

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
            id="comments"
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
        const form = document.getElementById('companyForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const responseDiv = document.getElementById('formResponse');

            fetch("{{ route('companies.store') }}", {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => {
                    if (res.redirected) {
                        window.location.href = res.url; // Sigue la redirección normal
                    }
                }).then(result => {
                    if (result.success) {
                        responseDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;

                        // Cerrar el modal
                        const modalEl = form.closest('.modal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        // Limpiar formulario
                        form.reset();

                        // Opcional: actualizar tabla si tienes función
                        if (typeof refreshCompaniesList === 'function') {
                            refreshCompaniesList(result.company);
                        }

                    } else {
                        responseDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Ocurrió un error'}</div>`;
                    }
                }).catch(err => {
                    console.error(err);
                    if (err instanceof Object) {
                        responseDiv.innerHTML = `<pre>${JSON.stringify(err, null, 2)}</pre>`;
                    } else {
                        responseDiv.innerHTML = `<div class="alert alert-danger">Error al procesar la solicitud</div>`;
                    }
                });
        });
    });
</script>