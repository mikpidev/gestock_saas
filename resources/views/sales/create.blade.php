@extends('layouts.admin')

@section('content')

<style>
    .gestok-form-card {
        background: #fff;
        color: #000;
        width: 100%;
        max-width: 450px;
        border-radius: 10px;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        overflow: hidden;
        margin: 2rem auto;
    }

    .gestok-form-header {
        background: #000;
        color: #fff;
        padding: 1.5rem;
        text-align: center;
    }

    .gestok-form-header h1 {
        font-size: 1.6rem;
        font-weight: bold;
        margin: 0;
    }

    .gestok-form-header p {
        font-size: 0.9rem;
        margin-top: 0.5rem;
    }

    .gestok-form-body {
        padding: 2rem;
    }

    .gestok-form-body label {
        font-size: 0.9rem;
        display: block;
        margin-bottom: 0.3rem;
        font-weight: 500;
    }

    .gestok-form-body input[type="email"],
    .gestok-form-body input[type="password"],
    .gestok-form-body input[type="text"],
    .gestok-form-body input[type="date"],
    .gestok-form-body input[type="number"],


    .gestok-form-body select {
        width: 100%;
        padding: 0.6rem;
        margin-bottom: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 0.95rem;
        box-sizing: border-box;
    }

    .gestok-form-body select {
        background: #fff;
        cursor: pointer;
    }

    .gestok-form-body .btn {
        background: #000;
        color: #fff;
        border: none;
        padding: 0.8rem 1.5rem;
        border-radius: 5px;
        cursor: pointer;
        font-size: 0.95rem;
        font-weight: bold;
        width: 100%;
        margin-bottom: 1rem;
        text-decoration: none;
        display: inline-block;
        text-align: center;
    }

    .gestok-form-body .btn:hover {
        background: #333;
    }

    .gestok-form-body .btn-secondary {
        background: #666;
        color: #fff;
    }

    .gestok-form-body .btn-secondary:hover {
        background: #555;
    }

    .gestok-form-body .text-danger {
        color: #dc3545;
        font-size: 0.8rem;
        margin-top: -0.8rem;
        margin-bottom: 0.8rem;
    }

    .gestok-form-body .form-text {
        font-size: 0.8rem;
        color: #666;
        margin-top: -0.8rem;
        margin-bottom: 0.8rem;
    }

    .gestok-form-actions {
        display: flex;
        gap: 0.5rem;
        flex-direction: column;
    }

    @media (min-width: 400px) {
        .gestok-form-actions {
            flex-direction: row;
        }

        .gestok-form-actions .btn {
            width: auto;
            flex: 1;
            margin-bottom: 0;
        }
    }
</style>


<div class="gestok-form-card">
    <div class="gestok-form-header">
        <h1>Nueva Venta</h1>
        <p>{{ $store->store_name }}</p>
    </div>
    <div class="gestok-form-body">
        <form action="{{ route('stores.sales.store', $store->id) }}" method="POST">
            @csrf

            <!-- Selección de tipo de documento -->
            <label for="tipo_documento_id">Tipo de Venta</label>
            <select id="tipo_documento_id" name="tipo_documento_id" class="form-control">
                @foreach($tipoDocumentos as $tipoDocumento)
                <option value="{{ $tipoDocumento->id }}"
                    {{ old('tipo_documento_id') == $tipoDocumento->id ? 'selected' : '' }}>
                    {{ $tipoDocumento->nombre }}
                </option>
                @endforeach
            </select>


            <!-- Selección de cliente -->
            <label for="customers_id">Cliente</label>
            <select id="customers_id" name="customers_id" class="form-control">
                <option value="">-- Selecciona un cliente --</option>
                @foreach($customers as $customer)
                <option value="{{ $customer->id }}"
                    {{ old('customers_id') == $customer->id ? 'selected' : '' }}>
                    {{ $customer->nombre }}
                </option>
                @endforeach
            </select>

            @error('customers_id')
            <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Fecha de venta -->
            <label for="sale_date">Fecha de venta</label>
            <input type="date" id="sale_date" name="sale_date" value="{{ old('sale_date', now()->format('Y-m-d')) }}" required>
            @error('sale_date')
            <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Lista de productos -->
            <h4>Productos</h4>
            <div id="products-wrapper">
                @foreach(old('products', [['id' => '', 'quantity' => 1, 'price' => 0]]) as $i => $product)
                <div class="product-row">
                    <select name="products[{{ $i }}][id]" class="product-select" required>
                        <option value="">-- Selecciona producto --</option>
                        @foreach($products as $p)
                        <option value="{{ $p->id }}"
                            {{ $product['id'] == $p->id ? 'selected' : '' }}
                            data-price="{{ $p->price }}">
                            {{ $p->name }}
                        </option>
                        @endforeach
                    </select>
                    <label for="quatity">Cantidad</label>
                    <input type="number" name="products[{{ $i }}][quantity]" value="{{ $product['quantity'] }}" min="1" placeholder="Cantidad" required>
                    <label for="price">Precio</label>
                    <input type="number" step="0.01" name="products[{{ $i }}][price]" value="{{ $product['price'] ?? 0 }}" placeholder="Precio" class="product-price" required>
                </div>
                @endforeach
            </div>

            <!-- Totales y estado de pago -->
            <label for="payment_status">Estado de pago</label>
            <select id="payment_status" name="payment_status" required>
                <option value="unpaid" {{ old('payment_status') == 'unpaid' ? 'selected' : '' }}>Pendiente</option>
                <option value="paid" {{ old('payment_status') == 'paid' ? 'selected' : '' }}>Pagado</option>
                <option value="partial" {{ old('payment_status') == 'partial' ? 'selected' : '' }}>Parcial</option>
            </select>
            @error('payment_status')
            <div class="text-danger">{{ $message }}</div>
            @enderror

            <div class="gestok-form-actions">
                <button type="submit" class="btn">Crear Venta</button>
                <a href="{{ route('stores.sales.index', $store->id) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

@if ($errors->any())
<div class="alert alert-danger mt-3">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const wrapper = document.getElementById('products-wrapper');

        wrapper.addEventListener('change', function(e) {
            if (e.target.classList.contains('product-select')) {
                const select = e.target;
                const priceInput = select.closest('.product-row').querySelector('.product-price');
                const selectedOption = select.options[select.selectedIndex];
                priceInput.value = selectedOption.dataset.price ?? 0;
            }
        });
    });
</script>
@endsection