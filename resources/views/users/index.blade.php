@extends('layouts.admin')

@section('content')



<div class="d-flex justify-content-between align-items-center mb-3">
    <h2></h2>
    <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#gestokModal" title="Crear Usuario" style="margin-right: 75px;">
        <i class="bi bi-plus-circle"></i>
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
        </svg>
    </button>
</div>


<!-- Modal para crear Usuario -->
<div class="modal fade" id="gestokModal" tabindex="-1" aria-labelledby="gestokModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #fff; color: #000;">
                <h5 class="modal-title" id="gestokModalLabel">Crear Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar" style="color: #000;"></button>
            </div>
            <div class="modal-body">
                <div id="formResponse"></div>
                <form action="{{ route('stores.users.store', $store->id) }}" method="POST">
                    @csrf
                    @include('users._form')
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
                <th>Email</th>
                <th>Rol</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="usersTable">
            @forelse($users as $user)
            <tr id="user-{{ $user->id }}">
                <td><a href="{{ route('stores.users.show', [$store->id, $user->id]) }}">{{ $user->name }}</a></td>
                <td>{{ $user->email }}</td>
                <td>
                    @if($user->roles->isNotEmpty())
                    <span class="badge bg-primary">{{ ucfirst($user->roles->first()->name) }}</span>
                    @else
                    <span class="badge bg-secondary">Sin rol</span>
                    @endif
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-edit editUserBtn"
                        data-bs-toggle="modal"
                        data-bs-target="#editUserModal"
                        data-id="{{ $user->id }}"
                        data-name="{{ $user->name }}"
                        data-email="{{ $user->email }}"
                        data-new-password=""
                        data-confirm-password=""
                        data-role="{{ $user->roles->first()->name ?? '' }}">
                        <i class="bi bi-pencil"></i>
                        <h5 class="modal-title">Editar</h5>
                    </button>

                    <form action="{{ route('stores.users.destroy', [$store->id, $user->id]) }}" method="POST" class="d-inline" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-sm btn-rm gradient-text">Eliminar</button>
                    </form>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="text-center">No hay usuarios disponibles en esta tienda.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal único de edición al final de la página -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editUserForm" method="POST">
                    @csrf
                    @method('PUT')
                    @include('users._form') <!-- Reutiliza inputs -->
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Botón para regresar a tiendas --}}
<div class="mt-3">
    <a href="{{ route('stores.index') }}" class="btn btn-sm btn-back">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-left" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M15 8a.5.5 0 0 0-.5-.5H2.707l3.147-3.146a.5.5 0 1 0-.708-.708l-4 4a.5.5 0 0 0 0 .708l4 4a.5.5 0 0 0 .708-.708L2.707 8.5H14.5A.5.5 0 0 0 15 8" />
        </svg>
        Volver a Tiendas
    </a>
</div>


<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
        document.querySelectorAll('.editUserBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const form = document.getElementById('editUserForm');
                form.action = `/stores/{{ $store->id }}/users/${id}`; // URL dinámica
                form.querySelector('#edit_name').value = btn.dataset.name;
                form.querySelector('#edit_email').value = btn.dataset.email;
                form.querySelector('#edit_new-password').value = btn.dataset.newPassword;
                form.querySelector('#edit_confirm-password').value = btn.dataset.confirmPassword;
                form.querySelector('#edit_role').value = btn.dataset.role;

                editModal.show();
            });
        });
    });
</script>
@endsection