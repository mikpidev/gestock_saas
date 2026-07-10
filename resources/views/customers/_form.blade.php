<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="tipoDocumento">Tipo de Documento</label>
    </div>


    <div class="col-md-6">
        <select id="edit_tipoDocumento" name="tipoDocumento" class="select2 form-control">
            <option value="">Seleccione</option>
            @foreach($tiposDocumento as $tipo)
            <option value="{{ $tipo->codigo }}"
                {{ old('tipoDocumento', $customer->tipoDocumento ?? '') == $tipo->codigo ? 'selected' : '' }}>
                {{ $tipo->codigo }} - {{ $tipo->nombre }}
            </option>
            @endforeach
        </select>
    </div>
    @error('tipoDocumento')
    <div class="text-danger">{{ $message }}</div> @enderror

</div>


{{-- Número de Documento --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="numDocumento">
            Número de Documento
        </label>
    </div>

    <div class="col-md-6">
        <input id="edit_numDocumento" type="text" name="numDocumento" maxlength="14"
            value="{{ old('numDocumento', $customer->numDocumento ?? '') }}" class="form-control">
        @error('numDocumento') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- NRC --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="nrc">
            NRC
        </label>
    </div>

    <div class="col-md-6">
        <input id="edit_nrc" type="text" name="nrc" maxlength="10"
            value="{{ old('nrc', $customer->nrc ?? '') }}" class="form-control">
        @error('nrc') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="nombre">Nombre</label>
    </div>
    <div class="col-md-6">
        <input id="edit_nombre" type="text" name="nombre" value="{{ old('nombre', $customer->nombre ?? '') }}" class="form-control">
        @error('nombre') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="nombreComercial">Nombre Comercial</label>
    </div>
    <div class="col-md-6">
        <input id="edit_nombreComercial" type="text" name="nombreComercial" value="{{ old('nombreComercial', $customer->nombreComercial ?? '') }}" class="form-control">
        @error('nombreComercial') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>



{{-- Actividad Económica --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="codActividad">Actividad Económica</label>
    </div>
    <div class="col-md-6">
        <select id="edit_codActividad" name="codActividad" class="select2 form-control">
            <option value="">Seleccione</option>
            @foreach($actividades as $act)
            <option value="{{ $act->codigo }}"
                {{ old('codActividad', $customer->codActividad ?? '') == $act->codigo ? 'selected' : '' }}>
                {{ $act->codigo }} - {{ $act->nombre }}
            </option>
            @endforeach
        </select>
        @error('codActividad') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="descActividad">Descripción de Actividad</label>
    </div>
    <div class="col-md-6">
        <input id="edit_descActividad" type="text" name="descActividad"
            value="{{ old('descActividad', $customer->descActividad ?? '') }}" class="form-control">
        @error('descActividad') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Departamento --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="direccion_departamento">Departamento</label>
    </div>
    <div class="col-md-6">
        <select id="edit_direccion_departamento" name="direccion_departamento" class="select2 form-control">
            <option value="">Seleccione</option>
            @foreach($departamentos as $dep)
            <option value="{{ $dep->codigo }}"
                {{ old('direccion_departamento', $customer->direccion_departamento ?? '') == $dep->codigo ? 'selected' : '' }}>
                {{ $dep->codigo }} - {{ $dep->nombre }}
            </option>
            @endforeach
        </select>
        @error('direccion_departamento') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

{{-- Municipio --}}
<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="direccion_municipio">Municipio</label>
    </div>
    <div class="col-md-6">
        <select id="edit_direccion_municipio" name="direccion_municipio" class="select2 form-control">
            <option value="">Seleccione</option>
            @foreach($municipios as $mun)
            <option value="{{ $mun->codigo }}"
                {{ old('direccion_municipio', $customer->direccion_municipio ?? '') == $mun->codigo ? 'selected' : '' }}>
                {{ $mun->codigo }} - {{ $mun->nombre }}
            </option>
            @endforeach
        </select>
        @error('direccion_municipio') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    {{-- Dirección Complementaria --}}
    <div class="col-md-6">
        <label for="direccion_complemento">Dirección Complementaria</label>
    </div>
    <div class="col-md-6">
        <input id="edit_direccion_complemento" type="text" name="direccion_complemento"
            value="{{ old('direccion_complemento', $customer->direccion_complemento ?? '') }}" class="form-control">
        @error('direccion_complemento') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Teléfono --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="telefono">Teléfono</label>
    </div>
    <div class="col-md-6">
        <input id="edit_telefono" type="text" name="telefono" maxlength="15"
            value="{{ old('telefono', $customer->telefono ?? '') }}" class="form-control">
        @error('telefono') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>


{{-- Correo --}}

<div class="row mb-3 justify-content-center align-items-center">
    <div class="col-md-6">
        <label for="correo">Correo Electrónico</label>
    </div>
    <div class="col-md-6">
        <input id="edit_correo" type="email" name="correo"
            value="{{ old('correo', $customer->correo ?? '') }}" class="form-control">
        @error('correo') <div class="text-danger">{{ $message }}</div> @enderror
    </div>
</div>



<!-- Botón -->
<div class="row justify-content-center">
    <div class="col-sm-8 d-flex justify-content-end gap-2">
        <button type="button"
            class="btn btn-modal"
            data-bs-dismiss="modal">

            <span class="gradient-text">
                Cerrar
            </span>

        </button>

        <button type="submit"
            class="btn btn-modal"
            data-redirect="{{ route('stores.customers.index', $store->id) }}">

            <span class="gradient-text">
                Guardar
            </span>

        </button>

    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>

<!-- Select2 CSS -->
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

<!-- Select2 JS -->
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>

    document.addEventListener('DOMContentLoaded', () => {
        const form = document.getElementById('customersForm'); // ID del form de customers
        if (!form) return;

        form.addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(form);
            const responseDiv = document.getElementById('formResponse');
            const actionUrl = form.action; // URL dinámica del form

            fetch(actionUrl, {
                    method: 'POST', // Laravel acepta POST + @method('PUT') si es edición
                    headers: {
                        'X-CSRF-TOKEN': "{{ csrf_token() }}",
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(res => res.json())
                .then(result => {
                    if (result.success) {
                        responseDiv.innerHTML = `<div class="alert alert-success">${result.message}</div>`;

                        // Cerrar modal si existe
                        const modalEl = form.closest('.modal');
                        if (modalEl) {
                            const modal = bootstrap.Modal.getInstance(modalEl);
                            if (modal) modal.hide();
                        }

                        // Limpiar formulario
                        form.reset();

                        // Opcional: actualizar tabla de stores
                        if (typeof refreshStoresList === 'function') {
                            refreshStoresList(result.store);
                        }

                    } else {
                        responseDiv.innerHTML = `<div class="alert alert-danger">${result.message || 'Ocurrió un error'}</div>`;
                    }
                })
                .catch(err => {
                    console.error(err);
                    responseDiv.innerHTML = `<div class="alert alert-danger">Error al procesar la solicitud</div>`;
                });
        });
    });
</script>