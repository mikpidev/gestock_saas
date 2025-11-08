@extends('layouts.admin')

@section('content')

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="fw-bold">Información Tributaria de la Tienda</h2>

        <!-- BOTÓN EDITAR -->
        <a href="{{ route('store_tax_info.edit', $store->id) }}" class="btn btn-warning text-white">
            <i class="fas fa-edit"></i> Editar Información
        </a>
    </div>

    <div class="card shadow-sm border-0 rounded-4">
        <div class="card-body">

            <!-- Header Empresa -->
            <div class="text-center mb-4">
                <h5 class="text-muted mb-1">{{ $store->company->company_name ?? 'Sin compañía' }}</h5>
                <h4 class="fw-bold">{{ $store->store_name ?? 'Sin Tienda' }}</h4>
                <hr>
            </div>

            <!-- Grid de información -->
            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label fw-semibold">NIT</label>
                    <div class="p-2 border rounded bg-light">{{ $storeTaxInfo->nit }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">NRC</label>
                    <div class="p-2 border rounded bg-light">{{ $storeTaxInfo->nrc ?? '—' }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Razón Social</label>
                    <div class="p-2 border rounded bg-light">{{ $storeTaxInfo->razon_social }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Actividad Económica</label>
                    <div class="p-2 border rounded bg-light">{{ $storeTaxInfo->actividad_economica }}</div>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Dirección Fiscal</label>
                    <div class="p-2 border rounded bg-light">{{ $storeTaxInfo->direccion_fiscal }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Correo Electrónico</label>
                    <div class="p-2 border rounded bg-light">{{ $storeTaxInfo->email }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Teléfono</label>
                    <div class="p-2 border rounded bg-light">{{ $storeTaxInfo->telefono }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Certificado Firma Digital</label>
                    <div class="p-2 border rounded bg-light text-truncate">{{ $storeTaxInfo->cert_firma_digital }}</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label fw-semibold">Estado</label>
                    <div class="p-2 border rounded bg-light">
                        @if($storeTaxInfo->estado == 'ACTIVO')
                            <span class="badge bg-success">ACTIVO</span>
                        @else
                            <span class="badge bg-danger">{{ $storeTaxInfo->estado ?? '—' }}</span>
                        @endif
                    </div>
                </div>

                <div class="col-md-12">
                    <label class="form-label fw-semibold">Comentarios</label>
                    <div class="p-3 border rounded bg-light">
                        {{ $storeTaxInfo->comentarios ?? 'Sin comentarios' }}
                    </div>
                </div>

            </div>

        </div>
    </div>
</div>

@endsection
