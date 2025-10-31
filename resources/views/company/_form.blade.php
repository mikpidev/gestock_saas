<!-- resources/views/company/_form.blade.php -->

<div class="mb-3">
    <label for="company_name" class="form-label">Nombre de la Compañía</label>
    <input type="text" name="company_name" id="company_name" class="form-control" required>
</div>

<div class="mb-3">
    <label for="address" class="form-label">Dirección</label>
    <input type="text" name="address" id="address" class="form-control" required>
</div>

<div class="mb-3">
    <label for="phone" class="form-label">Teléfono</label>
    <input type="text" name="phone" id="phone" class="form-control" required>
</div>

<div class="mb-3">
    <label for="owner" class="form-label">Dueño</label>
    <input type="text" name="owner" id="owner" class="form-control" required>
</div>

<div class="mb-3">
    <label for="email" class="form-label">Correo Electrónico</label>
    <input type="email" name="email" id="email" class="form-control" required>
</div>

<div class="mb-3">
    <label for="website" class="form-label">Sitio Web</label>
    <input type="url" name="website" id="website" class="form-control">
</div>

<div class="mb-3">
    <label for="plan" class="form-label">Plan</label>
    <select name="plan" id="plan" class="form-control" required>
        <option value="free">Free</option>
        <option value="basic">Basic</option>
        <option value="premium">Premium</option>
    </select>
</div>

<div class="mb-3">
    <label for="deployment_type" class="form-label">Tipo de Despliegue</label>
    <select name="deployment_type" id="deployment_type" class="form-control" required>
        <option value="saas">SaaS</option>
        <option value="on_premise">On-Premise</option>
    </select>
</div>

<div class="mb-3">
    <label for="status" class="form-label">Estado</label>
    <select name="status" id="status" class="form-control" required>
        <option value="activa">Activa</option>
        <option value="suspendida">Suspendida</option>
        <option value="inactiva">Inactiva</option>
    </select>
</div>

<div class="mb-3">
    <label for="comments" class="form-label">Comentarios</label>
    <textarea name="comments" id="comments" class="form-control" rows="4"></textarea>
</div>

<button type="submit" class="btn btn-primary mt-3" data-redirect="{{ route('companies.index') }}">Guardar</button>
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