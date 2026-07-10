@extends('layouts.admin')

@section('content')



<div class="d-flex justify-content-between align-items-center mb-3">
    <h2></h2>
    <button type="button" class="btn btn-add" data-bs-toggle="modal" data-bs-target="#gestokModal" title="Crear Cliente" style="margin-right: 75px;">
        <i class="bi bi-plus-circle"></i>
        <svg xmlns="http://www.w3.org/2000/svg" width="30" height="" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
        </svg>
    </button>
</div>


<!-- Modal para crear Cliente -->
<div class="modal fade" id="gestokModal" tabindex="-1" aria-labelledby="gestokModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" style="background-color: #fff; color: #000;">
                <h5 class="modal-title" id="gestokModalLabel">Crear Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar" style="color: #000;"></button>
            </div>
            <div class="modal-body">
                <div id="formResponse"></div>
                <form action="{{ route('stores.customers.store', $store->id) }}" method="POST">
                    @csrf
                    @include('customers._form')

                </form>
            </div>
        </div>
    </div>
</div>

<div class="container-fluid mt-4">
    <table class="table table-hover">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Número de Documento</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th></th>
            </tr>
        </thead>
        <tbody id="customerTable">
            @forelse($customers as $customer)
            <tr>
                <td><a href="{{ route('stores.customers.show', [$store->id, $customer->id]) }}">{{ $customer->nombre }}</a></td>
                <td>{{ $customer->numDocumento }}</td>
                <td>{{ $customer->correo }}</td>
                <td>{{ $customer->telefono }}</td>
                <td class="text-center">

                    <div class="d-flex justify-content-center gap-1">

                        <button class="btn btn-sm btn-edit editCustomersBtn"
                            data-bs-toggle="modal"
                            data-bs-target="#editCustomersModal"
                            data-id="{{ $customer->id }}"
                            data-tipodocumento="{{ $customer->tipoDocumento }}"
                            data-numdocumento="{{ $customer->numDocumento }}"
                            data-nrc="{{ $customer->nrc }}"
                            data-nombre="{{ $customer->nombre }}"
                            data-nombrecomercial="{{$customer->nombreComercial }}"
                            data-codActividad="{{ $customer->codActividad }}"
                            data-descActividad="{{ $customer->descActividad }}"
                            data-direccion_departamento="{{ $customer->direccion_departamento }}"
                            data-direccion_municipio="{{ $customer->direccion_municipio }}"
                            data-direccion_complemento="{{$customer->direccion_complemento }}"
                            data-telefono="{{ $customer->telefono }}"
                            data-correo="{{ $customer->correo }}"
                            <i class="bi bi-pencil"></i>
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-pencil" viewBox="0 0 16 16">
                                <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325" />
                            </svg>
                        </button>

                        {{-- Dropdown con engranaje --}}
                        <div class="dropdown">
                            <button id="config-btn" class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" fill="currentColor" class="bi bi-gear-wide-connected" viewBox="0 0 16 16">
                                    <path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c-.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z" />
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="{{ route('stores.customers.edit', ['store' => $store->id, 'customer' => $customer->id]) }}">Ver detalles</a></li>
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <form action="{{ route('stores.customers.destroy', [$store->id, $customer->id]) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar este cliente?');">
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
                <td colspan="5" class="text-center">No hay clientes disponibles en esta tienda.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

<!-- Modal único de edición al final de la página -->
<div class="modal fade" id="editCustomersModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editCustomersForm" method="POST">
                    @csrf
                    @method('PUT')
                    @include('customers._form') <!-- Reutiliza inputs -->
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
//validar en que modal se encuentra y aplicar select2 al modal correspondiente

$(document).ready(function () {

    $('#gestokModal').on('shown.bs.modal', function () {

        $(this).find('.select2').select2({
            width: '100%',
            dropdownParent: $('#gestokModal')
        });

    });


    $('#editCustomersModal').on('shown.bs.modal', function () {

        $(this).find('.select2').select2({
            width: '100%',
            dropdownParent: $('#editCustomersModal')
        });

    });

});
    document.addEventListener('DOMContentLoaded', () => {
        const editModal = new bootstrap.Modal(document.getElementById('editCustomersModal'));
        document.querySelectorAll('.editCustomersBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                const id = btn.dataset.id;
                const form = document.getElementById('editCustomersForm');
                form.action = `customers/${id}`; // URL dinámica
                form.querySelector('#edit_tipodocumento').value = btn.dataset.tipoDocumento ?? '';
                form.querySelector('#edit_numdocumento').value = btn.dataset.numDocumento ?? '';
                form.querySelector('#edit_nrc').value = btn.dataset.nrc ?? '';
                form.querySelector('#edit_nombre').value = btn.dataset.nombre ?? '';
                form.querySelector('#edit_nombrecomercial').value = btn.dataset.nombreComercial ?? '';
                form.querySelector('#edit_codActividad').val(btn.dataset.codactividad ?? '')
                    .trigger('change');
                form.querySelector('#edit_descActividad').value = btn.dataset.descActividad ?? '';
                form.querySelector('#edit_direccion_departamento').val(btn.dataset.direccion_departamento ?? '')
                    .trigger('change');
                form.querySelector('#edit_direccion_municipio').val(btn.dataset.direccion_municipio ?? '')
                    .trigger('change');
                form.querySelector('#edit_telefono').value = btn.dataset.telefono ?? '';
                form.querySelector('#edit_correo').value = btn.dataset.correo ?? '';

                editModal.show();
            });
        });
    });
</script>



@endsection