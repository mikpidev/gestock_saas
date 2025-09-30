

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

            <label for="customers_id">Cliente</label>
            <select id="customers_id" name="customers_id">
                <option value="">-- Seleccionar cliente --</option>
                @foreach($customers as $customer)
                    <option value="{{ $customer->id }}" {{ old('customers_id', $sale->customers_id ?? '') == $customer->id ? 'selected' : '' }}>
                        {{ $customer->name }}
                    </option>
                @endforeach
            </select>
            @error('customers_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <label for="sale_date">Fecha</label>
            <input type="date" id="sale_date" name="sale_date" value="{{ old('sale_date', $sale->sale_date ?? date('Y-m-d')) }}" required>
            @error('sale_date')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <label for="payment_status">Estado de pago</label>
            <select id="payment_status" name="payment_status" required>
                <option value="paid" {{ old('payment_status', $sale->payment_status ?? '') == 'paid' ? 'selected' : '' }}>Pagado</option>
                <option value="unpaid" {{ old('payment_status', $sale->payment_status ?? '') == 'unpaid' ? 'selected' : '' }}>Pendiente</option>
                <option value="partial" {{ old('payment_status', $sale->payment_status ?? '') == 'partial' ? 'selected' : '' }}>Parcial</option>
            </select>
            @error('payment_status')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <hr>

            <h4>Productos</h4>
            <div id="products-container">
                @if(old('products', isset($sale) ? $sale->details->toArray() : []))
                    @foreach(old('products', isset($sale) ? $sale->details->toArray() : []) as $i => $product)
                        <div class="product-row">
                            <select name="products[{{ $i }}][id]" required>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" {{ $product['product_type_id'] ?? $product['id'] == $p->id ? 'selected' : '' }}>
                                        {{ $p->name }}
                                    </option>
                                @endforeach
                            </select>
                            <input type="number" name="products[{{ $i }}][quantity]" value="{{ $product['quantity'] ?? 1 }}" min="1" required>
                            <input type="number" name="products[{{ $i }}][price]" value="{{ $product['unit_price'] ?? 0 }}" step="0.01" required>
                        </div>
                    @endforeach
                @endif
            </div>
            <button type="button" onclick="addProductRow()">Agregar producto</button>

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
    `;
    container.appendChild(row);
}
</script>


