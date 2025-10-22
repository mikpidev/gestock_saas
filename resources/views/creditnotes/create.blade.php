@extends('layouts.admin')

@section('content')

<style>
    .gestok-form-card {
        background: #fff;
        color: #000;
        width: 100%;
        max-width: 800px;
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

    .gestok-form-body {
        padding: 2rem;
    }

    .gestok-form-body label {
        font-size: 0.9rem;
        display: block;
        margin-bottom: 0.3rem;
        font-weight: 500;
    }

    .gestok-form-body input,
    .gestok-form-body select,
    .gestok-form-body textarea {
        width: 100%;
        padding: 0.6rem;
        margin-bottom: 1rem;
        border: 1px solid #ccc;
        border-radius: 5px;
        font-size: 0.95rem;
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

    .gestok-form-actions {
        display: flex;
        gap: 0.5rem;
        flex-direction: column;
    }

    .details-table {
        width: 100%;
        border-collapse: collapse;
        margin: 1rem 0;
    }

    .details-table th,
    .details-table td {
        padding: 0.75rem;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }

    .details-table th {
        background: #f8f9fa;
        font-weight: 600;
    }

    .details-table input[type="number"] {
        width: 80px;
        padding: 0.3rem;
        border: 1px solid #ccc;
        border-radius: 3px;
    }

    .sale-details-section {
        margin: 1.5rem 0;
        padding: 1rem;
        border: 1px solid #e0e0e0;
        border-radius: 5px;
        background: #f9f9f9;
    }

    .sale-info {
        background: #e8f4fd;
        padding: 1rem;
        border-radius: 5px;
        margin-bottom: 1rem;
    }

    .quantity-input {
        text-align: center;
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
        <h1>Nueva Nota de Crédito</h1>
        <p>{{ $store->store_name }}</p>
    </div>

    <div class="gestok-form-body">
        <form action="{{ route('stores.creditnotes.store', $store->id) }}" method="POST" id="creditNoteForm">
            @csrf

            <!-- Seleccionar la venta asociada -->
            <label for="sale_id">Venta relacionada *</label>
            <select id="sale_id" name="sale_id" required onchange="showSaleDetails()">
                <option value="">-- Selecciona una venta --</option>
                @foreach($sales as $sale)
                <option value="{{ $sale->id }}"
                    data-customer="{{ $sale->customer->nombre ?? 'Sin cliente' }}"
                    data-date="{{ $sale->sale_date->format('d/m/Y') }}"
                    data-total="{{ number_format($sale->total_amount, 2) }}"
                    data-details='@json($sale->details)'>
                    #{{ $sale->codigo_generacion }} — {{ $sale->customer->nombre ?? 'Sin cliente' }} ({{ $sale->sale_date->format('d/m/Y') }}) - ${{ number_format($sale->total_amount, 2) }}
                </option>
                @endforeach
            </select>
            @error('sale_id')
            <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Información de la venta seleccionada -->
            <div id="saleInfo" class="sale-info" style="display: none;">
                <strong>Venta seleccionada:</strong>
                <span id="saleCustomer"></span> -
                <span id="saleDate"></span> -
                Total: $<span id="saleTotal"></span>
            </div>

            <!-- Detalles de la venta -->
            <div id="saleDetailsSection" class="sale-details-section" style="display: none;">
                <h4>Seleccionar productos a acreditar</h4>
                <p class="text-muted">Ingresa las cantidades que deseas acreditar de cada producto:</p>

                <table class="details-table">
                    <thead>
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

            <!-- Fecha de emisión -->
            <label for="credit_note_date">Fecha de emisión *</label>
            <input type="date" id="credit_note_date" name="credit_note_date" value="{{ old('credit_note_date', now()->format('Y-m-d')) }}" required>
            @error('credit_note_date')
            <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Motivo -->
            <label for="reason">Motivo de la nota de crédito *</label>
            <textarea id="reason" name="reason" rows="3" placeholder="Ejemplo: Devolución de producto, producto defectuoso, error en facturación..." required>{{ old('reason') }}</textarea>
            @error('reason')
            <div class="text-danger">{{ $message }}</div>
            @enderror

            <div class="gestok-form-actions">
                <button type="submit" class="btn" id="submitBtn" disabled>Crear Nota de Crédito</button>
                <a href="{{ route('stores.creditnotes.index', $store->id) }}" class="btn btn-secondary">Cancelar</a>
            </div>
        </form>
    </div>
</div>

<script>
    function showSaleDetails() {
        const saleId = document.getElementById('sale_id').value;
        const saleInfo = document.getElementById('saleInfo');
        const tbody = document.getElementById('saleDetailsBody');
        const submitBtn = document.getElementById('submitBtn');

        tbody.innerHTML = ''; // limpiar tabla

        if (!saleId) {
            saleInfo.style.display = 'none';
            document.getElementById('saleDetailsSection').style.display = 'none';
            submitBtn.disabled = true;
            return;
        }

        const selectedOption = document.querySelector(`#sale_id option[value="${saleId}"]`);
        document.getElementById('saleCustomer').textContent = selectedOption.dataset.customer;
        document.getElementById('saleDate').textContent = selectedOption.dataset.date;
        document.getElementById('saleTotal').textContent = selectedOption.dataset.total;
        saleInfo.style.display = 'block';
        document.getElementById('saleDetailsSection').style.display = 'block';

        // cargar detalles desde data-details
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