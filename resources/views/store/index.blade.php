@extends('layouts.admin')

@section('content')

<div class="store-index">

<div class="d-flex justify-content-end align-items-center mb-3">
    <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#createStoreModal" title="Crear tienda">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
        </svg>
        <span>Nueva tienda</span>
    </button>
</div>

<div class="modal fade" id="createStoreModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Tienda</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('store.store', ['company' => $companyId]) }}" method="POST">
                    @csrf
                    @include('store._form', ['store' => null])
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
                <th>Encargado</th>
                <th>Estado</th>
                <th>Comentarios</th>
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
                    <div class="store-row-actions">
                        <button class="btn-edit editStoreBtn" type="button"
                            data-update-url="{{ route('stores.update', $store->id) }}"
                            data-store_name="{{ $store->store_name }}"
                            data-establecimiento="{{ $store->establecimiento }}"
                            data-punto_venta="{{ $store->punto_venta }}"
                            data-address="{{ $store->address }}"
                            data-phone="{{ $store->phone }}"
                            data-manager="{{ $store->manager }}"
                            data-email="{{ $store->email }}"
                            data-status="{{ $store->status }}"
                            data-environment="{{ $store->environment }}"
                            data-comments="{{ $store->comments }}">
                            Editar
                        </button>

                        <div class="dropdown">
                            <button class="config-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Configuración" id="config-btn-{{ $store->id }}">
                                <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                    <path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c-.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z" />
                                </svg>
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#taxInfoModal-{{ $store->id }}">
                                        {{ $store->taxInfo ? 'Editar' : 'Crear' }} Información Tributaria
                                    </button>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#mhAccessModal-{{ $store->id }}">
                                        {{ $store->mh_access ? 'Editar' : 'Crear' }} Credenciales Ministerio de Hacienda
                                    </button>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <button type="button" class="dropdown-item" data-bs-toggle="modal" data-bs-target="#correlativosModal-{{ $store->id }}">
                                        Editar Correlativos
                                    </button>
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

@foreach ($stores as $store)
    @php
        $correlativos = $store->correlativoStores->keyBy('tipo_documento_id');
    @endphp

    <div class="modal fade" id="taxInfoModal-{{ $store->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $store->taxInfo ? 'Editar' : 'Crear' }} información tributaria — {{ $store->store_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($store->taxInfo)
                        <form action="{{ route('stores_tax_info.update', $store->taxInfo) }}" method="POST">
                            @csrf
                            @method('PUT')
                            @include('stores_tax_info._modal_fields', ['store' => $store])
                            <div class="text-end">
                                <button type="button" class="btn btn-modal" data-bs-dismiss="modal"><span class="gradient-text">Cerrar</span></button>
                                <button type="submit" class="btn btn-modal"><span class="gradient-text">Guardar</span></button>
                            </div>
                        </form>
                    @else
                        <form action="{{ route('stores_tax_info.store', $store) }}" method="POST">
                            @csrf
                            @include('stores_tax_info._modal_fields', ['store' => $store])
                            <div class="text-end">
                                <button type="button" class="btn btn-modal" data-bs-dismiss="modal"><span class="gradient-text">Cerrar</span></button>
                                <button type="submit" class="btn btn-modal"><span class="gradient-text">Guardar</span></button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mhAccessModal-{{ $store->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ $store->mh_access ? 'Editar' : 'Crear' }} credenciales de Hacienda — {{ $store->store_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ $store->mh_access ? route('mh_access.update', $store) : route('mh_access.store', $store) }}" method="POST">
                        @csrf
                        @if ($store->mh_access)
                            @method('PUT')
                        @endif
                        <input type="hidden" name="store_id" value="{{ $store->id }}">
                        <div class="mb-3">
                            <label class="form-label">Hacienda API Password</label>
                            <input type="text" name="api_key" class="form-control" value="{{ old('api_key', $store->mh_access->api_key ?? '') }}" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password Privada</label>
                            <input type="text" name="password_pri" class="form-control" value="{{ old('password_pri', $store->mh_access->password_pri ?? '') }}">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Port Firma Digital</label>
                            <input type="text" name="port_firma_digital" class="form-control" value="{{ old('port_firma_digital', $store->mh_access->port_firma_digital ?? '') }}" required>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-modal" data-bs-dismiss="modal"><span class="gradient-text">Cerrar</span></button>
                            <button type="submit" class="btn btn-modal"><span class="gradient-text">Guardar</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="correlativosModal-{{ $store->id }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Correlativos — {{ $store->store_name }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('correlativos.update', $store->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <table class="table table-sm table-bordered align-middle">
                            <thead>
                                <tr>
                                    <th>Tipo de documento</th>
                                    <th style="width: 180px;">Correlativo</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($tiposDte as $tipo)
                                    @php $correlativo = $correlativos[$tipo->id] ?? null; @endphp
                                    <tr>
                                        <td>
                                            {{ $tipo->nombre }}
                                            <input type="hidden" name="correlativos[{{ $loop->index }}][tipo_documento_id]" value="{{ $tipo->id }}">
                                            <input type="hidden" name="correlativos[{{ $loop->index }}][id]" value="{{ $correlativo->id ?? '' }}">
                                        </td>
                                        <td>
                                            <input type="number" name="correlativos[{{ $loop->index }}][correlativo]" class="form-control form-control-sm" value="{{ $correlativo->correlativo ?? 0 }}" min="0">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                        <div class="text-end">
                            <button type="button" class="btn btn-modal" data-bs-dismiss="modal"><span class="gradient-text">Cerrar</span></button>
                            <button type="submit" class="btn btn-modal"><span class="gradient-text">Guardar</span></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endforeach

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
                    @include('store._form', ['store' => null])
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const editModalEl = document.getElementById('editStoreModal');
        const editModal = new bootstrap.Modal(editModalEl);
        const form = document.getElementById('editStoreForm');

        document.querySelectorAll('.editStoreBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.updateUrl;
                form.querySelector('#edit_store_name').value = btn.dataset.store_name || '';
                form.querySelector('#edit_establecimiento').value = btn.dataset.establecimiento || '';
                form.querySelector('#edit_punto_venta').value = btn.dataset.punto_venta || '';
                form.querySelector('#edit_address').value = btn.dataset.address || '';
                form.querySelector('#edit_phone').value = btn.dataset.phone || '';
                form.querySelector('#edit_manager').value = btn.dataset.manager || '';
                form.querySelector('#edit_email').value = btn.dataset.email || '';
                form.querySelector('#edit_comments').value = btn.dataset.comments || '';
                const statusSelect = form.querySelector('#status');
                if (statusSelect) statusSelect.value = btn.dataset.status || '';
                const environmentSelect = form.querySelector('#environment');
                if (environmentSelect) environmentSelect.value = btn.dataset.environment || '';
                editModal.show();
            });
        });

        document.querySelectorAll('.modal').forEach((modal) => {
            modal.addEventListener('shown.bs.modal', () => {
                if (!window.jQuery) return;
                window.jQuery(modal).find('select.js-select2').each(function () {
                    const $el = window.jQuery(this);
                    if ($el.hasClass('select2-hidden-accessible')) {
                        $el.select2('destroy');
                    }
                    $el.select2({
                        dropdownParent: window.jQuery(modal),
                        placeholder: 'Seleccione',
                        allowClear: true,
                        width: '100%'
                    });
                });
            });
        });
    });
</script>
</div>
@endsection
