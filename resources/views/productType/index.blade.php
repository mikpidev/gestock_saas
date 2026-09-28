@extends('layouts.admin')

@section('content')

<div class="store-index">

<div class="d-flex justify-content-end align-items-center mb-3">
    <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#createProductModal" title="Crear producto">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2" />
        </svg>
        <span>Nuevo producto</span>
    </button>
</div>

<div class="modal fade" id="createProductModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="formResponse"></div>
                <form action="{{ route('stores.product_types.store', $store->id) }}" method="POST">
                    @csrf
                    @include('productType._form')
                </form>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs" id="productTabs">
    @foreach($categories as $categoryName => $items)
    <li class="nav-item">
        <a class="nav-link {{ $loop->first ? 'active' : '' }}"
            data-bs-toggle="tab"
            href="#cat{{ Str::slug($categoryName) }}">
            {{ $categoryName }}
        </a>
    </li>
    @endforeach
</ul>

<div class="tab-content mt-3">
    @foreach($categories as $categoryName => $items)
    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
        id="cat{{ Str::slug($categoryName) }}">

        <div class="table mt-4">
            <table class="table table-hover">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Descripción</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($items as $productType)
                    <tr>
                        <td>
                            <a href="{{ route('stores.product_types.show', [$store->id, $productType->id]) }}">
                                {{ $productType->name }}
                            </a>
                        </td>
                        <td>${{ number_format($productType->price,2) }}</td>
                        <td>{{ $productType->stock }}</td>
                        <td>{{ $productType->description }}</td>
                        <td class="text-center">
                            <div class="store-row-actions">
                                <button class="btn-edit editProductTypeBtn" type="button"
                                    data-update-url="{{ route('stores.product_types.update', [$store->id, $productType->id]) }}"
                                    data-name="{{ $productType->name }}"
                                    data-price="{{ $productType->price }}"
                                    data-stock="{{ $productType->stock }}"
                                    data-category="{{ $productType->category }}"
                                    data-description="{{ $productType->description }}">
                                    Editar
                                </button>

                                <div class="dropdown">
                                    <button class="config-btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Configuración" id="config-btn-{{ $productType->id }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="currentColor" viewBox="0 0 16 16">
                                            <path d="M7.068.727c.243-.97 1.62-.97 1.864 0l.071.286a.96.96 0 0 0 1.622.434l.205-.211c.695-.719 1.888-.03 1.613.931l-.08.284a.96.96 0 0 0 1.187 1.187l.283-.081c.96-.275 1.65.918.931 1.613l-.211.205a.96.96 0 0 0 .434 1.622l.286.071c.97.243.97 1.62 0 1.864l-.286.071a.96.96 0 0 0-.434 1.622l.211.205c.719.695.03 1.888-.931 1.613l-.284-.08a.96.96 0 0 0-1.187 1.187l.081.283c-.275.96-.918 1.65-1.613.931l-.205-.211a.96.96 0 0 0-1.622.434l-.071.286c-.243.97-1.62.97-1.864 0l-.071-.286a.96.96 0 0 0-1.622-.434l-.205.211c-.695.719-1.888.03-1.613-.931l.08-.284a.96.96 0 0 0-1.186-1.187l-.284.081c-.96.275-1.65-.918-.931-1.613l.211-.205a.96.96 0 0 0-.434-1.622l-.286-.071c-.97-.243-.97-1.62 0-1.864l.286-.071a.96.96 0 0 0 .434-1.622l-.211-.205c-.719-.695-.03-1.888.931-1.613l.284.08a.96.96 0 0 0 1.187-1.186l-.081-.284c-.275-.96.918-1.65 1.613-.931l.205.211a.96.96 0 0 0 1.622-.434zM12.973 8.5H8.25l-2.834 3.779A4.998 4.998 0 0 0 12.973 8.5m0-1a4.998 4.998 0 0 0-7.557-3.779l2.834 3.78zM5.048 3.967l-.087.065zm-.431.355A4.98 4.98 0 0 0 3.002 8c0 1.455.622 2.765 1.615 3.678L7.375 8zm.344 7.646.087.065z" />
                                        </svg>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li>
                                            <form action="{{ route('stores.product_types.destroy', [$store->id, $productType->id]) }}"
                                                method="POST"
                                                class="d-inline"
                                                onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
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
                        <td colspan="5" class="text-center">
                            No hay productos disponibles en esta categoría.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @endforeach
</div>

<div class="modal fade" id="editProductTypeModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editProductTypeForm" method="POST">
                    @csrf
                    @method('PUT')
                    @include('productType._form')
                </form>
            </div>
        </div>
    </div>
</div>

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
        const editModalEl = document.getElementById('editProductTypeModal');
        const editModal = new bootstrap.Modal(editModalEl);
        const form = document.getElementById('editProductTypeForm');

        document.querySelectorAll('.editProductTypeBtn').forEach(btn => {
            btn.addEventListener('click', () => {
                form.action = btn.dataset.updateUrl;
                form.querySelector('#edit_name').value = btn.dataset.name || '';
                form.querySelector('#edit_price').value = btn.dataset.price || '';
                form.querySelector('#edit_stock').value = btn.dataset.stock || '';
                form.querySelector('#edit_category').value = btn.dataset.category || '';
                form.querySelector('#edit_description').value = btn.dataset.description || '';
                editModal.show();
            });
        });
    });
</script>
</div>
@endsection
