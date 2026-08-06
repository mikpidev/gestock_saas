<!--Formulario para crear o editar una tienda-->

<!-- Nombre -->

<div class="row mb-3 justify-content-center align-items-center">


    <label for="store_name" class="col-sm-3 col-form-label">
        Nombre de la tienda
    </label>

    <div class="col-sm-3">
        <input type="text"
            value="{{ old('store_name', $store->store_name ?? '') }}"
            name="store_name"
            id="edit_store_name"
            class="form-control"
            required>
    </div>
</div>


@foreach($tiposDte as $tipo)
    @php
        $correlativo = $correlativos[$tipo->id] ?? null;
    @endphp

    <div class="row mb-3 justify-content-center align-items-center">

        <label class="col-sm-3 col-form-label">
            Tipo de Documento
        </label>

        <div class="col-sm-3">
            <input type="text"
                class="form-control"
                value="{{ $tipo->nombre }}"
                readonly>
        </div>

        <label class="col-sm-2 col-form-label">
            Correlativo
        </label>

        <div class="col-sm-2">
            <input type="hidden"
                name="correlativos[{{ $loop->index }}][tipo_documento_id]"
                value="{{ $tipo->id }}">

            <input type="hidden"
                name="correlativos[{{ $loop->index }}][id]"
                value="{{ $correlativo->id ?? '' }}">

            <input type="number"
                name="correlativos[{{ $loop->index }}][correlativo]"
                class="form-control"
                value="{{ $correlativo->correlativo ?? 0 }}"
                min="0">
        </div>

    </div>
@endforeach

<button type="submit" class="btn">
    {{ isset($correlativo) ? 'Actualizar Acceso' : 'Crear Acceso' }}
</button>
<a href="{{ route('stores.show', $store->id) }}" class="btn btn-secondary">
    Cancelar
</a>