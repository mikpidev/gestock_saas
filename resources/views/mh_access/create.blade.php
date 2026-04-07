@extends('layouts.admin')

@section('content')
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>Crear Acceso a Hacienda</h2>
        <a href="{{ route('stores.show', $store->id) }}" class="btn btn-secondary">Volver a la Tienda</a>
    </div>
    <form action="{{ route('mh_access.store', ['store' => $store->id]) }}" method="POST">
        @csrf
        @include('mh_access._form', ['store' => $store, 'mhAccess' => null])
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