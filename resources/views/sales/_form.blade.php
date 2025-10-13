<div class="gestok-form-card">
    <div class="gestok-form">
        <h1>{{ isset($sale) ? 'Editar Venta' : 'Nueva Venta' }}</h1>
        <p>{{ $store->store_name }}</p>
    </div>

    <div class="gestok-form-body">
        <form action="{{ isset($sale) ? route('stores.sales.update', [$store->id, $sale->id]) : route('sales.store', $store->id) }}" method="POST">
            @csrf
            @if(isset($sale))
                @method('PUT')
            @endif

            <h4>Productos</h4>
            <div id="products-container">
                @if(old('products', isset($sale) ? $sale->details->toArray() : []))
                    @foreach(old('products', isset($sale) ? $sale->details->toArray() : []) as $i => $product)
                        <div class="product-row">
                            <select name="products[{{ $i }}][id]" required>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ ($product['product_type_id'] ?? $product['id']) == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="products[{{ $i }}][quantity]" value="{{ $product['quantity'] ?? 1 }}" min="1" required>
                            <input type="number" name="products[{{ $i }}][price]" value="{{ $product['unit_price'] ?? $p->price ?? 0 }}" step="0.01" required>
                            <button type="button" class="remove-btn" onclick="this.parentElement.remove()">✖</button>
                        </div>
                    @endforeach
                @endif
            </div>

            <button type="button" class="btn-add" onclick="addProductRow()">Agregar producto</button>

            <div class="gestok-form-actions">
                <button type="submit" class="btn">
                    {{ isset($sale) ? 'Actualizar Venta' : 'Crear Venta' }}
                </button>
                <a href="{{ route('stores.sales.index', $store->id) }}" class="btn btn-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<script>
function addProductRow() {
    let container = document.getElementById('products-container');
    let index = container.children.length;
    let row = document.createElement('div');
    row.classList.add('product-row');
    row.innerHTML = `
        <select name="products[${index}][id]" required>
            @foreach($products as $p)
                <option value="{{ $p->id }}">{{ $p->name }}</option>
            @endforeach
        </select>
        <input type="number" name="products[${index}][quantity]" value="1" min="1" required>
        <input type="number" name="products[${index}][price]" value="0" step="0.01" required>
        <button type="button" class="remove-btn" onclick="this.parentElement.remove()">✖</button>
    `;
    container.appendChild(row);
}
</script>

<style>
.gestok-form-card {
    background: #fff;
    color: #000;
    width: 100%;
    max-width: 600px;
    margin: auto;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.1);
}
.product-row {
    display: flex;
    gap: 10px;
    align-items: center;
    margin-bottom: 10px;
}
.product-row select,
.product-row input {
    flex: 1;
    padding: 6px;
}
.remove-btn {
    background: #ff4d4f;
    color: #fff;
    border: none;
    border-radius: 5px;
    padding: 4px 8px;
    cursor: pointer;
}
.btn-add {
    margin-top: 10px;
    background: #007bff;
    color: #fff;
    border: none;
    padding: 8px 12px;
    border-radius: 5px;
    cursor: pointer;
}
.btn-add:hover {
    background: #0056b3;
}
</style>
