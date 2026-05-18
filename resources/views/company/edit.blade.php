<div class="row mb-3 justify-content-center align-items-center">
    <label for="edit_company_name" class="col-sm-3 col-form-label">Nombre de la Compañía</label>
    <div class="col-sm-6">
        <input type="text" name="company_name" id="edit_company_name" class="form-control" required>
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="edit_address" class="col-sm-3 col-form-label">Dirección</label>
    <div class="col-sm-6">
        <input type="text" name="address" id="edit_address" class="form-control" required>
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="edit_phone" class="col-sm-3 col-form-label">Teléfono</label>
    <div class="col-sm-6">
        <input type="text" name="phone" id="edit_phone" class="form-control" required>
    </div>

    <div class="row mb-3 justify-content-center align-items-center">
        <label for="edit_owner" class="col-sm-3 col-form-label">Dueño</label>
        <div class="col-sm-6">
            <input type="text" name="owner" id="edit_owner" class="form-control" required>
        </div>
    </div>

    <div class="row mb-3 justify-content-center align-items-center">
        <label for="edit_email" class="col-sm-3 col-form-label">Correo Electrónico</label>
        <div class="col-sm-6">
            <input type="email" name="email" id="edit_email" class="form-control" required>
        </div>
    </div>

    <div class="row mb-3 justify-content-center align-items-center">
        <label for="edit_website" class="col-sm-3 col-form-label">Sitio Web</label>
        <div class="col-sm-6">
            <input type="url" name="website" id="edit_website" class="form-control">
        </div>
    </div>

    <div class="row mb-3 justify-content-center align-items-center">
        <label for="edit_plan" class="col-sm-3 col-form-label">Plan</label>
        <div class="col-sm-6">
            <select name="plan" id="edit_plan" class="form-control" required>
                <option value="free">Free</option>
                <option value="basic">Basic</option>
                <option value="premium">Premium</option>
            </select>
        </div>
    </div>

    <div class="row mb-3 justify-content-center align-items-center">
        <label for="edit_deployment_type" class="col-sm-3 col-form-label">Tipo de Despliegue</label>
        <div class="col-sm-6">
            <select name="deployment_type" id="edit_deployment_type" class="form-control" required>
                <option value="saas">SaaS</option>
                <option value="on_premise">On-Premise</option>
            </select>
        </div>
    </div>

    <div class="row mb-3 justify-content-center align-items-center">
        <label for="edit_status" class="col-sm-3 col-form-label">Estado</label>
        <div class="col-sm-6">
            <select name="status" id="edit_status" class="form-control" required>
                <option value="activa">Activa</option>
                <option value="suspendida">Suspendida</option>
                <option value="inactiva">Inactiva</option>
            </select>
        </div>
    </div>

    <div class="row mb-3 justify-content-center align-items-center">
        <label for="edit_comments" class="col-sm-3 col-form-label">Comentarios</label>
        <div class="col-sm-6">
            <textarea name="comments" id="edit_comments" class="form-control" rows="4"></textarea>
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