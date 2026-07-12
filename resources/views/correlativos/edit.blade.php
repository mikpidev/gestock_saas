@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Editar Correlativo</h2>
    <a href="{{ route('stores.show', $store->id) }}" class="btn btn-secondary">Volver a la Tienda</a>
</div>
<form action="{{ route('correlativos.update', $store->id) }}" method="POST">
    @csrf
    @method('PUT')

    @include('correlativos._form')
</form>

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
@endsection

