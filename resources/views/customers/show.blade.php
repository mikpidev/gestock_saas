@extends('layouts.admin')

@section('content')

<div class="container mt-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <!-- Tarjeta principal del cliente -->
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="currentColor" class="bi bi-person-circle me-2" viewBox="0 0 16 16">
                            <path d="M11 6a3 3 0 1 1-6 0 3 3 0 0 1 6 0"/>
                            <path fill-rule="evenodd" d="M0 8a8 8 0 1 1 16 0A8 8 0 0 1 0 8m8-7a7 7 0 0 0-5.468 11.37C3.242 11.226 4.805 10 8 10s4.757 1.225 5.468 2.37A7 7 0 0 0 8 1"/>
                        </svg>
                        Información del Cliente
                    </h4>
                </div>
                
                <div class="card-body">
                    <div class="row">
                        <!-- Información Personal -->
                        <div class="col-md-6">
                            <h5 class="text-primary mb-3">
                                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" fill="currentColor" class="bi bi-person-vcard me-2" viewBox="0 0 16 16">
                                    <path d="M5 8a2 2 0 1 0 0-4 2 2 0 0 0 0 4m4-2.5a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3a.5.5 0 0 1-.5-.5M9 8a.5.5 0 0 1 .5-.5h3a.5.5 0 0 1 0 1h-3A.5.5 0 0 1 9 8m1 2.5a.5.5 0 0 1 .5-.5h2a.5.5 0 0 1 0 1h-2a.5.5 0 0 1-.5-.5"/>
                                    <path d="M2 2a2 2 0 0 0-2 2v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM1 4a1 1 0 0 1 1-1h12a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1z"/>
                                </svg>
                                Datos del Cliente
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Nombre</label>
                                <p class="fw-bold fs-5">{{ $customer->nombre }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Número de Documento</label>
                                <p class="fw-bold">{{ $customer->numDocumento }}</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Correo electrónico</label>
                                <p class="fw-bold">{{ $customer->correo }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Teléfono</label>
                                <p class="fw-bold">{{ $customer->telefono }}</p>
                            </div>
                        </div>
                        
                        <!-- Información adicional -->
                        <div class="col-md-6">
                            <h5 class="text-success mb-3">
                                Información Fiscal / Empresa
                            </h5>

                            <div class="mb-3">
                                <label class="form-label text-muted">Razón Social</label>
                                <p class="fw-bold">{{ $customer->descActividad }}</p>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label text-muted">Codigo de Actividad Económica</label>
                                <p class="fw-bold">{{ $customer->codActividad}}</p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label text-muted">Dirección</label>
                                <p class="fw-bold">{{ $customer->direccion_complemento }}</p>
                                <p class="fw-bold">{{ $customer->departamento?->nombre }}</p>
                                <p class="fw-bold">{{ $customer->municipio?->nombre }}</p>
                            </div>

                        </div>
                    </div>
                </div>
                
                <!-- Footer con acciones -->
                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">Última actualización: {{ $customer->updated_at->format('d/m/Y H:i') }}</small>
                        </div>
                        <div class="col-md-6 text-end">
                            <a href="{{ route('stores.customers.edit', [$store->id, $customer->id]) }}" class="btn btn-outline-primary btn-sm me-2">
                                Editar
                            </a>
                            <a href="{{ route('stores.customers.index', $store->id) }}" class="btn btn-secondary btn-sm">
                                Volver
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection
