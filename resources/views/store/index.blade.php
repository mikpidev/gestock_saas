@extends('layouts.admin')

@section('content')


<div class="d-flex justify-content-between align-items-center mb-3">
    <h2></h2>
    <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#gestokModal" title="Crear Tienda" style="margin-right: 75px;">
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
                <h5 class="modal-title" id="gestokModalLabel">Crear Tienda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar" style="color: #000;"></button>
            </div>
            <div class="modal-body">
                <div id="formResponse"></div>
                <form action="{{ route('store.store', ['company' => session('selected_company_id')]) }}" method="POST">
                    @csrf
                    @include('store._form')
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
                <th>Encargado</th>
                <th>Estado</th>
                <th>Comentarios </th>
                <th></th>
            </tr>
        </thead>
        <tbody id="storesTable">
            @forelse($stores as $store)
            <tr id="store-{{ $store->id }}">
                <td><a href="{{ route('stores.dashboard', $store->id) }}">{{ $store->store_name }}</a></td>
                <td>{{ $store->manager }}</td>
                <td>{{ ucfirst($store->status) }}</td>
                <td>{{ $store->comments }}</td>
                <td class="text-center">
                    <div class="d-flex justify-content-center gap-1">

                        <button class="btn btn-sm btn-edit gradient-text editStoreBtn"
                            data-bs-toggle="modal"
                            data-bs-target="#editStoreModal"
                            data-id="{{ $store->id }}"
                            data-store_name="{{ $store->store_name }}"
                            data-establecimiento="{{ $store->establecimiento }}"
                            data-punto_venta="{{ $store->punto_venta }}"
                            data-address="{{ $store->address }}"
                            data-phone="{{ $store->phone }}"
                            data-manager="{{ $store->manager }}"
                            data-email="{{ $store->email }}"
                            data-comments="{{ $store->comments }}"
                            data-deployment_type="{{ $store->deployment_type }}"
                            data-status="{{ $store->status }}"
                            data-comments="{{ $store->comments }}">
                            <i class="bi bi-pencil"></i>
                            <h5 class="modal-title">Editar</h5>

                        </button>

                        {{-- Dropdown con engranaje --}}
                        <div class="dropdown">
                            <button id="config-btn" class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-gear-wide-connected" viewBox="0 0 16 16">
                                    <path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c-.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z" />
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li>    <a class="dropdown-item" href="{{ route('store_tax_info.edit', $store->id) }}">
                                    Editar Información Tributaria de la Tienda</a>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="{{ route('mh_access.edit', [$store, $store->mh_access]) }}">Editar Credenciales Ministerio de Hacienda</a>
                                    <hr class="dropdown-divider">
                                </li>
                                <li><a class="dropdown-item" href="{{ route('correlativos.edit', $store->id) }}">Editar Correlativos</a>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('stores.destroy', $store->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta tienda?');">
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
                <td colspan="5" class="text-center">No hay tiendas disponibles.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal único de edición al final de la página -->
<div class="modal fade" id="editStoreModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Tienda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editStoreForm" method="POST">
                    @csrf
                    @method('PUT')
                    @include('store._form') <!-- Reutiliza inputs -->
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editModal = new bootstrap.Modal(document.getElementById('editStoreModal'));
        document.querySelectorAll('.editStoreBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const form = document.getElementById('editStoreForm');
                form.action = `/stores/${id}`; // URL dinámica
                form.querySelector('#edit_store_name').value = btn.dataset.store_name;
                form.querySelector('#edit_establecimiento').value = btn.dataset.establecimiento;
                form.querySelector('#edit_punto_venta').value = btn.dataset.punto_venta;
                form.querySelector('#edit_address').value = btn.dataset.address;
                form.querySelector('#edit_phone').value = btn.dataset.phone;
                form.querySelector('#edit_manager').value = btn.dataset.manager;
                form.querySelector('#edit_email').value = btn.dataset.email;

                form.querySelector('#edit_comments').value = btn.dataset.comments;


                editModal.show();
            });
        });
    });
</script>
@endsection