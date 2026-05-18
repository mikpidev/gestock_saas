<input type="hidden" name="company_id" id="company_id" class="form-control" value="{{ old('company_id', $store->company_id ?? '') }}" required>
<input type="hidden" name="store_id" id="store_id" class="form-control" value="{{ old('store_id', $store->id ?? '') }}" required>


<div class="row mb-3 justify-content-center align-items-center">
    <label for="name" class="col-sm-3 col-form-label">Nombre</label>
    <div class="col-sm-6">
        <input type="text" id="name" name="name" value="{{ old('name', $productType->name ?? '') }}" class="form-control" required>
        @error('name')
        <div class="text-danger">{{ $message }}</div>
        @enderror

    </div>
</div>


<div class="row mb-3 justify-content-center align-items-center">
    <label for="price" class="col-sm-3 col-form-label">
        Precio
    </label>
    
    <div class="col-sm-6">
        <input type="numeric" id="edit_price" name="price" step="0.00" value="{{ old('price', $productType->price ?? '') }}" class="form-control" required>
        @error('price')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>

</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="stock" class="col-sm-3 col-form-label">Cantidad</label>
    <div class="col-sm-6">
        <input type="number" id="stock" name="stock" min="1" step="1" value="{{ old('stock', $productType->stock ?? '') }}" class="form-control" required>
        @error('stock')
        <div class="text-danger">{{ $message }}</div>
        @enderror

    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="category" class="col-sm-3 col-form-label">
        Categoría
    </label>
    
    <div class="col-sm-6">
        <input type="text" id="category" name="category" value="{{ old('category', $productType->category ?? '') }}" class="form-control" required>
        @error('category')
        <div class="text-danger">{{ $message }}</div>
        @enderror
    </div>
</div>

<div class="row mb-3 justify-content-center align-items-center">
    <label for="description" class="col-sm-3 col-form-label">
        Descripción
    </label>
    <div class="col-sm-6">
        <input type="text" id="description" name="description" value="{{ old('description', $productType->description ?? '') }}" class="form-control" required>
        @error('description')
        <div class="text-danger">{{ $message }}</div>
        @enderror
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
            data-redirect="{{ route('stores.product_types.index', $store->id) }}">

            <span class="gradient-text">
                Guardar
            </span>

        </button>

    </div>
</div>