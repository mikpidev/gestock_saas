@extends('layouts.admin')

@section('content')
<style>
    :root {
        --color-primario: #ffb548;
        --color-secundario: #ff6c37;
        --color-acento: #8e5928;
    }

    /* ===== Layout general ===== */
    .main-container {
        display: flex;
        gap: 1rem;
        height: 90vh;
        flex-wrap: wrap;
        /* permite que los elementos bajen de línea en pantallas pequeñas */
    }

    .products-grid {
        flex: 1;
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        gap: 1rem;
        overflow-y: auto;
        padding: 1rem;
    }

    /* ===== Tarjetas de productos ===== */
    .product-card.card {
        cursor: pointer;
        user-select: none;
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        transition: all 0.2s ease;
        display: flex;
        flex-direction: column;
        align-items: center;
        background: #fff;
        border: 1px solid #eee;
        border-radius: 12px;
    }

    .product-card.card img {
        width: 100%;
        height: 120px;
        object-fit: contain;
        margin-bottom: 0.5rem;
    }

    .product-card.card .card-body {
        width: 100%;
        text-align: center;
    }

    .product-card.card h6 {
        font-size: 0.95rem;
        margin-bottom: 0.2rem;
        font-weight: 600;
    }

    .product-card.card p {
        font-size: 0.85rem;
        color: var(--color-secundario);
        margin: 0;
    }

    .product-card.card:active {
        transform: scale(0.97);
    }

    .product-card.card:hover {
        background: var(--color-primario);
        color: #fff;
    }

    /* ===== Sidebar del carrito ===== */
    .cart-sidebar {
        width: 340px;
        background: #fff;
        padding: 1rem;
        border-left: 2px solid var(--color-primario);
        box-shadow: -2px 0 10px rgba(0, 0, 0, 0.05);
        display: flex;
        flex-direction: column;
    }

    .cart-sidebar h4 {
        color: var(--color-acento);
        font-weight: 700;
        border-bottom: 1px solid #eee;
        padding-bottom: 0.5rem;
        margin-bottom: 1rem;
    }

    .cart-scroll {
        flex: 1;
        overflow-y: auto;
        margin-bottom: 1rem;
    }

    /* ===== Campos ===== */
    .cart-select,
    .cart-date {
        margin-bottom: 1rem;
        width: 100%;
        padding: 0.5rem;
        border-radius: 8px;
        border: 1px solid #ddd;
        font-size: 0.95rem;
    }

    /* ===== Tabla del carrito ===== */
    .cart-table th,
    .cart-table td {
        vertical-align: middle;
        font-size: 0.9rem;
    }

    #total-wrapper {
        font-weight: bold;
        text-align: right;
        font-size: 1.1rem;
        color: var(--color-acento);
        margin-top: 0.5rem;
    }

    /* ===== Botón principal ===== */
    .btn-create-sale {
        background: var(--color-secundario);
        color: #fff;
        border: none;
        padding: 0.9rem;
        border-radius: 8px;
        cursor: pointer;
        font-weight: bold;
        width: 100%;
        transition: background 0.3s;
    }

    .btn-create-sale:hover {
        background: var(--color-acento);
    }

    /* ===== Media Queries ===== */
    @media(max-width: 992px) {
        .main-container {
            flex-direction: column;
            height: auto;
        }

        .cart-sidebar {
            width: 100%;
            border-left: none;
            border-top: 2px solid var(--color-primario);
            margin-top: 1rem;
        }

        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));
        }

        .product-card.card img {
            height: 100px;
        }
    }

    @media(max-width: 768px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(140px, 1fr));
        }
    }

    @media(max-width: 576px) {
        .products-grid {
            grid-template-columns: repeat(auto-fill, minmax(100%, 1fr));
            padding: 0.5rem;
        }

        .product-card.card img {
            height: 80px;
        }
    }
</style>


<h2 class="mb-3">Nueva Venta</h2>

<div class="main-container">

    <!-- SECCIÓN DE PRODUCTOS -->
    <div style="flex: 1; display: flex; flex-direction: column;">

        <!-- Tabs -->
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

        <!-- Productos por categoría -->
        <div class="tab-content mt-3">
            @foreach($categories as $categoryName => $items)
            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                id="cat{{ Str::slug($categoryName) }}">

                <div class="products-grid">
                    @foreach($items as $p)
                    <div class="product-card card"
                        data-id="{{ $p->id }}"
                        data-name="{{ $p->name }}"
                        data-price="{{ $p->price }}">

                        <div class="card-body">
                            <h6 class="card-title">{{ $p->name }}</h6>
                            <p class="card-text">${{ number_format($p->price, 2) }}</p>
                        </div>

                    </div>
                    @endforeach
                </div>

            </div>
            @endforeach
        </div>

    </div>



    <!-- Carrito -->
    <div class="cart-sidebar">
        <h4>Carrito</h4>

        <select id="tipo_documento_id" class="cart-select">
            <option value="">-- Selecciona Tipo de Venta --</option>
            @foreach($tipoDocumentos as $tipoDocumento)
            <option value="{{ $tipoDocumento->id }}">{{ $tipoDocumento->nombre }}</option>
            @endforeach
        </select>

        <select id="customers_id" class="cart-select">
            <option value="">-- Selecciona Cliente --</option>
            @foreach($customers as $customer)
            <option value="{{ $customer->id }}">{{ $customer->nombre }}</option>
            @endforeach
        </select>

        <input type="date" id="sale_date" class="cart-date" value="{{ now()->format('Y-m-d') }}">

        <div class="cart-scroll">
            <table class="table table-sm table-bordered cart-table">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>Cant.</th>
                        <th>Total</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody id="cart-body">
                    <tr id="cart-empty">
                        <td colspan="4" class="text-center">Carrito vacío</td>
                    </tr>
                </tbody>
            </table>

            <div id="total-wrapper">Total: $<span id="cart-total">0.00</span></div>

            <div class="mt-3">
                <button id="submit-sale" class="btn-create-sale">Crear Venta</button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const products = document.querySelectorAll('.product-card');
        const cartBody = document.getElementById('cart-body');
        const cartTotalEl = document.getElementById('cart-total');
        let cart = {};

        function renderCart() {
            cartBody.innerHTML = '';
            let total = 0;

            if (Object.keys(cart).length === 0) {
                cartBody.innerHTML = '<tr id="cart-empty"><td colspan="4" class="text-center">Carrito vacío</td></tr>';
                cartTotalEl.textContent = '0.00';
                return;
            }

            Object.values(cart).forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
                <td>${item.name}</td>
                <td><input type="number" min="1" value="${item.quantity}" class="form-control cart-qty" data-id="${item.id}" style="width:60px"></td>
                <td>$${(item.price*item.quantity).toFixed(2)}</td>
                <td><button class="btn btn-sm btn-danger remove-item" data-id="${item.id}">X</button></td>
            `;
                cartBody.appendChild(row);
                total += item.price * item.quantity;
            });

            cartTotalEl.textContent = total.toFixed(2);
        }

        products.forEach(p => {
            p.addEventListener('click', () => {
                const id = p.dataset.id;
                if (cart[id]) {
                    cart[id].quantity++;
                } else {
                    cart[id] = {
                        id: id,
                        name: p.dataset.name,
                        price: parseFloat(p.dataset.price),
                        quantity: 1
                    }
                }
                renderCart();
            });
        });

        cartBody.addEventListener('input', function(e) {
            if (e.target.classList.contains('cart-qty')) {
                const id = e.target.dataset.id;
                cart[id].quantity = parseInt(e.target.value);
                renderCart();
            }
        });

        cartBody.addEventListener('click', function(e) {
            if (e.target.classList.contains('remove-item')) {
                const id = e.target.dataset.id;
                delete cart[id];
                renderCart();
            }
        });

        document.getElementById('submit-sale').addEventListener('click', function() {
            if (Object.keys(cart).length === 0) {
                alert('Agrega al menos un producto');
                return;
            }

            const tipo_documento_id = document.getElementById('tipo_documento_id').value;
            const customers_id = document.getElementById('customers_id').value;
            const sale_date = document.getElementById('sale_date').value;

            if (!tipo_documento_id || !customers_id || !sale_date) {
                alert('Completa todos los campos requeridos');
                return;
            }

            const payload = {
                tipo_documento_id,
                customers_id,
                sale_date,
                products: Object.values(cart)
            };

            fetch("{{ route('stores.sales.store', $store->id) }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify(payload)
            }).then(res => res.redirected ? window.location.href = res.url : res.json()).then(data => {
                if (data && data.success) {
                    alert('Venta creada con éxito!');
                    window.location.href = "{{ route('stores.sales.create', $store->id) }}";
                }
            }).catch(err => {
                console.error(err);
                alert('Error al crear la venta');
            });
        });
    });
</script>

@endsection