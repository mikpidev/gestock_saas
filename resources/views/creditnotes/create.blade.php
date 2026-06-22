@extends('layouts.admin')

@section('content')




<div class="gestok-form-card">
    <div class="gestok-form-header">
        <h1>Nueva Nota de Crédito</h1>
        <p>{{ $store->store_name }}</p>
    </div>

    <div class="gestok-form-body">
        <form action="{{ route('stores.creditnotes.store', $store->id) }}" method="POST" id="creditNoteForm">
            @csrf

            <div class="row g-3 mb-3">

                <div class="col-md-6">
                    <!-- Seleccionar la venta asociada -->
                    <label for="sale_id">Venta relacionada *</label>

                    <select id="sale_id" name="sale_id" class="form-control" required onchange="showSaleDetails()">
                        <option value="">-- Selecciona una venta --</option>

                        @foreach($sales as $sale)
                        <option value="{{ $sale->id }}"
                            data-sale='@json($sale)'
                            data-customer='@json($sale->customer)'
                            data-details='@json($sale->details)'>

                            {{ $sale->codigo_generacion }}

                        </option>
                        @endforeach
                    </select>

                    @error('sale_id')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

                <div class="col-md-6">
                    <!-- Fecha de emisión -->
                    <label for="credit_note_date">Fecha de emisión *</label>

                    <input type="date"
                        id="credit_note_date"
                        name="credit_note_date"
                        value="{{ old('credit_note_date', now()->format('Y-m-d')) }}"
                        class="form-control" required>

                    @error('credit_note_date')
                    <div class="text-danger">{{ $message }}</div>
                    @enderror
                </div>

            </div>

            <!-- SUPERIOR -->
            <table class="contenedor-superior">

                <tr style="background:#e6e6e6; font-weight:bold;">
                    <td>Nombre</td>
                    <td>Numero de Documento</td>
                    <td>NRC</td>
                    <td>Actividad Economica</td>
                    <td>Direccion</td>
                    <td>Correo Electronico</td>
                    <td>Telefono</td>
                </tr>

                <tr>
                    <td>{{$sale->store->taxInfo->razon_social}}</td>
                    <td>{{$sale->store->taxInfo->nit}}</td>
                    <td>{{$sale->store->taxInfo->nrc}}</td>
                    <td>{{$sale->store->taxInfo->actividad_economica}}</td>
                    <td>{{$sale->store->taxInfo->direccion_fiscal}} {{$sale->store->taxInfo->departamento->nombre}} {{$sale->store->taxInfo->municipio->nombre}}</td>
                    <td>{{$sale->store->taxInfo->email}}</td>
                    <td>{{$sale->store->taxInfo->telefono}}</td>
                </tr>


                <tr style="background:#e6e6e6; font-weight:bold;">
                    <td>Nombre</td>
                    <td>Numero de Documento</td>
                    <td>Razon Social</td>
                    <td>Codigo de Actividad Economica</td>
                    <td>Direccion</td>
                    <td>Correo Electronico</td>
                    <td>Telefono</td>

                </tr>
                <!-- CLIENTE VENTA RELACIONADA -->

                <tr id="saleCustomer">
                    <td></td>

                </tr>

                <tr style="background:#e6e6e6; font-weight:bold;">
                    <td>Código DTE:</td>
                    <td>Número de Control:</td>
                    <td>Sello DTE:</td>
                </tr>

                <tr id="saleInfo">
                    <td></td>

                </tr>

                <!-- EMPRESA -->
            </table>
            <!-- Detalles de la venta -->
            <div id="saleDetailsSection" class="sale-details-section" style="display: none;">

                <table class="details-table">

                    <thead>
                        <tr>
                            <td>
                                <h4>Seleccionar productos a acreditar</h4>
                                <p class="text-muted">Ingresa las cantidades que deseas acreditar de cada producto:</p>
                            </td>
                        </tr>
                        <tr>
                            <th>Producto</th>
                            <th>Precio Unit.</th>
                            <th>Disponible</th>
                            <th>Cant. a Acreditar</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody id="saleDetailsBody">
                        <!-- Filas generadas dinámicamente -->
                    </tbody>
                </table>

                <!-- Resumen de totales -->
                <div id="totalsSummary" style="margin-top: 1rem; padding: 1rem; background: #f0f0f0; border-radius: 5px; display: none;">
                    <strong>Resumen:</strong><br>
                    Subtotal: $<span id="summarySubtotal">0.00</span><br>
                    IVA (13%): $<span id="summaryTax">0.00</span><br>
                    <strong>Total a acreditar: $<span id="summaryTotal">0.00</span></strong>
                </div>
            </div>


            <!-- Motivo -->
            <label for="reason">Motivo de la nota de crédito *</label>
            <textarea id="reason" name="reason" rows="3" placeholder="Ejemplo: Devolución de producto, producto defectuoso, error en facturación..." required>{{ old('reason') }}</textarea>
            @error('reason')
            <div class="text-danger">{{ $message }}</div>
            @enderror

            <div class="gestok-form-actions">
                <button type="submit" class="btn-create-nc" id="submitBtn">Crear Nota de Crédito</button>
                <a href="{{ route('stores.creditnotes.index', $store->id) }}" class="btn btn-secondary">Cancelar</a>
            </div>
            </table>
        </form>


    </div>
</div>

<script>
    function showSaleDetails() {

        const select = document.getElementById('sale_id');
        const selectedOption = select.selectedOptions[0];

        const saleInfo = document.getElementById('saleInfo');
        const tbody = document.getElementById('saleDetailsBody');
        const submitBtn = document.getElementById('submitBtn');


        // limpiar tabla productos
        tbody.innerHTML = '';

        // limpiar customer
        document.getElementById('saleCustomer').innerHTML = '<td></td>';

        //limpiar SaleInfo
        document.getElementById('saleInfo').innerHTML = '<td></td>';



        // si no seleccionó venta
        if (!select.value) {

            document.getElementById('saleDetailsSection').style.display = 'none';

            submitBtn.disabled = true;

            return;
        }

        // obtener customer
        const customer = JSON.parse(selectedOption.dataset.customer);

        // llenar tabla customer
        document.getElementById('saleCustomer').innerHTML = `
        <td>${customer.nombre ?? ''}</td>
        <td>${customer.numDocumento ?? ''}</td>
        <td>${customer.nombreComercial ?? ''}</td>
        <td>${customer.codActividad ?? ''}</td>
        <td>${customer.direccion_complemento ?? ''}</td>
        <td>${customer.correo ?? ''}</td>
        <td>${customer.telefono ?? ''}</td>
    `;

        const sale = JSON.parse(selectedOption.dataset.sale);


        document.getElementById('saleInfo').innerHTML = `
            <td>${sale.codigo_generacion ?? ''}</td>
            <td>${sale.numero_control ?? ''}</td>
            <td>${sale.sello_recibido ?? ''}</td>
    `;

        // obtener detalles
        const details = JSON.parse(selectedOption.dataset.details);
        details.forEach(detail => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    ${detail.product_type?.name || 'Producto'}
                    <input type="hidden" name="items[${detail.id}][sale_detail_id]" value="${detail.id}">
                </td>
                <td>$${parseFloat(detail.unit_price).toFixed(2)}</td>
                <td>${parseFloat(detail.quantity).toFixed(2)}</td>
                <td class="quantity-input">
                    <input type="number"
                        name="items[${detail.id}][quantity]"
                        value="0"
                        min="0"
                        max="${parseFloat(detail.quantity).toFixed(2)}"
                        step="1"
                        onchange="updateTotals()"
                        class="quantity-field">
                </td>
                <td class="item-subtotal">$0.00</td>
            `;
            tbody.appendChild(row);
        });

        updateTotals();


        document.getElementById('saleDetailsSection').style.display = 'block';
    }

    function updateTotals() {
        const quantityFields = document.querySelectorAll('.quantity-field');
        let subtotal = 0;

        quantityFields.forEach(field => {
            const quantity = parseFloat(field.value) || 0;
            const unitPrice = parseFloat(field.closest('tr').querySelector('td:nth-child(2)').textContent.replace('$', ''));
            const itemSubtotal = quantity * unitPrice;
            field.closest('tr').querySelector('.item-subtotal').textContent = `$${itemSubtotal.toFixed(2)}`;
            subtotal += itemSubtotal;
        });

        const tax = subtotal * 0.13;
        const total = subtotal + tax;

        document.getElementById('summarySubtotal').textContent = subtotal.toFixed(2);
        document.getElementById('summaryTax').textContent = tax.toFixed(2);
        document.getElementById('summaryTotal').textContent = total.toFixed(2);

        const submitBtn = document.getElementById('submitBtn');
        submitBtn.disabled = !Array.from(quantityFields).some(f => parseFloat(f.value) > 0);
    }

    document.getElementById('creditNoteForm').addEventListener('submit', function(e) {
        if (!Array.from(document.querySelectorAll('.quantity-field')).some(f => parseFloat(f.value) > 0)) {
            e.preventDefault();
            alert('Debe seleccionar al menos un producto y cantidad para acreditar.');
        }
    });
</script>

@endsection