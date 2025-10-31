<div class="mb-3">
    <label for="edit_company_name" class="form-label">Nombre de la Compañía</label>
    <input type="text" name="company_name" id="edit_company_name" class="form-control" required>
</div>

<div class="mb-3">
    <label for="edit_address" class="form-label">Dirección</label>
    <input type="text" name="address" id="edit_address" class="form-control" required>
</div>

<div class="mb-3">
    <label for="edit_phone" class="form-label">Teléfono</label>
    <input type="text" name="phone" id="edit_phone" class="form-control" required>
</div>

<div class="mb-3">
    <label for="edit_owner" class="form-label">Dueño</label>
    <input type="text" name="owner" id="edit_owner" class="form-control" required>
</div>

<div class="mb-3">
    <label for="edit_email" class="form-label">Correo Electrónico</label>
    <input type="email" name="email" id="edit_email" class="form-control" required>
</div>

<div class="mb-3">
    <label for="edit_website" class="form-label">Sitio Web</label>
    <input type="url" name="website" id="edit_website" class="form-control">
</div>

<div class="mb-3">
    <label for="edit_plan" class="form-label">Plan</label>
    <select name="plan" id="edit_plan" class="form-control" required>
        <option value="free">Free</option>
        <option value="basic">Basic</option>
        <option value="premium">Premium</option>
    </select>
</div>

<div class="mb-3">
    <label for="edit_deployment_type" class="form-label">Tipo de Despliegue</label>
    <select name="deployment_type" id="edit_deployment_type" class="form-control" required>
        <option value="saas">SaaS</option>
        <option value="on_premise">On-Premise</option>
    </select>
</div>

<div class="mb-3">
    <label for="edit_status" class="form-label">Estado</label>
    <select name="status" id="edit_status" class="form-control" required>
        <option value="activa">Activa</option>
        <option value="suspendida">Suspendida</option>
        <option value="inactiva">Inactiva</option>
    </select>
</div>

<div class="mb-3">
    <label for="edit_comments" class="form-label">Comentarios</label>
    <textarea name="comments" id="edit_comments" class="form-control" rows="4"></textarea>
</div>

<button type="submit" class="btn btn-primary mt-3" data-redirect="{{ route('companies.index') }}">Guardar Cambios</button>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('editCompanyForm');
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const responseDiv = document.getElementById('formResponse');
            const actionUrl = form.action;

            fetch(actionUrl, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': @json(csrf_token()),
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

                        const modalEl = form.closest('.modal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        form.reset();

                        if (typeof refreshCompaniesList === 'function') {
                            refreshCompaniesList(result.company);
                        }

                    } else {
                        responseDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Ocurrió un error'}</div>`;
                    }
                });
        });
    });
</script>