@extends('layouts.admin')

@section('content')
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">


<h2 class="mb-3">Nueva Venta</h2>

<div class="main-container">

    <!-- SECCIÓN DE PRODUCTOS -->
    <div style="flex: 1; display: flex; flex-direction: column;">

        <!-- Tabs -->
        <ul class="nav nav-tabs" id="productTabs">
            @foreach($categories as $categoryName => $items)
            <li class="nav-item">
                <a class="nav-link gradient-text {{ $loop->first ? 'active' : '' }}  "
                    data-bs-toggle="tab"
                    href="#cat{{ Str::slug($categoryName) }}" >
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

        <select  id="payment_method" name="payment_method" class="cart-select">
            <option value="Efectivo">Efectivo</option>
            <option value="Tarjeta">Tarjeta</option>
            <option value="Transferencia">Transferencia</option>
        </select>


        <input type="date" id="sale_date" class="cart-date" value="{{ now()->format('Y-m-d') }}">


        <div class="form-check">
            <input class="form-check-input" type="checkbox" value="0.10" id="discount_amount">
            <label class="form-check-label" for="discount_amount"> Aplicar 10% de Descuento </label>
        </div>

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
            <div>
                <strong>Subtotal:</strong> $<span id="cart-subtotal">0.00</span><br>
                <strong>Descuento:</strong> <span id="cart-discount">$0.00</span><br>
                <strong>Total con descuento:</strong> $<span id="cart-total">0.00</span>
            </div>
            <div class="mt-3">
                <button id="submit-sale" class="btn-create-sale">Crear Venta</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalVentaCreada" tabindex="-1">
<div class="modal-dialog">
    <div class="modal-content">

        <div class="modal-header">
            <h5 class="modal-title">Venta creada</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>

        <div class="modal-body text-center">
            Ticket listo para imprimir.
        </div>

        <div class="modal-footer">
            <button id="btnImprimirVenta" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir Ticket
            </button>
            <button id="btnImprimirPreOrden" class="btn btn-primary">
                <i class="bi bi-printer"></i> Imprimir Pre Orden
            </button>
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
                const subtotalEl = document.getElementById("cart-subtotal");
                subtotalEl.textContent = '0.00';
                const discountEl = document.getElementById("cart-discount");
                discountEl.textContent = '$0.00';
                return;
            }

            // Calcular total sin descuento
            Object.values(cart).forEach(item => {
                const row = document.createElement('tr');
                row.innerHTML = `
            <td>${item.name}</td>
            <td><input type="number" min="1" value="${item.quantity}" class="form-control cart-qty" data-id="${item.id}" style="width:60px"></td>
            <td>$${(item.price * item.quantity).toFixed(2)}</td>
            <td><button class="btn btn-sm btn-danger remove-item" data-id="${item.id}">X</button></td>
        `;
                cartBody.appendChild(row);

                total += item.price * item.quantity;
            });

            // Mostrar total sin descuento
            const subtotalEl = document.getElementById("cart-subtotal");
            subtotalEl.textContent = total.toFixed(2);

            const discountCheckbox = document.getElementById("discount_amount");
            let discountPercent = 0;

            if (discountCheckbox.type === "checkbox" && discountCheckbox.checked === true) {
                discountPercent = parseFloat(discountCheckbox.value);
            }

            // Calcular descuento
            let discountAmount = total * discountPercent;

            // Total con descuento
            let totalWithDiscount = total - discountAmount;

            // Mostrar descuento
            const discountEl = document.getElementById("cart-discount");
            discountEl.textContent = discountAmount > 0 ? `- $${discountAmount.toFixed(2)}` : '$0.00';

            // Mostrar total final
            const finalTotalEl = document.getElementById("cart-total");
            finalTotalEl.textContent = totalWithDiscount.toFixed(2);
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
            const payment_method = document.getElementById('payment_method').value;

            if (!tipo_documento_id || !customers_id || !sale_date) {
                alert('Completa todos los campos requeridos');
                return;
            }
            const discountCheckbox = document.getElementById("discount_amount");
            const discount_amount = discountCheckbox.checked ? parseFloat(discountCheckbox.value) : 0;

            const payload = {
                tipo_documento_id,
                customers_id,
                discount_amount,
                sale_date,
                payment_method,
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

                    // ABRIR MODAL
                    let modal = new bootstrap.Modal(document.getElementById('modalVentaCreada'));
                    modal.show();

                    // PREPARAR BOTÓN DE IMPRIMIR
                    document.getElementById('btnImprimirVenta').onclick = function() {
                        const w = window.open(data.ticket_url, '_blank', 'width=400,height=800');
                        w.onload = () => w.print();
                    };

                    // PREPARAR BOTÓN DE PRE ORDEN
                    document.getElementById('btnImprimirPreOrden').onclick = function() {
                        console.log('PREORDER URL:', data.pre_order_url);
                        const w = window.open(data.pre_order_url, '_blank', 'width=400,height=800');
                        w.onload = () => w.print();
                    };

                    // RESETEAR CARRITO
                    cart = {};
                    renderCart();
                }
            })
            .catch(err => {
                console.error(err);
                alert('Error al crear la venta');
            });
        });
    });
</script>

@endsection