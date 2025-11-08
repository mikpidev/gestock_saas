@extends('layouts.admin')

@section('content')
<!--     formulario para editar informacion tributaria -->
<form action="{{ route('stores_tax_info.update', [$storeTaxInfo->id, $storeTaxInfo->store->company->id]) }}" method="POST">
    @csrf
    @method('PUT')
    @include('stores_tax_info._form')
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