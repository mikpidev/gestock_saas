@extends('layouts.admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Crear Tienda</h2>

</div>

<!--     formulario para la creacion de un companias -->
<form action="{{ route('store.store', ['company' => $company->id]) }}" method="POST">
    @csrf
    @include('store._form')
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