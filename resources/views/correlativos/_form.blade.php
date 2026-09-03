<div class="card border-0 shadow-sm">

    <div class="card-header bg-white py-2">
        <h6 class="mb-0">Configuración de tienda</h6>
    </div>

    <div class="card-body p-3">

        <div class="mb-3">
            <label for="edit_store_name" class="form-label mb-1">
                Nombre de la tienda
            </label>

            <input
                type="text"
                name="store_name"
                id="edit_store_name"
                class="form-control form-control-sm"
                value="{{ old('store_name', $store->store_name ?? '') }}"
                required
            >
        </div>

        <h6 class="border-bottom pb-2 mb-2">
            Correlativos DTE
        </h6>

        <table class="table table-sm table-bordered align-middle mb-0">

            <thead class="table-light">
                <tr>
                    <th>Tipo de documento</th>
                    <th style="width: 200px;">Correlativo</th>
                </tr>
            </thead>

            <tbody>

                @foreach($tiposDte as $tipo)

                    @php
                        $correlativo = $correlativos[$tipo->id] ?? null;
                    @endphp

                    <tr>

                        <td class="py-1">

                            {{ $tipo->nombre }}

                            <input
                                type="hidden"
                                name="correlativos[{{ $loop->index }}][tipo_documento_id]"
                                value="{{ $tipo->id }}"
                            >

                            <input
                                type="hidden"
                                name="correlativos[{{ $loop->index }}][id]"
                                value="{{ $correlativo->id ?? '' }}"
                            >

                        </td>

                        <td class="py-1">

                            <input
                                type="number"
                                name="correlativos[{{ $loop->index }}][correlativo]"
                                class="form-control form-control-sm"
                                value="{{ $correlativo->correlativo ?? 0 }}"
                                min="0"
                            >

                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    </div>

    <div class="card-footer bg-white py-2 text-end">

        <a href="{{ route('stores.show', $store->id) }}"
           class="btn btn-sm btn-light border">
            Cancelar
        </a>

        <button type="submit" class="btn btn-sm btn-primary">
            Actualizar tienda
        </button>

    </div>

</div>