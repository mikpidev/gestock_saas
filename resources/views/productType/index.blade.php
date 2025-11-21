@extends('layouts.admin')

@section('content')

    <div class="d-flex justify-content-between align-items-center mb-3">
    <h2>{{ $store->store_name }}</h2> 
    <a href="{{ route('stores.product_types.create', $store->id) }}" class="btn btn-add"> 
        <i class="bi bi-plus-circle"></i> 
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2"/>
        </svg>
    </a>
</div>

{{-- Tabs de categorías --}}
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

{{-- Contenido de cada categoría --}}
<div class="tab-content mt-3">

    @foreach($categories as $categoryName => $items)
    <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
         id="cat{{ Str::slug($categoryName) }}">

        <div class="table-responsive mt-3">
            <table class="table table-hover align-middle">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Precio</th>
                        <th>Cantidad</th>
                        <th>Descripción</th>
                        <th class="text-center">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                @foreach($items as $productType)
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
                            <div class="d-flex justify-content-center gap-1">

                                {{-- Editar --}}
                                <a href="{{ route('stores.product_types.edit', [$store->id, $productType->id]) }}"
                                   class="btn btn-sm btn-edit" title="Editar">
                                    <i class="bi bi-pencil"></i>
                                </a>

                                {{-- Engranaje --}}
                                <div class="dropdown">
                                    <button class="btn btn-outline-secondary btn-sm dropdown-toggle"
                                            type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-gear-wide-connected"></i>
                                    </button>

                                    <ul class="dropdown-menu">
                                        <li>
                                            <a class="dropdown-item"
                                               href="{{ route('stores.product_types.show', [$store->id, $productType->id]) }}">
                                                Ver detalles
                                            </a>
                                        </li>

                                        <li><hr class="dropdown-divider"></li>

                                        <li>
                                            <form action="{{ route('stores.product_types.destroy', [$store->id, $productType->id]) }}"
                                                  method="POST"
                                                  onsubmit="return confirm('¿Seguro que deseas eliminar este producto?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    Eliminar
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>

                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>

            </table>
        </div>

    </div>
    @endforeach

</div>

{{-- Volver --}}
<div class="mt-3">
    <a href="{{ route('stores.index') }}" class="btn btn-secondary">
        <i class="bi bi-arrow-left"></i>
        Volver a Tiendas
    </a>
</div>

@endsection
