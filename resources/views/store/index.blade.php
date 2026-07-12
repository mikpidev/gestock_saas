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

                    <form action="{{ route('stores.destroy', $store->id) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar esta tienda?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-rm gradient-text">Eliminar</button>
                    </form>
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