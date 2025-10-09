@extends('layouts.admin')

@section('content')


<!--Mostrar compania-->
    <h1>Informacion tributaria de la Tienda</h1>

    <div class="card">
        <div class="card-body">

            <h6>{{ $store->company->company_name ?? 'Sin compañía' }}</h6>
            <h5 class="card-subtitle">{{ $store->store_name ?? 'Sin Tienda'}}</h5>

            <p class="card-text"><strong>NIT</strong> {{ $storeTaxInfo->nit }}</p>
            <p class="card-text"><strong>NRC</strong> {{ $storeTaxInfo->nrc ?? 'Sin NCR' }}</p>
            <p class="card-text"><strong>Razón Social</strong> {{ $storeTaxInfo->razon_social }}</p>
            <p class="card-text"><strong>Actividad Económica</strong> {{ $storeTaxInfo->actividad_economica }}</p>
            <p class="card-text"><strong>Dirección Fiscal</strong> {{ $storeTaxInfo->direccion_fiscal }}</p>
            <p class="card-text"><strong>Correo Electrónico</strong> {{ $storeTaxInfo->email }}</p>
            <p class="card-text"><strong>Teléfono</strong> {{ $storeTaxInfo->telefono }}</p>
            <p class="card-text"><strong>Certificado de Firma Digital</strong> {{ $storeTaxInfo->cert_firma_digital }}</p>
            <p class="card-text"><strong>Estado</strong> {{ $storeTaxInfo->estado }}</p>
            <p class="card-text"><strong>Comentarios</strong> {{ $storeTaxInfo->comentarios }}</p>
        </div>


    </div>





@endsection