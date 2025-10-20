@extends('layouts.admin')

@section('content')

<style>
    /* Se mantiene el mismo estilo */
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
        <form action="{{ route('stores.creditnotes.store', $store->id) }}" method="POST">
            @csrf

            <!-- Seleccionar la venta asociada -->
            <label for="sale_id">Venta relacionada</label>
            <select id="sale_id" name="sale_id" required>
                <option value="">-- Selecciona una venta --</option>
                @foreach($sales as $sale)
                    <option value="{{ $sale->id }}" {{ old('sale_id') == $sale->id ? 'selected' : '' }}>
                        #{{ $sale->codigo_generacion }} — {{ $sale->customer->nombre ?? 'Sin cliente' }} ({{ $sale->sale_date->format('d/m/Y') }})
                    </option>
                @endforeach
            </select>
            @error('sale_id')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Fecha de emisión -->
            <label for="credit_date">Fecha de emisión</label>
            <input type="date" id="credit_date" name="credit_date" value="{{ old('credit_date', now()->format('Y-m-d')) }}" required>
            @error('credit_date')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Monto total -->
            <label for="amount">Monto total</label>
            <input type="number" step="0.01" id="amount" name="amount" value="{{ old('amount') }}" placeholder="Ej: 125.50" required>
            @error('amount')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Motivo -->
            <label for="reason">Motivo</label>
            <textarea id="reason" name="reason" rows="3" placeholder="Ejemplo: Devolución de producto, descuento aplicado..." required>{{ old('reason') }}</textarea>
            @error('reason')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <!-- Estado -->
            <label for="status">Estado</label>
            <select id="status" name="status" required>
                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Borrador</option>
                <option value="issued" {{ old('status') == 'issued' ? 'selected' : '' }}>Emitida</option>
                <option value="cancelled" {{ old('status') == 'cancelled' ? 'selected' : '' }}>Anulada</option>
            </select>
            @error('status')
                <div class="text-danger">{{ $message }}</div>
            @enderror

            <div class="gestok-form-actions">
                <button type="submit" class="btn">Crear Nota de Crédito</button>
                <a href="{{ route('stores.creditnotes.index', $store->id) }}" class="btn btn-secondary">Cancelar</a>
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

@endsection
