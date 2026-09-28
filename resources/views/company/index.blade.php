@extends('layouts.admin')

@section('content')

<div class="store-index">

<div class="d-flex justify-content-end align-items-center mb-3">
    <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#createCompanyModal" title="Crear compañía">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
        </svg>
        <span>Nueva compañía</span>
    </button>
</div>

<div class="modal fade" id="createCompanyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Compañía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
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

<div class="table mt-4">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Dueño</th>
                <th>Correo</th>
                <th>Estado</th>
                <th>Comentarios</th>
                <th></th>
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
                    <div class="store-row-actions">
                        <button class="btn-edit editCompanyBtn" type="button"
                            data-update-url="{{ route('companies.update', $company->id) }}"
                            data-company_name="{{ $company->company_name }}"
                            data-address="{{ $company->address }}"
                            data-phone="{{ $company->phone }}"
                            data-owner="{{ $company->owner }}"
                            data-email="{{ $company->email }}"
                            data-website="{{ $company->website }}"
                            data-deployment_type="{{ $company->deployment_type }}"
                            data-status="{{ $company->status }}"
                            data-comments="{{ $company->comments }}">
                            Editar
                        </button>

                        <div class="dropdown">
                            <button class="config-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Configuración" id="config-btn-{{ $company->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c-.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z" />
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <form action="{{ route('companies.destroy', $company->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta compañía?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="dropdown-item text-danger">Eliminar</button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    </div>
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

<div class="modal fade" id="editCompanyModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Compañía</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCompanyForm" method="POST">
                    @csrf
                    @method('PUT')
                    @include('company.edit')
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editModalEl = document.getElementById('editCompanyModal');
        const editModal = new bootstrap.Modal(editModalEl);
        const form = document.getElementById('editCompanyForm');

        document.querySelectorAll('.editCompanyBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.updateUrl;
                form.querySelector('#edit_company_name').value = btn.dataset.company_name || '';
                form.querySelector('#edit_address').value = btn.dataset.address || '';
                form.querySelector('#edit_phone').value = btn.dataset.phone || '';
                form.querySelector('#edit_owner').value = btn.dataset.owner || '';
                form.querySelector('#edit_email').value = btn.dataset.email || '';
                form.querySelector('#edit_website').value = btn.dataset.website || '';
                form.querySelector('#edit_deployment_type').value = btn.dataset.deployment_type || '';
                form.querySelector('#edit_status').value = btn.dataset.status || '';
                form.querySelector('#edit_comments').value = btn.dataset.comments || '';
                editModal.show();
            });
        });
    });
</script>
</div>
@endsection
