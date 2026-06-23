@extends('layouts.admin')

@section('content')

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2></h2>
    <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#gestokModal" title="Crear Compañía" style="margin-right: 75px;">
        <i class="bi bi-plus-circle"></i>
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
        </svg>
    </button>
</div>


<!-- Modal para crear compañía -->
<div class="modal fade" id="gestokModal" tabindex="-1" aria-labelledby="gestokModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #fff; color: #000;">
                <h5 class="modal-title" id="gestokModalLabel">Crear Compañía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar" style="color: #000;"></button>
            </div>
            <div class="modal-body">
                <div id="formResponse"></div>
                <form id="companyForm" action="{{ route('companies.store') }}" method="POST">
                    @csrf
                    @include('company._form')
                </form>
            </div>
        </div>
    </div>
</div>

<div class="table-responsive mt-4">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dueño</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Comentarios</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody id="companiesTable">
            @forelse($companies as $company)
            <tr id="company-{{ $company->id }}">
                <td><a href="{{ route('companies.show', $company->id) }}">{{ $company->company_name }}</a></td>
                <td>{{ $company->owner }}</td>
                <td>{{ $company->email }}</td>
                <td>{{ ucfirst($company->status) }}</td>
                <td>{{ $company->comments }}</td>
                <td class="text-center">
                    <button class="btn btn-sm  btn-edit  editCompanyBtn gradient-text"
                        data-id="{{ $company->id }}"
                        data-company_name="{{ $company->company_name }}"
                        data-address="{{ $company->address }}"
                        data-phone="{{ $company->phone }}"
                        data-owner="{{ $company->owner }}"
                        data-email="{{ $company->email }}"
                        data-website="{{ $company->website }}"
                        data-plan="{{ $company->plan }}"
                        data-deployment_type="{{ $company->deployment_type }}"
                        data-status="{{ $company->status }}"
                        data-comments="{{ $company->comments }}">
                        <i class="bi bi-pencil"></i>
                        <h5 class="modal-title">Editar</h5>

                    </button>

                    <form action="{{ route('companies.destroy', $company->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta compañía?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-rm gradient-text">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="text-center">No hay compañías registradas.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>


<!-- Modal único de edición al final de la página -->
<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCompanyForm" method="POST">
                    @csrf
                    @method('PUT')
                    @include('company.edit') <!-- Reutiliza inputs -->
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editModal = new bootstrap.Modal(document.getElementById('editCompanyModal'));
        document.querySelectorAll('.editCompanyBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const form = document.getElementById('editCompanyForm');
                form.action = `/companies/${id}`; // URL dinámica
                form.querySelector('#edit_company_name').value = btn.dataset.company_name;
                form.querySelector('#edit_address').value = btn.dataset.address;
                form.querySelector('#edit_phone').value = btn.dataset.phone;
                form.querySelector('#edit_owner').value = btn.dataset.owner;
                form.querySelector('#edit_email').value = btn.dataset.email;
                form.querySelector('#edit_website').value = btn.dataset.website;
                form.querySelector('#edit_plan').value = btn.dataset.plan;
                form.querySelector('#edit_deployment_type').value = btn.dataset.deployment_type;
                form.querySelector('#edit_status').value = btn.dataset.status;
                form.querySelector('#edit_comments').value = btn.dataset.comments;


                editModal.show();
            });
        });
    });
</script>

@endsection